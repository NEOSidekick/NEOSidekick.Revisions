<?php
declare(strict_types=1);

namespace NEOSidekick\Revisions\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Domain\Service\Context as ContentContext;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\ContentRepository\Utility;
use Neos\ContentRepository\Validation\Validator\NodeIdentifierValidator;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\AbstractRenderer;
use Neos\Flow\Annotations as Flow;
use NEOSidekick\Revisions\Domain\Model\Revision;
use NEOSidekick\Revisions\Domain\Repository\RevisionRepository;
use NEOSidekick\Revisions\Exception\RevisionApplyDeniedException;
use NEOSidekick\Revisions\Exception\RevisionNotApplicableException;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Utility\Algorithms;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Neos\Service\PublishingService;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class RevisionService
{
    /**
     * Leaves live as it is: the node and its descendants are neither restored nor removed
     */
    public const RESOLUTION_SKIP = '__skip';

    /**
     * Moves content that was moved to another page since the revision back to its place in the revision
     */
    public const RESOLUTION_MOVE_BACK = '__moveBack';

    /**
     * Removes content that was moved to the page since the revision
     */
    public const RESOLUTION_REMOVE = '__remove';

    /**
     * @Flow\Inject
     * @var NodeExportService
     */
    protected $nodeExportService;

    /**
     * @Flow\Inject
     * @var RevisionRepository
     */
    protected $revisionRepository;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var NodeImportService
     */
    protected $nodeImportService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array<string, NodeInterface>
     */
    protected static $nodesForRevisions = [];

    /**
     * @var array<string, bool>
     */
    protected static $movedNodes = [];

    /**
     * Applied revisions that the revisions created on shutdown refer to, by document node identifier
     *
     * @var array<string, Revision>
     */
    protected static $appliedRevisions = [];

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var PublishingService
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\InjectConfiguration(package="NEOSidekick.Revisions")
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * @var TranslationHelper
     */
    protected $translationHelper;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var AuthorizationService
     */
    protected $authorizationService;

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="fallbackNodeType")
     * @var string|null
     */
    protected $fallbackNodeTypeName;

    public function createRevision(NodeInterface $node, string $label = null): ?Revision
    {
        return $this->createRevisionInternal($node, $label);
    }

    public function setLabel(?Revision $revision, $label): void
    {
        if (!$revision) {
            return;
        }
        $revision->setLabel($label);
        $this->logger->info(sprintf('Set label "%s" for revision %s', $label, $revision->getIdentifier()));
        $this->revisionRepository->update($revision);
    }

    protected function createRevisionInternal(NodeInterface $node, string $label = null): ?Revision
    {
        $xmlWriter = $this->nodeExportService->export($node->getPath());
        $content = $xmlWriter->flush();
        $creator = $this->securityContext->canBeInitialized() ? $this->securityContext->getAccount() : null;
        $enableCompression = $this->settings['compression']['enabled'] ?? true;

        $revision = new Revision(
            $node->getIdentifier(),
            $creator ? $creator->getAccountIdentifier() : 'CLI',
            $content,
            $label,
            $enableCompression,
            array_key_exists($node->getIdentifier(), self::$movedNodes)
        );

        try {
            $this->revisionRepository->add($revision);
            $this->logger->info(sprintf('Created revision for node %s', $node->getPath()));
        } catch (IllegalObjectTypeException $e) {
            $this->logger->error(sprintf('Failed to create revision for node %s', $node->getPath()));
            return null;
        }

        return $revision;
    }

    /**
     * @return array<Revision>
     */
    public function getRevisions(NodeInterface $node): array
    {
        return $this->revisionRepository->findByNodeIdentifier($node->getIdentifier())->toArray();
    }

    public function getRevision(string $identifier): ?Revision
    {
        return $this->revisionRepository->findByIdentifier($identifier);
    }

    /**
     * Applies the revision to the document node it was created from, at the place that node has now
     *
     * @param array<mixed> $resolutions By node identifier, see validateRevision()
     * @throws RevisionNotApplicableException if a node still needs a resolution, cannot be applied at all, or changed
     *     in a way that needs a resolution while the revision was applied
     * @throws RevisionApplyDeniedException
     */
    public function applyRevision(string $identifier, array $resolutions = []): bool
    {
        $revision = $this->getRevision($identifier);

        if (!$revision) {
            $this->logger->warning(sprintf('Could not find revision with identifier %s', $identifier));
            return false;
        }

        $context = $this->contextFactory->create();
        $node = $context->getNodeByIdentifier($revision->getNodeIdentifier());
        $liveWorkspace = $context->getWorkspace();

        if (!$node) {
            $this->logger->warning(sprintf('Could not find node with identifier "%s" to apply revision to', $revision->getNodeIdentifier()));
            return false;
        }

        $revisionContent = $revision->getContent();
        if (!$revisionContent) {
            $this->logger->warning(sprintf('Could not find any content in revision %s', $identifier));
            return false;
        }

        try {
            $nodesInRevision = $this->withConfiguredAuthorizationChecks(function () use ($revisionContent, $node) {
                return $this->nodeImportService->parseNodes($revisionContent, $node->getParentPath(), true);
            });
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to read revision %s: %s', $revision->getIdentifier(), $throwable->getMessage()));
            return false;
        }
        $resolved = $this->withConfiguredAuthorizationChecks(function () use ($revision, $node, $nodesInRevision, $liveWorkspace, $resolutions) {
            return $this->resolveRevision($revision, $node, $nodesInRevision, $liveWorkspace, $resolutions);
        });
        if (!$resolved['isApplicable']) {
            throw new RevisionNotApplicableException($resolved['rows'], $resolved['errors']);
        }

        $this->emitRevisionApplying($node, $revision);

        // Staging the revision in a workspace and publishing it lets search indexing, frontend revalidation,
        // redirects and the event log handle it like any other change to live
        $workspace = $this->createTemporaryWorkspace($liveWorkspace);
        try {
            $publishedNodes = $this->withConfiguredAuthorizationChecks(function () use ($resolved, $nodesInRevision, $node, $workspace, $liveWorkspace, $revision) {
                $this->stageNodes($resolved['nodesToStage'], $workspace, $node->getIdentifier(), $resolved['variantsToMoveBack']);
                $this->removeNodesMissingInRevision($node, $nodesInRevision, $workspace, $resolved['keptIdentifiers'], $resolved['protectedVariants']);
                $this->persistenceManager->persistAll();
                $publishedNodes = $this->publishingService->getUnpublishedNodes($workspace);
                $this->assertNodesAreEditable($publishedNodes);
                $this->logger->info(sprintf('Publishing %d changed node variants to apply revision %s', count($publishedNodes), $revision->getIdentifier()));
                $this->publishingService->publishNodes($publishedNodes, $liveWorkspace);
                $this->persistenceManager->persistAll();
                return $publishedNodes;
            });
        } catch (AccessDeniedException $exception) {
            throw new RevisionApplyDeniedException($exception->getMessage(), 1790500002, $exception);
        } catch (RevisionNotApplicableException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to apply revision %s: %s', $revision->getIdentifier(), $throwable->getMessage()));
            return false;
        } finally {
            $this->removeTemporaryWorkspace($workspace);
        }

        $this->logger->info(sprintf('Applied revision %s on node %s', $revision->getIdentifier(), $node->getIdentifier()));
        foreach ($resolved['rows'] as $row) {
            $this->logger->info(sprintf('Applied revision %s with resolution %s for node %s of type %s', $revision->getIdentifier(), $row['resolution'], $row['identifier'], $row['nodeType']['name']));
        }

        // The revision of the restored state is created on shutdown like for any publish. The document registered
        // while publishing belongs to the removed temporary workspace, so the live one replaces it.
        if ($this->settings['revisions']['createRevisionAfterApply']) {
            self::$nodesForRevisions[$node->getIdentifier()] = $node;
            self::$appliedRevisions[$node->getIdentifier()] = $revision;
        } else {
            unset(self::$nodesForRevisions[$node->getIdentifier()]);
        }

        $this->emitRevisionApplied($node, $revision, $publishedNodes);

        return true;
    }

    /**
     * Returns the nodes that need a resolution before the revision can be applied, as rows with the resolution each one
     * got so far, and the problems that no resolution can fix. Reading the revision writes nothing.
     *
     * A resolution is keyed by the identifier of a node in the revision or of content moved to the page since the
     * revision, and holds exactly one of the RESOLUTION_* keys set to true, e.g. ['<identifier>' => ['__skip' => true]].
     *
     * @param array<mixed> $resolutions
     * @return array{rows: array<array>, errors: array<string>, isApplicable: bool}
     */
    public function validateRevision(Revision $revision, array $resolutions = []): array
    {
        $context = $this->contextFactory->create();
        $node = $context->getNodeByIdentifier($revision->getNodeIdentifier());
        $revisionContent = $revision->getContent();
        // applyRevision() logs and rejects revisions without a node or content, and unreadable ones
        $unchecked = ['rows' => [], 'errors' => [], 'isApplicable' => true];
        if (!$node || !$revisionContent) {
            return $unchecked;
        }
        return $this->withConfiguredAuthorizationChecks(function () use ($revision, $revisionContent, $node, $context, $resolutions, $unchecked) {
            try {
                $nodesInRevision = $this->nodeImportService->parseNodes($revisionContent, $node->getParentPath());
            } catch (\Throwable $throwable) {
                return $unchecked;
            }
            $resolved = $this->resolveRevision($revision, $node, $nodesInRevision, $context->getWorkspace(), $resolutions);
            return ['rows' => $resolved['rows'], 'errors' => $resolved['errors'], 'isApplicable' => $resolved['isApplicable']];
        });
    }

    /**
     * Publishing writes every variant through Node::setNodeData(), which the edit privileges cover. Staging only checks
     * them through the setters it calls, and Node::setIndex() is not covered, so a variant that only changed its position
     * would fail halfway through publishing without this check.
     *
     * @param array<NodeInterface> $nodes
     * @throws AccessDeniedException
     */
    protected function assertNodesAreEditable(array $nodes): void
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return;
        }
        foreach ($nodes as $node) {
            if (!$this->authorizationService->isGrantedToEditNode($node)) {
                throw new AccessDeniedException(sprintf('Not allowed to edit node "%s" in %s', $node->getPath(), json_encode($node->getDimensions())), 1790500003);
            }
        }
    }

    /**
     * Runs the callback without the editor's node privileges unless the settings ask for them
     *
     * @return mixed The result of the callback
     */
    protected function withConfiguredAuthorizationChecks(\Closure $callback)
    {
        if (!($this->settings['revisions']['applyWithoutAuthorizationChecks'] ?? true)) {
            return $callback();
        }
        $result = null;
        $this->securityContext->withoutAuthorizationChecks(static function () use ($callback, &$result) {
            $result = $callback();
        });
        return $result;
    }

    /**
     * Finds what keeps the revision from being applied as it is, checks the resolutions against it and plans the apply
     *
     * There are rows for nodes whose node type no longer fits, for content moved to another page since the revision and
     * for content moved to the page since then, one per node identifier with the variants concerned. Rows inside a
     * skipped node are left out, and so is content that only a node moved back would bring to the page, as long as that
     * node is not moved back.
     *
     * @param array<array> $nodesInRevision
     * @param array<mixed> $resolutions By node identifier, see validateRevision()
     * @return array{rows: array<array>, errors: array<string>, isApplicable: bool, nodesToStage: array<array>, keptIdentifiers: array<string, bool>, protectedVariants: array<string, bool>, variantsToMoveBack: array<string, bool>}
     */
    protected function resolveRevision(Revision $revision, NodeInterface $documentNode, array $nodesInRevision, Workspace $liveWorkspace, array $resolutions): array
    {
        $documentIdentifier = $documentNode->getIdentifier();
        $revisionDate = $revision->getCreationDateTime();
        $variantsInRevision = $this->getVariantsInRevision($nodesInRevision);
        // Different nodes can have the same path in different dimensions, and the variants of a node different paths
        $revisionVariants = [];
        $variantsByPath = [];
        $documentPaths = [];
        foreach ($nodesInRevision as $nodeData) {
            if ($nodeData['removed']) {
                continue;
            }
            $variantKey = $this->getVariantKey($nodeData);
            $dimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeData['dimensionValues']);
            $revisionVariants[$nodeData['identifier']][$variantKey] = ['path' => $nodeData['path'], 'dimensionsHash' => $dimensionsHash];
            $variantsByPath[$nodeData['path']][$variantKey] = $nodeData['identifier'];
            if ($nodeData['identifier'] === $documentIdentifier) {
                $documentPaths[$dimensionsHash] = $nodeData['path'];
            }
        }

        $rows = [];
        $errors = [];
        $movedAway = [];
        $nodeTypeNamesByPath = [];
        foreach ($nodesInRevision as $nodeData) {
            if ($nodeData['removed']) {
                continue;
            }
            $identifier = $nodeData['identifier'];
            $dimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeData['dimensionValues']);
            $context = $this->createContext($liveWorkspace, $nodeData['dimensionValues']);
            $existingNode = $context->getNodeByIdentifier($identifier);
            // A variant only visible through a fallback dimension does not exist yet, it is created where the revision has it
            if ($existingNode !== null && !$this->isVariantOfContext($existingNode)) {
                $existingNode = null;
            }
            $problem = $this->findNodeTypeProblem($nodeData, $existingNode, $context, $nodeTypeNamesByPath);
            if ($identifier === $documentIdentifier) {
                // The document itself is never resolved, only the content below it
                if ($problem !== null) {
                    $errors[] = $problem['message'];
                }
                // The revision is read below the document's current parent, so only a renamed document ends up elsewhere
                if ($existingNode !== null && $existingNode->getPath() !== $nodeData['path']) {
                    $errors[] = sprintf('The document node was renamed since the revision: it is at "%s", the revision has "%s"', $existingNode->getPath(), $nodeData['path']);
                }
                continue;
            }
            $relativePath = $this->getRelativePath($nodeData['path'], $documentPaths[$dimensionsHash] ?? $documentNode->getPath());
            if ($problem !== null) {
                $this->addRow($rows, $identifier, $existingNode !== null ? $existingNode->getLabel() : '', $nodeData['nodeType'], $relativePath, $nodeData['dimensionValues'], $problem, $nodeData['path']);
            }
            $closestDocument = $existingNode !== null ? $this->getClosestDocumentNode($existingNode) : null;
            if ($existingNode !== null && ($closestDocument === null || $closestDocument->getIdentifier() !== $documentIdentifier)) {
                $movedAway[$identifier]['variants'][$this->getVariantKey($nodeData)] = [$nodeData, $existingNode, $closestDocument, $relativePath];
            }
        }
        $identifiersWithNodeTypeProblems = array_fill_keys(array_keys($rows), true);

        foreach ($movedAway as $identifier => $moved) {
            foreach ($moved['variants'] as $variantKey => [, $existingNode]) {
                $subtree = [];
                $movedAway[$identifier]['hasUnresolvedNodeType'][$variantKey] = $this->collectLiveSubtree($existingNode, $subtree);
                $movedAway[$identifier]['subtrees'][$variantKey] = $subtree;
            }
        }

        $candidates = [];
        foreach ($movedAway as $identifier => $moved) {
            foreach ($moved['variants'] as $variantKey => [$nodeData, $existingNode, $closestDocument, $relativePath]) {
                // A variant still inside a variant of a node that was moved away with it follows that node
                if ($this->movesWithAncestor($variantKey, $nodeData['path'], $variantsByPath, $movedAway)) {
                    continue;
                }
                $movedAway[$identifier]['ownVariants'][$variantKey] = true;
                $this->addRow($rows, $identifier, $existingNode->getLabel(), $nodeData['nodeType'], $relativePath, $nodeData['dimensionValues'], [
                    'id' => 'movedAway',
                    'message' => $closestDocument !== null
                        ? sprintf('The content "%s" was moved to page "%s"', $existingNode->getLabel(), $closestDocument->getLabel())
                        : sprintf('The content "%s" was moved to an unknown page', $existingNode->getLabel()),
                ], $nodeData['path']);
                $rows[$identifier]['document'] = $closestDocument !== null ? ['identifier' => $closestDocument->getIdentifier(), 'label' => $closestDocument->getLabel()] : null;
                $this->collectContentMovedHere($existingNode, $variantsInRevision, $revisionDate, [$identifier], [], $identifier, $candidates);
            }
        }
        foreach ($this->getDocumentVariants($documentIdentifier, $liveWorkspace) as $documentVariant) {
            $this->collectContentMovedHere($documentVariant, $variantsInRevision, $revisionDate, [], [], null, $candidates);
        }

        $choices = [];
        foreach ($resolutions as $identifier => $resolution) {
            $choice = $this->parseResolution((string)$identifier, $resolution, $documentIdentifier, $revisionVariants, $candidates, $movedAway, $errors);
            if ($choice !== null) {
                $choices[(string)$identifier] = $choice;
            }
        }

        // A variant inside a skipped node stays where it is, so moving back is judged by the variants it would move
        $skippedPaths = $this->getSkippedPaths($revisionVariants, $choices);
        foreach ($movedAway as $identifier => $moved) {
            $movedVariants = [];
            foreach (array_keys($moved['ownVariants'] ?? []) as $variantKey) {
                $nodeData = $moved['variants'][$variantKey][0];
                if (!$this->isInsideSkippedNode($nodeData['path'], Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeData['dimensionValues']), $skippedPaths)) {
                    $movedVariants[$variantKey] = true;
                }
            }
            $movedAway[$identifier]['movedVariants'] = $movedVariants;
            $movedAway[$identifier]['cannotMoveBackBecause'] = $this->findMoveBackObstacle($identifier, $movedVariants, $moved, $identifiersWithNodeTypeProblems, $liveWorkspace);
        }
        foreach ($choices as $identifier => $choice) {
            if ($choice !== self::RESOLUTION_MOVE_BACK) {
                continue;
            }
            if ($movedAway[$identifier]['cannotMoveBackBecause'] !== null) {
                $errors[] = sprintf('Node "%s" cannot be moved back, because %s', $identifier, $movedAway[$identifier]['cannotMoveBackBecause']);
                unset($choices[$identifier]);
                continue;
            }
            // Skipping leaves live as it is, which content moving back along with this node would not be
            foreach (array_keys($movedAway[$identifier]['movedVariants']) as $movedVariant) {
                foreach (array_keys($movedAway[$identifier]['subtrees'][$movedVariant]) as $descendantVariant) {
                    $descendantIdentifier = $this->getIdentifierOfVariant($descendantVariant);
                    if (($choices[$descendantIdentifier] ?? null) === self::RESOLUTION_SKIP && !isset($candidates[$descendantIdentifier]['variants'][$descendantVariant])) {
                        $errors[] = sprintf('Node "%s" cannot be skipped, because it moves back along with node "%s"', $descendantIdentifier, $identifier);
                        unset($choices[$descendantIdentifier]);
                    }
                }
            }
        }

        $keptIdentifiers = array_fill_keys(array_keys(array_filter($choices, static function (string $choice): bool {
            return $choice === self::RESOLUTION_SKIP;
        })), true);
        $skippedPaths = $this->getSkippedPaths($revisionVariants, $choices);
        $variantsToMoveBack = [];
        foreach ($choices as $identifier => $choice) {
            if ($choice === self::RESOLUTION_MOVE_BACK) {
                $variantsToMoveBack += $movedAway[$identifier]['movedVariants'];
            }
        }
        foreach ($rows as $identifier => $row) {
            foreach ($row['variants'] as $variantKey => $variant) {
                if ($this->isInsideSkippedNode((string)$variant['revisionPath'], $variant['dimensionsHash'], $skippedPaths)) {
                    unset($rows[$identifier]['variants'][$variantKey]);
                }
            }
            if ($rows[$identifier]['variants'] === []) {
                unset($rows[$identifier]);
            }
        }

        $protectedVariants = [];
        foreach ($candidates as $identifier => $candidate) {
            foreach ($candidate['variants'] as $candidateVariant) {
                $isComingHere = $candidateVariant['movedWith'] === null || ($choices[$candidateVariant['movedWith']] ?? null) === self::RESOLUTION_MOVE_BACK;
                if (!$isComingHere || array_intersect_key(array_flip($candidateVariant['ancestors']), $keptIdentifiers) !== []) {
                    continue;
                }
                $variant = $candidateVariant['node'];
                $closestDocument = $this->getClosestDocumentNode($variant);
                $this->addRow($rows, $identifier, $variant->getLabel(), $variant->getNodeType()->getName(), $this->getRelativePath($variant->getPath(), $closestDocument !== null ? $closestDocument->getPath() : ''), $variant->getDimensions(), [
                    'id' => 'movedHere',
                    'message' => sprintf('The content "%s" was moved to the page after the revision was created', $variant->getLabel()),
                ], null);
                // Content created after the revision is removed, unless it holds content that is kept
                if (($choices[$identifier] ?? null) === self::RESOLUTION_SKIP) {
                    $protectedVariants += array_fill_keys($candidateVariant['youngAncestors'], true);
                }
            }
        }

        $isApplicable = $errors === [];
        foreach ($rows as $identifier => $row) {
            $canMoveBack = isset($movedAway[$identifier]) && $movedAway[$identifier]['cannotMoveBackBecause'] === null;
            $rows[$identifier] = $this->finishRow($row, $canMoveBack, $choices[$identifier] ?? null);
            if ($rows[$identifier]['resolution'] === null) {
                $isApplicable = false;
            }
        }
        $nodesToStage = [];
        foreach ($nodesInRevision as $nodeData) {
            if (!$nodeData['removed'] && !isset($keptIdentifiers[$nodeData['identifier']])
                && !$this->isInsideSkippedNode($nodeData['path'], Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeData['dimensionValues']), $skippedPaths)) {
                $nodesToStage[] = $nodeData;
            }
        }

        return [
            'rows' => array_values($rows),
            // Every variant of the document reports the same problem
            'errors' => array_values(array_unique($errors)),
            'isApplicable' => $isApplicable,
            'nodesToStage' => $nodesToStage,
            'keptIdentifiers' => $keptIdentifiers,
            'protectedVariants' => $protectedVariants,
            'variantsToMoveBack' => $variantsToMoveBack,
        ];
    }

    /**
     * @param array<string, array> $revisionVariants
     * @param array<string, string> $choices
     * @return array<string, array<string>> The paths of the skipped nodes with a trailing slash, by dimensions hash
     */
    protected function getSkippedPaths(array $revisionVariants, array $choices): array
    {
        $skippedPaths = [];
        foreach ($choices as $identifier => $choice) {
            if ($choice !== self::RESOLUTION_SKIP || !isset($revisionVariants[$identifier])) {
                continue;
            }
            foreach ($revisionVariants[$identifier] as $variant) {
                $skippedPaths[$variant['dimensionsHash']][] = $variant['path'] . '/';
            }
        }
        return $skippedPaths;
    }

    /**
     * Returns why the variants cannot be moved back to their place in the revision, or null
     *
     * @param array<string, bool> $movedVariants The variants of the node that moving it back would move
     * @param array<string, mixed> $moved What resolveRevision() found about the node moved away
     * @param array<string, bool> $identifiersWithNodeTypeProblems
     */
    protected function findMoveBackObstacle(string $identifier, array $movedVariants, array $moved, array $identifiersWithNodeTypeProblems, Workspace $liveWorkspace): ?string
    {
        $subtree = [];
        $paths = [];
        $hasUnresolvedNodeType = false;
        foreach (array_keys($movedVariants) as $variantKey) {
            $subtree += $moved['subtrees'][$variantKey];
            $paths[] = $moved['variants'][$variantKey][0]['path'];
            $hasUnresolvedNodeType = $hasUnresolvedNodeType || $moved['hasUnresolvedNodeType'][$variantKey];
        }
        // Moving rewrites the node data inside, which replaces a node type that no longer exists with the fallback node
        // type, and content inside can no longer be skipped
        if ($hasUnresolvedNodeType || isset($identifiersWithNodeTypeProblems[$identifier]) || array_intersect_key($this->getIdentifiersOfVariants($subtree), $identifiersWithNodeTypeProblems) !== []) {
            return 'its node type or one inside it no longer fits';
        }
        if ($this->isPlaceTakenByAnotherNode($paths, $identifier, $liveWorkspace)) {
            return 'another node has its place now';
        }
        return null;
    }

    /**
     * Returns why the node variant cannot be staged with the node type of the revision: the type does not exist or is
     * abstract, or the node would be created, moved back or retyped under a parent that does not exist or allow it
     *
     * @param array<string, string> $nodeTypeNamesByPath Node types of the revision, which override live, the node's is added
     * @return array{id: string, message: string}|null
     */
    protected function findNodeTypeProblem(array $nodeData, ?NodeInterface $existingNode, ContentContext $context, array &$nodeTypeNamesByPath): ?array
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeData['nodeType'])) {
            return ['id' => 'nodeTypeMissing', 'message' => sprintf('Node type "%s" of node "%s" does not exist', $nodeData['nodeType'], $nodeData['path'])];
        }
        $nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodeType']);
        // Neither createNode() nor setNodeType() refuses an abstract node type
        if ($nodeType->isAbstract()) {
            return ['id' => 'nodeTypeMissing', 'message' => sprintf('Node type "%s" of node "%s" is abstract', $nodeData['nodeType'], $nodeData['path'])];
        }
        $nodeTypeNamesByPath[(string)$nodeData['path']] = (string)$nodeData['nodeType'];
        if ($existingNode !== null && $existingNode->getPath() === $nodeData['path'] && $existingNode->getNodeType()->getName() === $nodeData['nodeType']) {
            return null;
        }

        // The node would be created, moved back or retyped, so its parent must allow it
        $nodeName = NodePaths::getNodeNameFromPath($nodeData['path']);
        $parentNodeType = $this->resolveNodeType($nodeData['parentPath'], $context, $nodeTypeNamesByPath);
        if ($parentNodeType === null) {
            return ['id' => 'parentMissing', 'message' => sprintf('Parent "%s" of node "%s" does not exist', $nodeData['parentPath'], $nodeData['path'])];
        }
        if (isset($parentNodeType->getAutoCreatedChildNodes()[$nodeName])) {
            return null;
        }
        $parentName = NodePaths::getNodeNameFromPath($nodeData['parentPath']);
        $grandParentNodeType = $this->resolveNodeType(NodePaths::getParentPath($nodeData['parentPath']), $context, $nodeTypeNamesByPath);
        $isAllowed = $grandParentNodeType !== null && isset($grandParentNodeType->getAutoCreatedChildNodes()[$parentName])
            ? $grandParentNodeType->allowsGrandchildNodeType($parentName, $nodeType)
            : $parentNodeType->allowsChildNodeType($nodeType);
        if (!$isAllowed) {
            return ['id' => 'nodeTypeNotAllowed', 'message' => sprintf('Node "%s" of type "%s" is not allowed in "%s"', $nodeData['path'], $nodeData['nodeType'], $nodeData['parentPath'])];
        }
        return null;
    }

    /**
     * Returns the resolution a node gets, or null after adding to the errors why it cannot get it
     *
     * @param mixed $resolution
     * @param array<string, array> $revisionVariants The variants of the revision's nodes by identifier
     * @param array<string, array> $candidates Content moved to the page since the revision
     * @param array<string, array> $movedAway Content moved to another page since the revision
     * @param array<string> $errors
     */
    protected function parseResolution(string $identifier, $resolution, string $documentIdentifier, array $revisionVariants, array $candidates, array $movedAway, array &$errors): ?string
    {
        if ($identifier === $documentIdentifier) {
            $errors[] = sprintf('Node "%s" is the document of the revision, which cannot be resolved', $identifier);
            return null;
        }
        if (!isset($revisionVariants[$identifier]) && !isset($candidates[$identifier])) {
            $errors[] = sprintf('Node "%s" is neither in the revision nor content moved to its page', $identifier);
            return null;
        }
        $supportedKeys = [self::RESOLUTION_SKIP, self::RESOLUTION_MOVE_BACK, self::RESOLUTION_REMOVE];
        $unsupportedKeys = is_array($resolution) ? array_diff(array_keys($resolution), $supportedKeys) : [];
        if ($unsupportedKeys !== []) {
            $errors[] = sprintf('Resolution "%s" for node "%s" is not supported', implode('", "', $unsupportedKeys), $identifier);
            return null;
        }
        $chosenKeys = is_array($resolution) ? array_keys($resolution, true, true) : [];
        if (count($chosenKeys) !== 1) {
            $errors[] = sprintf('Node "%s" needs exactly one of the resolutions "%s" set to true', $identifier, implode('", "', $supportedKeys));
            return null;
        }
        $choice = (string)$chosenKeys[0];
        if ($choice === self::RESOLUTION_MOVE_BACK && !isset($movedAway[$identifier]['ownVariants'])) {
            $errors[] = isset($movedAway[$identifier])
                ? sprintf('Node "%s" moves back along with the node that contains it, not on its own', $identifier)
                : sprintf('Node "%s" was not moved to another page, so it cannot be moved back', $identifier);
            return null;
        }
        if ($choice === self::RESOLUTION_REMOVE && !isset($candidates[$identifier])) {
            $errors[] = sprintf('Node "%s" was not moved to the page, so it cannot be removed', $identifier);
            return null;
        }
        return $choice;
    }

    /**
     * @param array<string, array> $rows
     * @param array<string, array<string>> $dimensions
     * @param array{id: string, message: string} $problem
     * @param string|null $revisionPath The variant's path in the revision, which leaves the problem out inside a skipped node
     */
    protected function addRow(array &$rows, string $identifier, string $label, string $nodeTypeName, string $path, array $dimensions, array $problem, ?string $revisionPath): void
    {
        if (!isset($rows[$identifier])) {
            $nodeTypeLabel = $this->nodeTypeManager->hasNodeType($nodeTypeName) ? $this->translate($this->nodeTypeManager->getNodeType($nodeTypeName)->getLabel()) : '';
            $rows[$identifier] = [
                'identifier' => $identifier,
                'label' => $label !== '' ? $label : ($nodeTypeLabel ?: $nodeTypeName),
                'nodeType' => ['name' => $nodeTypeName, 'label' => $nodeTypeLabel ?: $nodeTypeName],
                'path' => $path,
                'document' => null,
                'variants' => [],
            ];
        }
        $dimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($dimensions);
        $rows[$identifier]['variants'][$dimensionsHash]['dimensions'] = $dimensions;
        $rows[$identifier]['variants'][$dimensionsHash]['dimensionsHash'] = $dimensionsHash;
        $rows[$identifier]['variants'][$dimensionsHash]['revisionPath'] = $revisionPath;
        $rows[$identifier]['variants'][$dimensionsHash]['problems'][$problem['id']] = $problem;
    }

    /**
     * Merges the problems of the row's variants and adds the resolutions they allow, skipping first
     */
    protected function finishRow(array $row, bool $canMoveBack, ?string $choice): array
    {
        $dimensions = [];
        $problems = [];
        foreach ($row['variants'] as $variant) {
            $dimensions[] = $variant['dimensions'];
            $problems += $variant['problems'];
        }
        $choices = [self::RESOLUTION_SKIP];
        // A node whose variants need different resolutions can only be left as it is
        if (isset($problems['movedAway']) && !isset($problems['movedHere']) && $canMoveBack) {
            $choices[] = self::RESOLUTION_MOVE_BACK;
        }
        if (isset($problems['movedHere']) && count($problems) === 1) {
            $choices[] = self::RESOLUTION_REMOVE;
        }
        unset($row['variants']);
        return $row + [
            'dimensions' => $dimensions,
            'problems' => array_values($problems),
            'choices' => $choices,
            'resolution' => in_array($choice, $choices, true) ? $choice : null,
        ];
    }

    /**
     * Collects the node variants inside the node, which a move takes along, and tells whether one of their node types no
     * longer resolves
     *
     * @param array<string, bool> $variants By identifier and dimensions hash
     */
    protected function collectLiveSubtree(NodeInterface $node, array &$variants): bool
    {
        $hasUnresolvedNodeType = false;
        foreach ($node->getChildNodes() as $childNode) {
            $variants[$childNode->getIdentifier() . '@' . $childNode->getNodeData()->getDimensionsHash()] = true;
            $childNodeType = $childNode->getNodeType();
            if ($childNodeType->getName() === $this->fallbackNodeTypeName || $childNodeType->isAbstract()) {
                $hasUnresolvedNodeType = true;
            }
            if ($this->collectLiveSubtree($childNode, $variants)) {
                $hasUnresolvedNodeType = true;
            }
        }
        return $hasUnresolvedNodeType;
    }

    /**
     * Finds the content variants inside the node that removeChildNodesMissingInRevision() would remove although they
     * already existed when the revision was created, so they were moved here since. Creation dates survive moves and
     * publishes. Content created after the revision is removed, but content moved into it since can be older.
     *
     * @param array<string, bool> $variantsInRevision
     * @param array<string> $ancestorIdentifiers
     * @param array<string> $youngAncestorVariants The variants created after the revision that the content is inside
     * @param string|null $movedWith The node moved away since the revision that the content would come back with
     * @param array<string, array> $candidates By identifier, with the variants by identifier and dimensions hash
     */
    protected function collectContentMovedHere(NodeInterface $parentNode, array $variantsInRevision, \DateTimeInterface $revisionDate, array $ancestorIdentifiers, array $youngAncestorVariants, ?string $movedWith, array &$candidates): void
    {
        foreach ($parentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
            $identifier = $childNode->getIdentifier();
            $variantKey = $identifier . '@' . $childNode->getNodeData()->getDimensionsHash();
            $isRemovable = $this->isVariantOfContext($childNode) && !$childNode->isAutoCreated();
            $ancestors = array_merge($ancestorIdentifiers, [$identifier]);
            if (!$isRemovable || isset($variantsInRevision[$variantKey])) {
                $this->collectContentMovedHere($childNode, $variantsInRevision, $revisionDate, $ancestors, $youngAncestorVariants, $movedWith, $candidates);
                continue;
            }
            if ($childNode->getNodeData()->getCreationDateTime() < $revisionDate) {
                $candidates[$identifier]['variants'][$variantKey] = [
                    'node' => $childNode,
                    'ancestors' => $ancestorIdentifiers,
                    'youngAncestors' => $youngAncestorVariants,
                    'movedWith' => $movedWith,
                ];
                continue;
            }
            $this->collectContentMovedHere($childNode, $variantsInRevision, $revisionDate, $ancestors, array_merge($youngAncestorVariants, [$variantKey]), $movedWith, $candidates);
        }
    }

    /**
     * Tells whether a variant of content moved away is still inside a variant of a node of the revision that was moved
     * away with it
     *
     * @param string $path The path of the variant in the revision
     * @param array<string, array<string, string>> $variantsByPath The identifiers of the revision's variants at each path
     * @param array<string, array> $movedAway
     */
    protected function movesWithAncestor(string $variantKey, string $path, array $variantsByPath, array $movedAway): bool
    {
        for ($ancestorPath = NodePaths::getParentPath($path); $ancestorPath !== '/' && $ancestorPath !== ''; $ancestorPath = NodePaths::getParentPath($ancestorPath)) {
            // Only the variants at this path count, and their subtrees hold variants, so a node or a variant elsewhere
            // in another dimension does not match
            foreach ($variantsByPath[$ancestorPath] ?? [] as $ancestorVariantKey => $ancestorIdentifier) {
                if (isset($movedAway[$ancestorIdentifier]['subtrees'][$ancestorVariantKey][$variantKey])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Neos moves a node only to a path that no other node has, in any dimension
     *
     * @param array<string> $paths
     */
    protected function isPlaceTakenByAnotherNode(array $paths, string $identifier, Workspace $workspace): bool
    {
        foreach ($paths as $path) {
            foreach ($this->nodeDataRepository->findByPathWithoutReduce($path, $workspace) as $nodeData) {
                if ($nodeData->getIdentifier() !== $identifier) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $nodeData
     * @return string Identifier and dimensions hash of the node variant
     */
    protected function getVariantKey(array $nodeData): string
    {
        return $nodeData['identifier'] . '@' . Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeData['dimensionValues']);
    }

    protected function getIdentifierOfVariant(string $variantKey): string
    {
        return substr($variantKey, 0, (int)strrpos($variantKey, '@'));
    }

    /**
     * @param array<string, bool> $variants By identifier and dimensions hash
     * @return array<string, bool>
     */
    protected function getIdentifiersOfVariants(array $variants): array
    {
        $identifiers = [];
        foreach (array_keys($variants) as $variantKey) {
            $identifiers[$this->getIdentifierOfVariant((string)$variantKey)] = true;
        }
        return $identifiers;
    }

    /**
     * @param array<string, array<string>> $skippedPaths By dimensions hash, each path with a trailing slash
     */
    protected function isInsideSkippedNode(string $path, string $dimensionsHash, array $skippedPaths): bool
    {
        foreach ($skippedPaths[$dimensionsHash] ?? [] as $skippedPath) {
            if (strpos($path, $skippedPath) === 0) {
                return true;
            }
        }
        return false;
    }

    protected function getRelativePath(string $path, string $basePath): string
    {
        return strpos($path, $basePath . '/') === 0 ? substr($path, strlen($basePath) + 1) : $path;
    }

    /**
     * @param array<string, string> $nodeTypeNamesByPath Node types of the revision, which override live
     */
    protected function resolveNodeType(string $path, ContentContext $context, array $nodeTypeNamesByPath): ?NodeType
    {
        if (isset($nodeTypeNamesByPath[$path])) {
            return $this->nodeTypeManager->getNodeType($nodeTypeNamesByPath[$path]);
        }
        $node = $path !== '' ? $context->getNode($path) : null;
        return $node !== null ? $node->getNodeType() : null;
    }

    /**
     * Signals that a revision is about to be staged and published
     *
     * @Flow\Signal
     */
    protected function emitRevisionApplying(NodeInterface $documentNode, Revision $revision): void
    {
    }

    /**
     * Signals that a revision was published
     *
     * @Flow\Signal
     * @param array<NodeInterface> $stagedVariants The node variants that differed from live and were published
     */
    protected function emitRevisionApplied(NodeInterface $documentNode, Revision $revision, array $stagedVariants): void
    {
    }

    /**
     * Writes all node variants of the revision into the workspace, creating and moving nodes as needed
     *
     * @param array<array> $nodesInRevision Node data as read from the revision, parents before their children
     * @param array<string, bool> $variantsToMoveBack The variants resolveRevision() allows to be taken from other pages
     * @throws RevisionNotApplicableException if content was moved to another page after resolveRevision() looked at it
     */
    protected function stageNodes(array $nodesInRevision, Workspace $workspace, string $documentIdentifier, array $variantsToMoveBack): void
    {
        foreach ($nodesInRevision as $nodeData) {
            if ($nodeData['removed']) {
                continue;
            }

            $context = $this->createContext($workspace, $nodeData['dimensionValues']);
            $nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodeType']);
            $nodeName = NodePaths::getNodeNameFromPath($nodeData['path']);

            $node = $context->getNodeByIdentifier($nodeData['identifier']);
            if ($node === null) {
                $node = $this->getParentNode($context, $nodeData)->createNode($nodeName, $nodeType, $nodeData['identifier']);
            } elseif (!$this->isVariantOfContext($node)) {
                // Only visible through a fallback dimension. createVariantForContext() turns the node already known to the
                // context into the new variant, adoptNode() would also trigger translation integrations. The variant is
                // created at the fallback's place, so its node data is put where the revision has it: moving the node
                // would take the fallback's child nodes along.
                $node = $node->createVariantForContext($context);
                $node->getNodeData()->setPath($nodeData['path']);
                $context->getFirstLevelNodeCache()->flush();
            }

            // Only differences are written, so unchanged variants are neither published nor reindexed, and node
            // privileges are only checked for variants that change
            if ($node->getNodeType()->getName() !== $nodeType->getName()) {
                $node->setNodeType($nodeType);
            }
            foreach ($node->getNodeData()->getProperties() as $propertyName => $propertyValue) {
                if ($propertyValue !== null && !array_key_exists($propertyName, $nodeData['properties'])) {
                    $node->removeProperty($propertyName);
                }
            }
            foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
                // setProperty() materializes the node even if the value does not change
                if ($this->normalizePropertyValue($node->getProperty($propertyName)) !== $this->normalizePropertyValue($propertyValue)) {
                    $node->setProperty($propertyName, $propertyValue);
                }
            }
            if ($node->isHidden() !== $nodeData['hidden']) {
                $node->setHidden($nodeData['hidden']);
            }
            if ($node->isHiddenInIndex() !== $nodeData['hiddenInIndex']) {
                $node->setHiddenInIndex($nodeData['hiddenInIndex']);
            }
            // The setters only skip unchanged \DateTime values, the import creates \DateTimeImmutable
            if ($node->getHiddenBeforeDateTime() != ($nodeData['hiddenBeforeDateTime'] ?? null)) {
                $node->setHiddenBeforeDateTime($nodeData['hiddenBeforeDateTime'] ?? null);
            }
            if ($node->getHiddenAfterDateTime() != ($nodeData['hiddenAfterDateTime'] ?? null)) {
                $node->setHiddenAfterDateTime($nodeData['hiddenAfterDateTime'] ?? null);
            }
            if ($node->getAccessRoles() !== $nodeData['accessRoles']) {
                $node->setAccessRoles($nodeData['accessRoles']);
            }

            // Content moved within the page since the revision is moved back, content on another page only with
            // RESOLUTION_MOVE_BACK
            if ($node->getPath() !== $nodeData['path']) {
                // resolveRevision() checked that the document is where the revision puts it
                if ($nodeData['identifier'] === $documentIdentifier) {
                    throw new RevisionNotApplicableException([], [sprintf('The document was moved to "%s" after the revision was checked, apply the revision again', $node->getPath())]);
                }
                $closestDocument = $this->getClosestDocumentNode($node);
                $isOnThePage = $closestDocument !== null && $closestDocument->getIdentifier() === $documentIdentifier;
                if (!$isOnThePage && !isset($variantsToMoveBack[$this->getVariantKey($nodeData)])) {
                    throw new RevisionNotApplicableException([], [sprintf('Node "%s" was moved to another page after the revision was checked, apply the revision again', $nodeData['identifier'])]);
                }
                $node->moveInto($this->getParentNode($context, $nodeData), $nodeName);
            }
            $node->setIndex((int)$nodeData['sortingIndex']);
        }
    }

    /**
     * Removes the content node variants of the document that were not part of the revision, except the kept nodes and
     * everything inside them, and the protected variants, whose content is checked like the rest
     *
     * @param array<array> $nodesInRevision
     * @param array<string, bool> $keptIdentifiers
     * @param array<string, bool> $protectedVariants By identifier and dimensions hash
     */
    protected function removeNodesMissingInRevision(NodeInterface $documentNode, array $nodesInRevision, Workspace $workspace, array $keptIdentifiers, array $protectedVariants): void
    {
        $variantsInRevision = $this->getVariantsInRevision($nodesInRevision);
        foreach ($this->getDocumentVariants($documentNode->getIdentifier(), $workspace) as $documentVariant) {
            $this->removeChildNodesMissingInRevision($documentVariant, $variantsInRevision, $keptIdentifiers, $protectedVariants);
        }
    }

    /**
     * @param array<array> $nodesInRevision
     * @return array<string, bool> Identifier and dimensions hash of every node variant in the revision
     */
    protected function getVariantsInRevision(array $nodesInRevision): array
    {
        $variantsInRevision = [];
        foreach ($nodesInRevision as $nodeData) {
            $variantsInRevision[$this->getVariantKey($nodeData)] = true;
        }
        return $variantsInRevision;
    }

    /**
     * @return array<NodeInterface> The variants of the document in all allowed dimension combinations
     */
    protected function getDocumentVariants(string $documentIdentifier, Workspace $workspace): array
    {
        $documentVariants = [];
        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {
            $targetDimensionValues = array_map(static function (array $values) {
                return [reset($values)];
            }, $dimensionCombination);
            $documentVariant = $this->createContext($workspace, $targetDimensionValues)->getNodeByIdentifier($documentIdentifier);
            if ($documentVariant !== null) {
                $documentVariants[] = $documentVariant;
            }
        }
        return $documentVariants;
    }

    /**
     * @param array<string, bool> $variantsInRevision
     * @param array<string, bool> $keptIdentifiers
     * @param array<string, bool> $protectedVariants
     */
    protected function removeChildNodesMissingInRevision(NodeInterface $parentNode, array $variantsInRevision, array $keptIdentifiers, array $protectedVariants): void
    {
        foreach ($parentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
            if (isset($keptIdentifiers[$childNode->getIdentifier()])) {
                continue;
            }
            $variantKey = $childNode->getIdentifier() . '@' . $childNode->getNodeData()->getDimensionsHash();
            // Fallback variants are handled in the context of their own dimensions, tethered nodes belong to the node type
            $isRemovable = $this->isVariantOfContext($childNode) && !$childNode->isAutoCreated();
            if ($isRemovable && !isset($variantsInRevision[$variantKey]) && !isset($protectedVariants[$variantKey])) {
                $childNode->remove();
                continue;
            }
            $this->removeChildNodesMissingInRevision($childNode, $variantsInRevision, $keptIdentifiers, $protectedVariants);
        }
    }

    protected function isVariantOfContext(NodeInterface $node): bool
    {
        $targetDimensionValues = $node->getContext()->getTargetDimensionValues();
        return $node->getNodeData()->getDimensionsHash() === Utility::sortDimensionValueArrayAndReturnDimensionsHash($targetDimensionValues);
    }

    /**
     * Makes property values comparable: dates by instant, nodes and persistent objects by identifier
     *
     * @param mixed $value
     * @return mixed
     */
    protected function normalizePropertyValue($value)
    {
        if (is_array($value)) {
            return array_map(function ($item) {
                return $this->normalizePropertyValue($item);
            }, $value);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }
        if ($value instanceof NodeInterface) {
            return $value->getIdentifier();
        }
        if (is_object($value)) {
            return $this->persistenceManager->getIdentifierByObject($value) ?? $value;
        }
        return $value;
    }

    protected function getParentNode(ContentContext $context, array $nodeData): NodeInterface
    {
        $parentNode = $context->getNode($nodeData['parentPath']);
        if ($parentNode === null) {
            throw new \RuntimeException(sprintf('Cannot restore node "%s" because its parent "%s" does not exist', $nodeData['path'], $nodeData['parentPath']), 1790354984);
        }
        return $parentNode;
    }

    /**
     * Creates a context for the given dimension values, falling back like the matching dimension preset
     *
     * @param array<string, array<string>> $dimensionValues
     */
    protected function createContext(Workspace $workspace, array $dimensionValues): ContentContext
    {
        $presets = $this->contentDimensionPresetSource->getAllPresets();
        $dimensions = [];
        $targetDimensions = [];
        foreach ($dimensionValues as $dimensionName => $values) {
            $targetValue = reset($values);
            $targetDimensions[$dimensionName] = $targetValue;
            $dimensions[$dimensionName] = [$targetValue];
            foreach ($presets[$dimensionName]['presets'] ?? [] as $preset) {
                if (($preset['values'][0] ?? null) === $targetValue) {
                    $dimensions[$dimensionName] = $preset['values'];
                    break;
                }
            }
        }

        return $this->contextFactory->create([
            'workspaceName' => $workspace->getName(),
            'dimensions' => $dimensions,
            'targetDimensions' => $targetDimensions,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
        ]);
    }

    protected function createTemporaryWorkspace(Workspace $baseWorkspace): Workspace
    {
        $workspace = new Workspace('revision-' . Algorithms::generateUUID(), $baseWorkspace);
        $workspace->setTitle('NEOSidekick Revisions');
        $this->workspaceRepository->add($workspace);
        $this->persistenceManager->persistAll();
        return $workspace;
    }

    protected function removeTemporaryWorkspace(Workspace $workspace): void
    {
        try {
            $this->securityContext->withoutAuthorizationChecks(function () use ($workspace) {
                // Nodes are only left over if applying failed. Unflushed ones would escape discarding and end up
                // without a workspace once it is deleted.
                $this->persistenceManager->persistAll();
                $this->publishingService->discardAllNodes($workspace);
                $rootNodeData = $workspace->getRootNodeData();
                $this->workspaceRepository->remove($workspace);
                $this->persistenceManager->persistAll();
                // Deleting a workspace only unsets the workspace of its root node data
                $this->nodeDataRepository->remove($rootNodeData);
                $this->persistenceManager->persistAll();
            });
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to remove temporary workspace %s: %s', $workspace->getName(), $throwable->getMessage()));
        }
    }

    /**
     * @return array<string, array> List of changes by node identifier
     * @throws IllegalObjectTypeException|NodeTypeNotFoundException
     */
    public function compareRevision(Revision $revision, string $parentPath, AbstractRenderer $renderer): array
    {
        $revisionContent = $revision->getContent();
        if (!$revisionContent) {
            $this->logger->warning(sprintf('Could not find any content in revision %s', $revision->getIdentifier()));
            return [];
        }

        $context = $this->contextFactory->create([
            'invisibleContentShown' => true
        ]);
        $revisionRootNode = $context->getNodeByIdentifier($revision->getNodeIdentifier());

        if (!$revisionRootNode) {
            $this->logger->warning(sprintf('Could not find any rootnode of revision %s', $revision->getIdentifier()));
            return [];
        }

        $nodesInImport = $this->nodeImportService->getNodesInImport($revisionContent, $parentPath);
        $existingNodesInTargetPath = $this->nodeService->findContentNodes($revisionRootNode->getPath(), $context->getWorkspace());

        $changesByNode = [];
        foreach ($nodesInImport as $nodeDataInImport) {
            // Removed node data in a revision is neither live content nor restored by applying it
            if ($nodeDataInImport['removed']) {
                continue;
            }
            $importedNodeIdentifier = $nodeDataInImport['identifier'];
            $dimensionHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeDataInImport['dimensionValues']);

            $existingNode = $this->getExistingNode($context, $importedNodeIdentifier, $dimensionHash);

            if (!$existingNode) {
                $importedNodeTypeName = $nodeDataInImport['nodeType'];
                $importedNodeType = $this->nodeTypeManager->getNodeType($importedNodeTypeName);

                $changesByNode[$importedNodeIdentifier][$dimensionHash] = [
                    'type' => 'addNode',
                    'node' => [
                        'identifier' => $importedNodeIdentifier,
                        'label' => $this->translate($importedNodeType->getLabel()),
                        'lastModificationDateTime' => $nodeDataInImport['lastModificationDateTime'],
                        'dimensions' => $nodeDataInImport['dimensionValues'] ?? [],
                        'nodeType' => [
                            'name' => $importedNodeType->getName(),
                            'label' => $this->translate($importedNodeType->getLabel()),
                            'icon' => $importedNodeType->getConfiguration('ui.icon') ?? 'question',
                        ],
                    ],
                    // TODO: Generate diff. This is currently not easily possible as we need a NodeData object instead of an array.
                ];
                continue;
            }

            // Filter existing nodes that are part of the import
            $existingNodesInTargetPath = array_filter($existingNodesInTargetPath, static function ($existingNode) use ($importedNodeIdentifier) {
                return $existingNode->getIdentifier() !== $importedNodeIdentifier;
            });

            $changes = $this->generateNodeDiff(
                $existingNode,
                $nodeDataInImport,
                $renderer
            );

            // Skip empty changes
            $existingNodeTimestamp = $existingNode->getLastModificationDateTime()->getTimestamp();
            $importedNodeTimestamp = $nodeDataInImport['lastModificationDateTime']->getTimestamp();
            $isChanged = $existingNodeTimestamp !== $importedNodeTimestamp || $changes['type'] !== 'changeNode' || !empty($changes['changes']);

            if ($isChanged) {
                $changesByNode[$importedNodeIdentifier][$dimensionHash] = $changes;
            }
        }

        // Create diff for nodes that are not part of the import
        foreach ($existingNodesInTargetPath as $existingNodeData) {
            $identifier = $existingNodeData->getIdentifier();
            $dimensionHash = $existingNodeData->getDimensionsHash();
            $existingNode = $context->getNodeByIdentifier($identifier);
            $changesByNode[$identifier][$dimensionHash] = $this->generateNodeDiff(
                $existingNode,
                null,
                $renderer
            );
        }

        return $changesByNode;
    }

    protected function translate(string $id): string
    {
        if (!$this->translationHelper) {
            $this->translationHelper = new TranslationHelper();
        }
        return $this->translationHelper->translate($id) ?? $id;
    }

    /**
     * Adapted from \Neos\Neos\Controller\Module\Management\WorkspacesController
     */
    protected function getPropertyLabel(string $propertyName, NodeType $nodeType): string
    {
        $properties = $nodeType->getProperties();
        if (isset($properties[$propertyName]['ui']['label'])) {
            return $this->translate($properties[$propertyName]['ui']['label']);
        }
        return $propertyName;
    }

    /**
     * Adapted from \Neos\Neos\Controller\Module\Management\WorkspacesController
     */
    protected function renderSlimmedDownContent($propertyValue): string
    {
        if (is_string($propertyValue)) {
            $contentSnippet = preg_replace('/<br[^>]*>/', "\n", $propertyValue);
            $contentSnippet = preg_replace('/<[^>]*>/', ' ', $contentSnippet);
            $contentSnippet = str_replace('&nbsp;', ' ', $contentSnippet);
            return trim(preg_replace('/ {2,}/', ' ', $contentSnippet));
        }
        return '';
    }

    /**
     * Copied from \Neos\Neos\Controller\Module\Management\WorkspacesController
     */
    protected function postProcessDiffArray(array &$diffArray): void
    {
        foreach ($diffArray as $index => $blocks) {
            foreach ($blocks as $blockIndex => $block) {
                $baseLines = trim(implode('', $block['base']['lines']), " \t\n\r\0\xC2\xA0");
                $changedLines = trim(implode('', $block['changed']['lines']), " \t\n\r\0\xC2\xA0");
                if ($baseLines === '') {
                    foreach ($block['changed']['lines'] as $lineIndex => $line) {
                        $diffArray[$index][$blockIndex]['changed']['lines'][$lineIndex] = '<ins>' . $line . '</ins>';
                    }
                }
                if ($changedLines === '') {
                    foreach ($block['base']['lines'] as $lineIndex => $line) {
                        $diffArray[$index][$blockIndex]['base']['lines'][$lineIndex] = '<del>' . $line . '</del>';
                    }
                }
            }
        }
    }

    protected function getClosestDocumentNode(NodeInterface $node): ?NodeInterface
    {
        $parentNode = $node;
        while ($parentNode && !$parentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
            $parentNode = $parentNode->getParent();
        }
        return $parentNode;
    }

    public function registerNodeChange(NodeInterface $node, Workspace $targetWorkspace = null): void
    {
        // We only create revisions when nodes are published to the live workspace
        if (!$targetWorkspace || $targetWorkspace->getName() !== 'live') {
            return;
        }

        // Find the closest document node and store it.
        // For each document node a revision will be created at the end of the request to prevent duplicates.
        $documentNode = $node;
        while ($documentNode && !$documentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
            $documentNode = $documentNode->getParent();
        }

        if ($documentNode && $documentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
            self::$nodesForRevisions[$documentNode->getIdentifier()] = $documentNode;
        }
    }

    public function registerNodeBeforePublishing(NodeInterface $node, Workspace $targetWorkspace): void {
        if ($targetWorkspace->getName() !== 'live' || !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return;
        }
        $nodeInLiveWorkspace = $this->nodeDataRepository->findOneByIdentifier($node->getIdentifier(), $targetWorkspace);
        if (!$nodeInLiveWorkspace) {
            // TODO: Check here whether the node was removed and remove its revisions during shutdown
            return;
        }
        if ($node->getPath() !== $nodeInLiveWorkspace->getPath()) {
            self::$movedNodes[$node->getIdentifier()] = true;
            $this->logger->debug(sprintf('Node "%s" will be moved from "%s" to "%s"', $node->getIdentifier(), $nodeInLiveWorkspace->getPath(), $node->getPath()));
        }
    }

    public function deleteRevision(string $getIdentifier): void
    {
        $revision = $this->getRevision($getIdentifier);
        if (!$revision) {
            return;
        }
        $this->revisionRepository->remove($revision);
    }

    /**
     * Create revisions for all registered document nodes with changes
     */
    public function shutdownObject(): void
    {
        if (empty(self::$nodesForRevisions)) {
            return;
        }

        foreach (self::$nodesForRevisions as $identifier => $node) {
            // Make sure we have fresh nodedata from CR
            $node->getContext()->getFirstLevelNodeCache()->flush();
            $nodeToUse = $node->getContext()->getNodeByIdentifier($identifier);

            if ($nodeToUse) {
                if ($nodeToUse->isRemoved()) {
                    $this->logger->info(sprintf('Removing revisions for deleted node %s', $nodeToUse->getContextPath()));
                    // TODO: Remove revisions
                } else {
                    $revision = $this->createRevision($nodeToUse);
                    if ($revision !== null && isset(self::$appliedRevisions[$identifier])) {
                        $revision->setAppliedRevision(self::$appliedRevisions[$identifier]);
                    }
                }
            }
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Removes all stored revisions for all nodes
     */
    public function flush(\DateTime $since = null): int
    {
        if (!$since) {
            $count = $this->revisionRepository->countAll();
            $this->revisionRepository->removeAll();
            return $count;
        }
        return $this->revisionRepository->removeAllOlderThan($since);
    }

    /**
     * @return array{type: string, node: array, changes: array}
     */
    protected function generateNodeDiff(
        NodeInterface    $existingNode,
        array            $importedNodeData = null,
        AbstractRenderer $renderer = null
    ): array
    {
        if (!$renderer) {
            return [];
        }

        $importedProperties = $importedNodeData['properties'] ?? [];

        $changes = [
            'type' => $importedNodeData === null ? 'removeNode' : 'changeNode',
            'node' => [
                'identifier' => $existingNode->getIdentifier(),
                'label' => $existingNode->getLabel(),
                'lastModificationDateTime' => $existingNode->getLastPublicationDateTime() ?? $existingNode->getCreationDateTime(),
                'dimensions' => $existingNode->getDimensions(),
                'nodeType' => [
                    'name' => $existingNode->getNodeType()->getName(),
                    'label' => $this->translate($existingNode->getNodeType()->getLabel()),
                    'icon' => $existingNode->getNodeType()->getConfiguration('ui.icon') ?? 'question',
                ],
            ],
            'changes' => [],
        ];

        if ($importedNodeData === null) {
            return $changes;
        }

        if ($importedNodeData) {
            // Check for changes to nodes attributes
            if ($existingNode->getNodeData()->getLastModificationDateTime() != $importedNodeData['lastModificationDateTime']) {
                $changes['changes']['lastModificationDateTime'] = [
                    'propertyLabel' => 'Last modification date',
                    'original' => $existingNode->getNodeData()->getLastModificationDateTime()->format('c'),
                    'originalType' => 'datetime',
                    'changed' => $importedNodeData['lastModificationDateTime'],
                    'changedType' => 'datetime',
                    'diff' => '',
                ];
            }
            if ($existingNode->getNodeType()->getName() != $importedNodeData['nodeType']) {
                $changes['changes']['nodeType'] = [
                    'propertyLabel' => 'Nodetype',
                    'original' => $existingNode->getNodeType()->getName(),
                    'changed' => $importedNodeData['nodeType'],
                    'diff' => '',
                ];
            }
            if ($existingNode->isHidden() != $importedNodeData['hidden']) {
                $changes['changes']['hidden'] = [
                    'propertyLabel' => 'Hidden',
                    'original' => json_encode($existingNode->isHidden()),
                    'changed' => json_encode($importedNodeData['hidden']),
                    'diff' => '',
                ];
            }
            if ($existingNode->isHiddenInIndex() != $importedNodeData['hiddenInIndex']) {
                $changes['changes']['hiddenInIndex'] = [
                    'propertyLabel' => 'Hidden in index',
                    'original' => json_encode($existingNode->isHiddenInIndex()),
                    'changed' => json_encode($importedNodeData['hiddenInIndex']),
                    'diff' => '',
                ];
            }
        }

        foreach ($existingNode->getProperties() as $propertyName => $originalPropertyValue) {
            $changedPropertyValue = $importedProperties[$propertyName] ?? '';
            $diff = '';

            if (($changedPropertyValue === $originalPropertyValue || $this->isSameDateTime($originalPropertyValue, $changedPropertyValue)) && !$existingNode->isRemoved()) {
                continue;
            }

            $originalType = gettype($originalPropertyValue);
            $changedType = gettype($changedPropertyValue);

            $serializedOriginalValue = $this->serializeValue($originalPropertyValue, $existingNode);
            $serializedChangedValue = $this->serializeValue($changedPropertyValue, $existingNode);

            if ($serializedOriginalValue === $serializedChangedValue) {
                continue;
            }

            if (is_string($originalPropertyValue) && is_string($changedPropertyValue)) {
                $originalSlimmedDownContent = $this->renderSlimmedDownContent($serializedOriginalValue);
                $changedSlimmedDownContent = $existingNode->isRemoved() ? '' : $this->renderSlimmedDownContent($serializedChangedValue);

                $rawDiff = new Diff(explode("\n", $originalSlimmedDownContent), explode("\n", $changedSlimmedDownContent), ['context' => 1]);
                $diffArray = $rawDiff->render($renderer);

                if (is_array($diffArray)) {
                    $this->postProcessDiffArray($diffArray);
                }

                if ($diffArray) {
                    $diff = $diffArray;
                }
                // The && in belows condition is on purpose as creating a thumbnail for comparison only works if actually
                // BOTH are ImageInterface (or NULL).
            } else {
                $originalType = $this->resolveChangeType($originalPropertyValue);
                $changedType = $this->resolveChangeType($changedPropertyValue);
            }

            $changes['changes'][$propertyName] = [
                'propertyLabel' => $this->getPropertyLabel($propertyName, $existingNode->getNodeType()),
                'original' => $serializedOriginalValue,
                'changed' => $serializedChangedValue,
                'originalType' => $originalType,
                'changedType' => $changedType,
                'diff' => $diff,
            ];
        }
        return $changes;
    }

    protected function getExistingNode(ContentContext $context, string $importedNodeIdentifier, string $dimensionHash): ?NodeInterface
    {
        $nodeVariants = $context->getNodeVariantsByIdentifier($importedNodeIdentifier);

        foreach ($nodeVariants as $nodeVariant) {
            // The node factory returns null for removed, shadow and inaccessible node data
            if ($nodeVariant === null) {
                continue;
            }
            $variantDimensions = $nodeVariant->getDimensions();
            $variantDimensionHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($variantDimensions);
            if ($variantDimensionHash === $dimensionHash) {
                return $nodeVariant;
            }
        }
        return null;
    }

    protected function resolveChangeType($value): string
    {
        if ($value instanceof ImageInterface) {
            return 'image';
        }
        if ($value instanceof AssetInterface) {
            return 'asset';
        }
        if ($value instanceof NodeInterface) {
            return 'node';
        }
        if ($value instanceof \DateTimeInterface) {
            return 'datetime';
        }
        if (is_array($value)) {
            return 'array';
        }
        return 'text';
    }

    /**
     * Compares local time and UTC offset, the precision of the W3C strings in revisions exported before #20, so those
     * stay quiet while a value re-saved in a zone that displays a different local time still counts as changed
     */
    protected function isSameDateTime($originalValue, $changedValue): bool
    {
        return $originalValue instanceof \DateTimeInterface
            && $changedValue instanceof \DateTimeInterface
            && $originalValue->format(\DateTimeInterface::W3C) === $changedValue->format(\DateTimeInterface::W3C);
    }

    /**
     * Returns a usable label/value for the given property for the diff in the UI
     */
    protected function serializeValue($propertyValue, NodeInterface $contextNode, bool $simple = false): string
    {
        // Convert node id to node if necessary
        if (is_string($propertyValue) && preg_match(NodeIdentifierValidator::PATTERN_MATCH_NODE_IDENTIFIER, $propertyValue) !== 0) {
            $propertyValue = $contextNode->getContext()->getNodeByIdentifier($propertyValue);
        }

        if ($propertyValue instanceof AssetInterface) {
            $filename = $propertyValue->getResource()->getFilename();
            if ($simple) {
                return $filename;
            }
            
            try {
                $uri = $propertyValue->getAssetProxy()->getThumbnailUri()->__toString();
            } catch (\Exception $e) {
                $uri = '';
            }

            return json_encode([
                'src' => $uri,
                'alt' => $filename,
                'title' => $propertyValue->getTitle() ?: $filename,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        }

        if ($propertyValue instanceof NodeInterface) {
            return $propertyValue->getLabel() . ' (' . $propertyValue->getIdentifier() . ')';
        }

        if (is_bool($propertyValue)) {
            return $propertyValue ? 'true' : 'false';
        }

        if (is_array($propertyValue)) {
            $propertyValue = array_map(function ($value) use ($contextNode) {
                return $this->serializeValue($value, $contextNode, true);
            } , $propertyValue);
        }

        return json_encode($propertyValue, JSON_PRETTY_PRINT);
    }

}

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
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Service as I18nService;
use Neos\Flow\I18n\Translator;
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
     * Labels for the revisions created when an applied revision is published, by document node identifier
     *
     * @var array<string, string>
     */
    protected static $revisionLabels = [];

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
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var DatetimeFormatter
     */
    protected $datetimeFormatter;

    /**
     * @Flow\Inject
     * @var I18nService
     */
    protected $localizationService;

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

    public function applyRevision(string $identifier, string $nodePath): bool
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
            $nodesInRevision = $this->withConfiguredAuthorizationChecks(function () use ($revisionContent, $nodePath) {
                return $this->nodeImportService->parseNodes($revisionContent, $nodePath);
            });
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to read revision %s: %s', $revision->getIdentifier(), $throwable->getMessage()));
            return false;
        }
        $problems = $this->withConfiguredAuthorizationChecks(function () use ($nodesInRevision, $liveWorkspace) {
            return $this->validateNodes($nodesInRevision, $liveWorkspace);
        });
        if ($problems !== []) {
            throw new RevisionNotApplicableException($problems);
        }

        $this->emitRevisionApplying($node, $revision);

        // Staging the revision in a workspace and publishing it lets search indexing, frontend revalidation,
        // redirects and the event log handle it like any other change to live
        $workspace = $this->createTemporaryWorkspace($liveWorkspace);
        try {
            $publishedNodes = $this->withConfiguredAuthorizationChecks(function () use ($nodesInRevision, $node, $workspace, $liveWorkspace, $revision) {
                $this->stageNodes($nodesInRevision, $workspace);
                $this->removeNodesMissingInRevision($node, $nodesInRevision, $workspace);
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
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to apply revision %s: %s', $revision->getIdentifier(), $throwable->getMessage()));
            return false;
        } finally {
            $this->removeTemporaryWorkspace($workspace);
        }

        $this->logger->info(sprintf('Applied revision %s on node %s', $revision->getIdentifier(), $node->getIdentifier()));

        // The revision of the restored state is created on shutdown like for any publish. The document registered
        // while publishing belongs to the removed temporary workspace, so the live one replaces it.
        if ($this->settings['revisions']['createRevisionAfterApply']) {
            $useLocale = $this->localizationService->getConfiguration()->getCurrentLocale();
            $revisionDate = $this->datetimeFormatter->formatDateTimeWithCustomPattern($revision->getCreationDateTime(), 'dd.MM.yyyy, HH:mm', $useLocale);

            self::$nodesForRevisions[$node->getIdentifier()] = $node;
            self::$revisionLabels[$node->getIdentifier()] = $this->translator->translateById(
                'action.apply.newRevisionLabel',
                // TODO: Format with localized date or use different identifier?
                ['revision' => $revision->getLabel() ?: $revisionDate],
                null,
                null,
                'Main',
                'NEOSidekick.Revisions'
            );
        } else {
            unset(self::$nodesForRevisions[$node->getIdentifier()]);
        }

        $this->emitRevisionApplied($node, $revision, $publishedNodes);

        return true;
    }

    /**
     * Returns why the revision cannot be applied, not even when forced
     *
     * @return array<string>
     */
    public function validateRevision(Revision $revision): array
    {
        $context = $this->contextFactory->create();
        $node = $context->getNodeByIdentifier($revision->getNodeIdentifier());
        $revisionContent = $revision->getContent();
        if (!$node || !$revisionContent) {
            return [];
        }
        return $this->withConfiguredAuthorizationChecks(function () use ($revisionContent, $node, $context) {
            try {
                $nodesInRevision = $this->nodeImportService->parseNodes($revisionContent, $node->getParentPath());
            } catch (\Throwable $throwable) {
                // applyRevision() logs and rejects unreadable revisions
                return [];
            }
            return $this->validateNodes($nodesInRevision, $context->getWorkspace());
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
     * Finds node types that no longer exist and nodes that cannot be created, moved back or retyped under their parent
     *
     * @param array<array> $nodesInRevision
     * @return array<string>
     */
    protected function validateNodes(array $nodesInRevision, Workspace $liveWorkspace): array
    {
        $problems = [];
        $nodeTypeNamesByPath = [];
        foreach ($nodesInRevision as $nodeData) {
            if ($nodeData['removed']) {
                continue;
            }
            if (!$this->nodeTypeManager->hasNodeType($nodeData['nodeType'])) {
                $problems[] = sprintf('Node type "%s" of node "%s" does not exist', $nodeData['nodeType'], $nodeData['path']);
                continue;
            }
            $nodeTypeNamesByPath[$nodeData['path']] = $nodeData['nodeType'];

            $context = $this->createContext($liveWorkspace, $nodeData['dimensionValues']);
            $existingNode = $context->getNodeByIdentifier($nodeData['identifier']);
            if ($existingNode !== null && $existingNode->getPath() === $nodeData['path'] && $existingNode->getNodeType()->getName() === $nodeData['nodeType']) {
                continue;
            }

            // The node would be created, moved back or retyped, so its parent must allow it
            $nodeName = NodePaths::getNodeNameFromPath($nodeData['path']);
            $parentNodeType = $this->resolveNodeType($nodeData['parentPath'], $context, $nodeTypeNamesByPath);
            if ($parentNodeType === null) {
                $problems[] = sprintf('Parent "%s" of node "%s" does not exist', $nodeData['parentPath'], $nodeData['path']);
                continue;
            }
            if (isset($parentNodeType->getAutoCreatedChildNodes()[$nodeName])) {
                continue;
            }
            $nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodeType']);
            $parentName = NodePaths::getNodeNameFromPath($nodeData['parentPath']);
            $grandParentNodeType = $this->resolveNodeType(NodePaths::getParentPath($nodeData['parentPath']), $context, $nodeTypeNamesByPath);
            $isAllowed = $grandParentNodeType !== null && isset($grandParentNodeType->getAutoCreatedChildNodes()[$parentName])
                ? $grandParentNodeType->allowsGrandchildNodeType($parentName, $nodeType)
                : $parentNodeType->allowsChildNodeType($nodeType);
            if (!$isAllowed) {
                $problems[] = sprintf('Node "%s" of type "%s" is not allowed in "%s"', $nodeData['path'], $nodeData['nodeType'], $nodeData['parentPath']);
            }
        }
        return $problems;
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
     */
    protected function stageNodes(array $nodesInRevision, Workspace $workspace): void
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
                // Only visible through a fallback dimension. adoptNode() would also trigger translation integrations.
                $node = $node->createVariantForContext($context);
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

            // Content moved to another page since the revision is moved back, see checkRevisionForConflicts()
            if ($node->getPath() !== $nodeData['path']) {
                $node->moveInto($this->getParentNode($context, $nodeData), $nodeName);
            }
            $node->setIndex((int)$nodeData['sortingIndex']);
        }
    }

    /**
     * Removes the content node variants of the document that did not exist when the revision was created
     *
     * @param array<array> $nodesInRevision
     */
    protected function removeNodesMissingInRevision(NodeInterface $documentNode, array $nodesInRevision, Workspace $workspace): void
    {
        $variantsInRevision = [];
        foreach ($nodesInRevision as $nodeData) {
            $variantsInRevision[$nodeData['identifier'] . '@' . Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeData['dimensionValues'])] = true;
        }

        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {
            $targetDimensionValues = array_map(static function (array $values) {
                return [reset($values)];
            }, $dimensionCombination);
            $documentVariant = $this->createContext($workspace, $targetDimensionValues)->getNodeByIdentifier($documentNode->getIdentifier());
            if ($documentVariant !== null) {
                $this->removeChildNodesMissingInRevision($documentVariant, $variantsInRevision);
            }
        }
    }

    /**
     * @param array<string, bool> $variantsInRevision
     */
    protected function removeChildNodesMissingInRevision(NodeInterface $parentNode, array $variantsInRevision): void
    {
        foreach ($parentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
            $variantKey = $childNode->getIdentifier() . '@' . $childNode->getNodeData()->getDimensionsHash();
            // Fallback variants are handled in the context of their own dimensions, tethered nodes belong to the node type
            $isRemovable = $this->isVariantOfContext($childNode) && !$childNode->isAutoCreated();
            if ($isRemovable && !isset($variantsInRevision[$variantKey])) {
                $childNode->remove();
                continue;
            }
            $this->removeChildNodesMissingInRevision($childNode, $variantsInRevision);
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
            $importedNodeIdentifier = $nodeDataInImport['identifier'];
            $dimensionHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($nodeDataInImport['dimensionValues']);

            $existingNode = $this->getExistingNode($context, $importedNodeIdentifier, $dimensionHash);

            if (!$existingNode) {
                $importedNodeTypeName = $nodeDataInImport['nodeType'];
                $importedNodeType = $this->nodeTypeManager->getNodeType($importedNodeTypeName);

                $changesByNode[$importedNodeIdentifier][$dimensionHash] = [
                    'type' => 'addNode',
                    'node' => [
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

    /**
     * @return string[]
     */
    public function checkRevisionForConflicts(Revision $revision): array
    {
        $context = $this->contextFactory->create();
        $node = $context->getNodeByIdentifier($revision->getNodeIdentifier());

        if (!$node) {
            return [sprintf('Could not find node with identifier "%s" to apply revision to', $revision->getNodeIdentifier())];
        }

        $revisionContent = $revision->getContent();
        if (!$revisionContent) {
            return [sprintf('Could not find revision content for revision %s', $revision->getIdentifier())];
        }

        $revisionRootPath = $node->getPath();
        $nodeIdentifiersForImport = $this->getNodeIdentifiersFromRevision($revisionContent);
        $conflicts = [];

        foreach ($nodeIdentifiersForImport as $nodeIdentifier) {
            $existingNode = $context->getNodeByIdentifier($nodeIdentifier);
            if (!$existingNode) {
                continue;
            }

            $closestDocumentNode = $this->getClosestDocumentNode($existingNode);
            if (!$closestDocumentNode) {
                $conflicts[] = sprintf('The content "%s" was moved to an unknown page and would be moved back to this page when the revision is applied!', $existingNode->getLabel());
            } else if ($closestDocumentNode->getPath() !== $revisionRootPath) {
                $conflicts[] = sprintf('The content "%s" was moved to page "%s" and would be moved back to this page when the revision is applied!', $existingNode->getLabel(), $closestDocumentNode->getLabel());
            }
        }

        return $conflicts;
    }

    protected function getClosestDocumentNode(NodeInterface $node): ?NodeInterface
    {
        $parentNode = $node;
        while ($parentNode && !$parentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
            $parentNode = $parentNode->getParent();
        }
        return $parentNode;
    }

    protected function getNodeIdentifiersFromRevision(\XMLReader $xmlReader): array
    {
        $nodeIdentifiers = [];
        while ($xmlReader->read()) {
            if (!$xmlReader->isEmptyElement && $xmlReader->nodeType === \XMLReader::ELEMENT && $xmlReader->name === 'node') {
                $nodeIdentifiers[] = $xmlReader->getAttribute('identifier');
            }
        }
        return $nodeIdentifiers;
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
                    $this->createRevision($nodeToUse, self::$revisionLabels[$identifier] ?? null);
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
            'type' => $importedProperties === null ? 'removeNode' : 'changeNode',
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

            if ($changedPropertyValue === $originalPropertyValue && !$existingNode->isRemoved()) {
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
        if ($value instanceof \DateTime) {
            return 'datetime';
        }
        if (is_array($value)) {
            return 'array';
        }
        return 'text';
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

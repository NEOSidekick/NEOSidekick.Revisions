<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Service;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeDimension;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\ImportException;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\AbstractRenderer;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use CodeQ\Revisions\Domain\Model\Revision;
use CodeQ\Revisions\Domain\Repository\RevisionRepository;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\I18n\EelHelper\TranslationParameterToken;
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Service as I18nService;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Fusion\Cache\ContentCacheFlusher;
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
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ContentCacheFlusher
     */
    protected $contentCacheFlusher;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\InjectConfiguration(package="CodeQ.Revisions")
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
        $creator = $this->securityContext->canBeInitialized() ? $this->securityContext->getAccount() : null;
        $enableCompression = $this->settings['compression']['enabled'] ?? true;

        $revision = new Revision(
            $node->getIdentifier(),
            $creator ? $creator->getAccountIdentifier() : 'CLI',
            $xmlWriter->flush(),
            $label,
            $enableCompression
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

        $success = false;

        try {
            $this->nodeImportService->import($revisionContent, $nodePath);

            $importedNodeIdentifiers = $this->nodeImportService->getPersistedNodeIdentifiers();
            $this->handleUnknownNodesInTargetPath($importedNodeIdentifiers, $node->getPath(), $liveWorkspace);

            $this->logger->info(sprintf('Applied revision %s on node %s', $revision->getIdentifier(), $node->getIdentifier()));

            if ($this->settings['revisions']['createRevisionAfterApply']) {
                $useLocale = $this->localizationService->getConfiguration()->getCurrentLocale();
                $revisionDate = $this->datetimeFormatter->formatDateTimeWithCustomPattern($revision->getCreationDateTime(), 'dd.MM.yyyy, HH:mm', $useLocale);

                $this->createRevisionInternal(
                    $node,
                    $this->translator->translateById(
                        'action.apply.newRevisionLabel',
                        // TODO: Format with localized date or use different identifier?
                        ['revision' => $revision->getLabel() ?: $revisionDate],
                        null,
                        null,
                        'Main',
                        'CodeQ.Revisions'
                    )
                );
            }

            $success = true;
        } catch (ImportException $e) {
            $this->logger->error(sprintf('Failed to apply revision for node %s', $revision->getIdentifier()));
        } finally {
            $this->flushNodeAndChildren($node, $liveWorkspace);
        }

        return $success;
    }

    /**
     * @return array<string, array> List of changes by node identifier
     * @throws IllegalObjectTypeException
     */
    public function compareRevision(Revision $revision, string $parentPath, AbstractRenderer $renderer): array
    {
        $revisionContent = $revision->getContent();
        if (!$revisionContent) {
            $this->logger->warning(sprintf('Could not find any content in revision %s', $revision->getIdentifier()));
            return [];
        }

        $context = $this->contextFactory->create();
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
            $existingNode = $context->getNodeByIdentifier($importedNodeIdentifier);
            $importedProperties = $nodeDataInImport['properties'];

            if (!$existingNode) {
                $importedNodeTypeName = $nodeDataInImport['nodeType'];
                $importedNodeType = $this->nodeTypeManager->getNodeType($importedNodeTypeName);

                $changesByNode[$importedNodeIdentifier] = [
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
                    // TODO: Generate diff. This is currently not easiyl possible as we need a NodeData object instead of an array.
                ];
                continue;
            }

            // Filter existing nodes that are part of the import
            $existingNodesInTargetPath = array_filter($existingNodesInTargetPath, static function ($existingNode) use ($importedNodeIdentifier) {
                return $existingNode->getIdentifier() !== $importedNodeIdentifier;
            });

            $changes = $this->generateNodeDiff(
                $existingNode,
                $importedProperties,
                $renderer
            );

            // Skip empty changes
            $existingNodeTimestamp = $existingNode->getLastModificationDateTime()->getTimestamp();
            $importedNodeTimestamp = $nodeDataInImport['lastModificationDateTime']->getTimestamp();
            $isChanged = $existingNodeTimestamp !== $importedNodeTimestamp || $changes['type'] !== 'changeNode' || !empty($changes['contentChanges']);

            if ($isChanged)   {
                $changesByNode[$importedNodeIdentifier] = $changes;
            }
        }

        // Create diff for nodes that are not part of the import
        foreach ($existingNodesInTargetPath as $existingNodeData) {
            $identifier = $existingNodeData->getIdentifier();
            $existingNode = $context->getNodeByIdentifier($identifier);
            $changesByNode[$identifier] = $this->generateNodeDiff(
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

    protected function flushNodeAndChildren(NodeInterface $node, Workspace $workspace): void
    {
        try {
            /** @var array<NodeInterface> $nodes */
            $nodes = (new FlowQuery([$node]))->find('[instanceof Neos.Neos:Node]')->get();

            foreach ($nodes as $childNode) {
                $this->contentCacheFlusher->registerNodeChange($childNode, $workspace);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Failed to flush node %s', $node->getPath()), [$e->getMessage()]);
        }
    }

    public function registerNodeChange(NodeInterface $node, Workspace $targetWorkspace = null): void
    {
        if (!$targetWorkspace || $targetWorkspace->getName() !== 'live') {
            return;
        }

        // Find the closest document node and store it.
        // For each node a revisions will be created at the end of the request to prevent duplicates.
        $documentNode = $node;
        while ($documentNode && !$documentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
            $documentNode = $documentNode->getParent();
        }

        if ($documentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
            self::$nodesForRevisions[$documentNode->getIdentifier()] = $documentNode;
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
        foreach (self::$nodesForRevisions as $node) {
            $this->createRevision($node);
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

    protected function handleUnknownNodesInTargetPath(array $importedNodeIdentifiers, string $startingPointNodePath, Workspace $workspace): void
    {
        $nodes = $this->nodeService->findContentNodes($startingPointNodePath, $workspace);

        // Filter nodes that are on the path of the imported nodes but didn't exist in the applied revision
        $unknownNodes = array_filter($nodes, static function (NodeData $node) use ($importedNodeIdentifiers) {
            return !in_array($node->getIdentifier(), $importedNodeIdentifiers, true);
        });

        $this->nodeService->removeNodes($unknownNodes);
    }

    protected function generateNodeDiff(
        NodeInterface $existingNode,
        array $importedProperties = null,
        AbstractRenderer $renderer = null
    ): array
    {
        if (!$renderer) {
            return [];
        }

        $changes = [
            'type' => $importedProperties === null ? 'removeNode' : 'changeNode',
            'node' => [
                'identifier' => $existingNode->getIdentifier(),
                'label' => $existingNode->getLabel(),
                'lastModificationDateTime' => $existingNode->getLastPublicationDateTime(),
                'dimensions' => $existingNode->getDimensions(),
                'nodeType' => [
                    'name' => $existingNode->getNodeType()->getName(),
                    'label' => $this->translate($existingNode->getNodeType()->getLabel()),
                    'icon' => $existingNode->getNodeType()->getConfiguration('ui.icon') ?? 'question',
                ],
            ],
            'contentChanges' => [],
        ];

        foreach ($existingNode->getProperties() as $propertyName => $originalPropertyValue) {
            $changedPropertyValue = $importedProperties[$propertyName] ?? '';
            $diff = '';
            $type = '';

            if ($changedPropertyValue === $originalPropertyValue && !$existingNode->isRemoved()) {
                continue;
            }

            if (!is_object($originalPropertyValue) && !is_object($changedPropertyValue)) {
                $originalSlimmedDownContent = $this->renderSlimmedDownContent($originalPropertyValue);
                $changedSlimmedDownContent = $existingNode->isRemoved() ? '' : $this->renderSlimmedDownContent($changedPropertyValue);

                $rawDiff = new Diff(explode("\n", $originalSlimmedDownContent), explode("\n", $changedSlimmedDownContent), ['context' => 1]);
                $diffArray = $rawDiff->render($renderer);

                if (is_array($diffArray)) {
                    $this->postProcessDiffArray($diffArray);
                }

                if ($diffArray) {
                    $type = 'text';
                    $diff = $diffArray;
                }
                // The && in belows condition is on purpose as creating a thumbnail for comparison only works if actually
                // BOTH are ImageInterface (or NULL).
            } elseif (
                ($originalPropertyValue instanceof ImageInterface || $originalPropertyValue === null)
                && ($changedPropertyValue instanceof ImageInterface || $changedPropertyValue === null)
            ) {
                $type = 'image';
            } elseif ($originalPropertyValue instanceof AssetInterface || $changedPropertyValue instanceof AssetInterface) {
                $type = 'asset';
            } elseif ($originalPropertyValue instanceof \DateTime || $changedPropertyValue instanceof \DateTime) {
                $changed = false;
                if (!$changedPropertyValue instanceof \DateTime || !$originalPropertyValue instanceof \DateTime) {
                    $changed = true;
                } elseif ($changedPropertyValue->getTimestamp() !== $originalPropertyValue->getTimestamp()) {
                    $changed = true;
                }
                if ($changed) {
                    $type = 'datetime';
                }
            }

            $changes['contentChanges'][$propertyName] = [
                'type' => $type,
                'propertyLabel' => $this->getPropertyLabel($propertyName, $existingNode->getNodeType()),
                'original' => $originalPropertyValue,
                'changed' => $changedPropertyValue,
                'diff' => $diff,
            ];
        }
        return $changes;
    }

}

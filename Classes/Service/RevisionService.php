<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Service;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context as ContentContext;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\ImportException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Utility;
use Neos\ContentRepository\Validation\Validator\NodeIdentifierValidator;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\AbstractRenderer;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use CodeQ\Revisions\Domain\Model\Revision;
use CodeQ\Revisions\Domain\Repository\RevisionRepository;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Service as I18nService;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Context;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
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
     * @var array<string, bool>
     */
    protected static $movedNodes = [];

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

    protected function createRevisionInternal(NodeInterface $node, string $label = null, bool $empty = false): ?Revision
    {
        $content = '';
        if (!$empty) {
            $xmlWriter = $this->nodeExportService->export($node->getPath());
            $content = $xmlWriter->flush();
        }
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
                    ),
                    true
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
                    $this->createRevision($nodeToUse);
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

    protected function handleUnknownNodesInTargetPath(array $importedNodeIdentifiers, string $startingPointNodePath, Workspace $workspace): void
    {
        $nodes = $this->nodeService->findContentNodes($startingPointNodePath, $workspace);

        // Filter nodes that are on the path of the imported nodes but didn't exist in the applied revision
        $unknownNodes = array_filter($nodes, static function (NodeData $node) use ($importedNodeIdentifiers) {
            return !in_array($node->getIdentifier(), $importedNodeIdentifiers, true);
        });

        $this->nodeService->removeNodes($unknownNodes);
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

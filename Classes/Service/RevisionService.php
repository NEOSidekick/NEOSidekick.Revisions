<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Service;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Exception\ImportException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use CodeQ\Revisions\Domain\Model\Revision;
use CodeQ\Revisions\Domain\Repository\RevisionRepository;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
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

    public function createRevision(NodeInterface $node): ?Revision
    {
        $xmlWriter = $this->nodeExportService->export($node->getPath());
        $creator = $this->securityContext->canBeInitialized() ? $this->securityContext->getAccount() : null;
        $enableCompression = $this->settings['compression']['enabled'] ?? true;

        $revision = new Revision(
            $node->getIdentifier(),
            $creator ? $creator->getAccountIdentifier() : 'CLI',
            $xmlWriter->flush(),
            null,
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

    public function setLabel(?Revision $revision, $label): void
    {
        if (!$revision) {
            return;
        }
        $revision->setLabel($label);
        $this->logger->info(sprintf('Set label "%s" for revision %s', $label, $revision->getIdentifier()));
        $this->revisionRepository->update($revision);
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
            $this->logger->warning(sprintf('Could not find revision content for revision %s', $identifier));
            return false;
        }

        $success = false;

        try {
            $this->nodeImportService->import($revisionContent, $nodePath);

            $importedNodeIdentifiers = $this->nodeImportService->getPersistedNodeIdentifiers();
            $this->handleUnkownNodesInTargetPath($importedNodeIdentifiers, $node->getPath(), $liveWorkspace);

            $this->logger->info(sprintf('Applied revision %s on node %s', $revision->getIdentifier(), $node->getIdentifier()));
            $success = true;
        } catch (ImportException $e) {
            $this->logger->error(sprintf('Failed to apply revision for node %s', $revision->getIdentifier()));
        } finally {
            $this->flushNodeAndChildren($node, $liveWorkspace);
        }

        return $success;
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

    protected function handleUnkownNodesInTargetPath(array $importedNodeIdentifiers, string $startingPointNodePath, Workspace $workspace): void
    {
        $nodes = $this->nodeService->findContentNodes($startingPointNodePath, $workspace);

        // Filter nodes that are on the path of the imported nodes but didn't exist in the applied revision
        $unknownNodes = array_filter($nodes, static function (NodeData $node) use ($importedNodeIdentifiers) {
            return !in_array($node->getIdentifier(), $importedNodeIdentifiers, true);
        });

        $this->nodeService->removeNodes($unknownNodes);
    }

}

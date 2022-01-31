<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Command;

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use CodeQ\Revisions\Service\RevisionService;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 *
 * @Flow\Scope("singleton")
 */
class RevisionCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var RevisionService
     */
    protected $revisionService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Create revision for the given nodePath
     */
    public function createCommand(string $nodeIdentifier): void
    {
        $node = $this->contextFactory->create()->getNodeByIdentifier($nodeIdentifier);

        if ($node === null) {
            $this->outputLine('Node not found');
            $this->quit(1);
        }

        $this->outputLine('Creating revision for node "%s"', [$node->getPath()]);
        $result = $this->revisionService->createRevision($node);

        if ($result === null) {
            $this->outputLine('Revision could not be created');
            $this->quit(1);
        } else {
            $this->outputLine('Revision created');
        }
    }

    public function listCommand(string $nodeIdentifier): void
    {
        $node = $this->contextFactory->create()->getNodeByIdentifier($nodeIdentifier);

        if ($node === null) {
            $this->outputLine('Node not found');
            $this->quit(1);
        }

        $this->outputLine('Retrieving revisions for node "%s"', [$node->getPath()]);
        $result = $this->revisionService->getRevisions($node);

        if (empty($result)) {
            $this->outputLine('No revisions found');
            $this->quit(1);
        } else {
            $this->outputLine("\nRevisions found:\n");
            foreach ($result as $revision) {
                $this->outputLine('%s - %s - %s', [
                    $revision->getCreator(),
                    $revision->getCreationDateTime()->format('Y-m-d H:i:s'),
                    $this->persistenceManager->getIdentifierByObject($revision),
                ]);
            }
        }
    }

    public function applyCommand(string $revisionIdentifier): void
    {
        $revision = $this->revisionService->getRevision($revisionIdentifier);

        if ($revision === null) {
            $this->outputLine('Revision not found');
            $this->quit(1);
        }

        $node = $this->contextFactory->create()->getNodeByIdentifier($revision->getNodeIdentifier());

        if (!$node) {
            $this->outputLine('Node for revision not found');
            $this->quit(1);
        }

        $this->outputLine('Applying revision "%s"', [$revisionIdentifier]);
        $result = $this->revisionService->applyRevision($revisionIdentifier, $node->getParentPath());

        if ($result === null) {
            $this->outputLine('Revision could not be applied');
            $this->quit(1);
        } else {
            $this->outputLine('Revision applied');
        }
    }
}

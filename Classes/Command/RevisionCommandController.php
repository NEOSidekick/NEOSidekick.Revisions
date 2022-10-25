<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Command;

/**
 * This file is part of the CodeQ.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use CodeQ\Revisions\Domain\Model\Revision;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Diff\Renderer\Text\TextUnifiedRenderer;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use CodeQ\Revisions\Service\RevisionService;
use Neos\Flow\Cli\Exception\StopCommandException;
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
     * @throws StopCommandException
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

    /**
     * List all revisions for the given node identifier
     *
     * @throws StopCommandException
     */
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
            $rows = array_map(function (Revision $revision) {
                return [
                    $revision->getCreationDateTime()->format('Y-m-d H:i:s'),
                    $revision->getLabel(),
                    $revision->getCreator(),
                    $this->persistenceManager->getIdentifierByObject($revision),
                ];
            }, $result);

            $this->outputLine("\nRevisions found:\n");
            $this->output->outputTable($rows, ['Creation Date', 'Label', 'Creator', 'Identifier']);
        }
    }

    /**
     * Apply a specific revision to the node it was created from
     *
     * @throws StopCommandException
     */
    public function applyCommand(string $revisionIdentifier): void
    {
        [$revision, $node] = $this->getRevisionAndNode($revisionIdentifier);

        if (!$this->output->askConfirmation(
            sprintf(
                'Do you really want to apply revision "%s" to node "%s" with identifier "%s"? [y/N]',
                $revisionIdentifier,
                $node->getLabel(),
                $node->getIdentifier()
            ),
            false
        )) {
            $this->outputLine('Aborted');
            $this->quit(1);
        }

        $this->outputLine('Applying revision "%s"', [$revisionIdentifier]);
        $result = $this->revisionService->applyRevision($revisionIdentifier, $node->getParentPath());

        if (!$result) {
            $this->outputLine('Revision could not be applied');
            $this->quit(1);
        } else {
            $this->outputLine('Revision applied');
        }
    }

    public function flushCommand(string $since = null, bool $force = false): void
    {
        if (!$force && !$this->output->askConfirmation('Do you really want to flush all revisions? [y/N]', false)) {
            $this->outputLine('Aborted');
            $this->quit(1);
        }
        $sinceDateTime = $since ? new \DateTime($since) : null;
        $count = $this->revisionService->flush($sinceDateTime);
        $this->outputLine(sprintf('%d revisions flushed', $count));
    }

    public function compareCommand(string $revisionIdentifier): void
    {
        [$revision, $node] = $this->getRevisionAndNode($revisionIdentifier);
        $diff = $this->revisionService->compareRevision($revision, $node->getParentPath(), new TextUnifiedRenderer());
        $headers = ['Type', 'Node', 'Dimensions', 'Label', 'Diff'];
        $rows = array_reduce($diff, static function ($carry, $nodeChangeByDimension) {
            foreach ($nodeChangeByDimension as $nodeChange) {
                $carry[] = [
                    $nodeChange['type'],
                    $nodeChange['node']['identifier'],
                    json_encode($nodeChange['node']['dimensions']),
                    $nodeChange['node']['label'],
                    implode("\n", array_map(static function ($property) use ($nodeChange) {
                        return sprintf("%s:\n%s", $property, $nodeChange['changes'][$property]['diff']);
                    }, array_keys($nodeChange['changes'] ?? []))),
                ];
            }
            return $carry;
        }, []);
        $this->output->outputTable($rows, $headers);
    }

    /**
     * @return array[Revision, NodeInterface]
     * @throws StopCommandException
     */
    protected function getRevisionAndNode(string $revisionIdentifier): array
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

        return [$revision, $node];
    }
}

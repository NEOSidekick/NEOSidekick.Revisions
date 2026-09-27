<?php
declare(strict_types=1);

namespace NEOSidekick\Revisions\Command;

/**
 * This file is part of the NEOSidekick.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use NEOSidekick\Revisions\Domain\Model\Revision;
use NEOSidekick\Revisions\Exception\RevisionApplyDeniedException;
use NEOSidekick\Revisions\Exception\RevisionNotApplicableException;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Diff\Renderer\Text\TextUnifiedRenderer;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use NEOSidekick\Revisions\Service\RevisionService;
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
            $revisionsByIdentifier = [];
            foreach ($result as $revision) {
                $revisionsByIdentifier[$revision->getIdentifier()] = $revision;
            }
            $rows = array_map(function (Revision $revision) use ($revisionsByIdentifier) {
                $label = $revision->getLabel();
                if ($label === '' && $revision->getAppliedRevisionIdentifier() !== null) {
                    $source = $revisionsByIdentifier[$revision->getAppliedRevisionIdentifier()] ?? null;
                    $label = sprintf('Applied revision %s', $source && $source->getLabel() !== '' ? $source->getLabel() : $revision->getAppliedRevisionCreationDateTime()->format('Y-m-d H:i:s'));
                }
                return [
                    $revision->getCreationDateTime()->format('Y-m-d H:i:s'),
                    $label,
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
     * Content moved to another page since the revision is moved back, and content moved to the page since the
     * revision is removed, unless the resolutions file decides otherwise. A revision whose node types no longer fit is
     * refused, with the nodes that need a resolution.
     *
     * @param string $revisionIdentifier
     * @param string|null $resolutions A JSON file with resolutions by node identifier, e.g. {"<node identifier>": {"__skip": true}}
     * @throws StopCommandException
     */
    public function applyCommand(string $revisionIdentifier, ?string $resolutions = null): void
    {
        [$revision, $node] = $this->getRevisionAndNode($revisionIdentifier);

        $resolutionsByNode = $resolutions !== null ? $this->readResolutions($resolutions) : [];
        $validation = $this->revisionService->validateRevision($revision, $resolutionsByNode);
        // A default can bring further rows, e.g. for content moved into a node that is moved back
        for ($round = 0; $round < 10; $round++) {
            $defaults = $this->getDefaultResolutions($validation['rows'], $resolutionsByNode);
            if ($defaults === []) {
                break;
            }
            foreach ($defaults as $identifier => $default) {
                $resolutionsByNode[$identifier] = [$default['resolution'] => true];
                $this->outputLine($default['resolution'] === RevisionService::RESOLUTION_MOVE_BACK ? 'Moving back "%s" (%s): %s' : 'Removing "%s" (%s): %s', [$default['row']['label'], $identifier, implode(' ', array_column($default['row']['problems'], 'message'))]);
            }
            $validation = $this->revisionService->validateRevision($revision, $resolutionsByNode);
        }
        if (!$validation['isApplicable']) {
            $this->outputLine('Revision cannot be applied:');
            foreach ($validation['errors'] as $error) {
                $this->outputLine('  %s', [$error]);
            }
            $unresolvedRows = array_filter($validation['rows'], static function (array $row): bool {
                return $row['resolution'] === null;
            });
            if ($unresolvedRows !== []) {
                $this->outputLine("\nThese nodes need a resolution in the file given with --resolutions:\n");
                $this->output->outputTable(array_map(static function (array $row): array {
                    return [$row['identifier'], $row['path'], implode("\n", array_column($row['problems'], 'message')), implode(', ', $row['choices'])];
                }, $unresolvedRows), ['Node', 'Path', 'Problem', 'Resolutions']);
            }
            $this->quit(1);
        }

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
        try {
            $result = $this->revisionService->applyRevision($revisionIdentifier, $resolutionsByNode);
        } catch (RevisionNotApplicableException | RevisionApplyDeniedException $exception) {
            $this->outputLine('Revision cannot be applied:');
            $this->outputLine($exception->getMessage());
            $this->quit(1);
        }

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
     * @return array<mixed>
     * @throws StopCommandException
     */
    protected function readResolutions(string $path): array
    {
        $json = is_file($path) ? file_get_contents($path) : false;
        $resolutions = $json !== false ? json_decode($json, true) : null;
        if (!is_array($resolutions)) {
            $this->outputLine('The resolutions file "%s" cannot be read as a JSON object', [$path]);
            $this->quit(1);
        }
        return $resolutions;
    }

    /**
     * Keeps the behaviour from before resolutions existed for the rows the file leaves open: content moved to another
     * page is moved back, content moved to the page is removed
     *
     * @param array<array> $rows
     * @param array<mixed> $resolutions
     * @return array<string, array{resolution: string, row: array}>
     */
    protected function getDefaultResolutions(array $rows, array $resolutions): array
    {
        $defaults = [];
        foreach ($rows as $row) {
            if ($row['resolution'] !== null || isset($resolutions[$row['identifier']])) {
                continue;
            }
            $problemIds = array_column($row['problems'], 'id');
            if (in_array('movedAway', $problemIds, true) && in_array(RevisionService::RESOLUTION_MOVE_BACK, $row['choices'], true)) {
                $defaults[$row['identifier']] = ['resolution' => RevisionService::RESOLUTION_MOVE_BACK, 'row' => $row];
            } elseif (in_array('movedHere', $problemIds, true) && in_array(RevisionService::RESOLUTION_REMOVE, $row['choices'], true)) {
                $defaults[$row['identifier']] = ['resolution' => RevisionService::RESOLUTION_REMOVE, 'row' => $row];
            }
        }
        return $defaults;
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

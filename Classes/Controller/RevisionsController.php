<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Controller;

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
use CodeQ\Revisions\Service\RevisionService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\View\JsonView;

class RevisionsController extends ActionController
{
    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class
    ];

    /**
     * @Flow\Inject
     * @var RevisionService
     */
    protected $revisionService;

    /**
     * @throws StopActionException
     */
    public function getAction(NodeInterface $node = null): void
    {
        if (!$node) {
            $this->throwStatus(404, 'Node not found');
        }

        $revisions = array_map(static function (Revision $revision) {
            return [
                'identifier' => $revision->getIdentifier(),
                'label' => $revision->getLabel(),
                'nodeIdentifier' => $revision->getNodeIdentifier(),
                'creator' => $revision->getCreator(),
                'creationDateTime' => $revision->getCreationDateTime(),
            ];
        }, $this->revisionService->getRevisions($node));

        $this->view->assign('value', [
            'revisions' => $revisions,
        ]);
    }

    public function applyAction(NodeInterface $node = null, Revision $revision = null, bool $force = false): void
    {
        if (!$node) {
            $this->throwStatus(404, 'Node not found');
        }

        if (!$revision) {
            $this->throwStatus(404, 'Revision not found');
        }

        if (!$force) {
            $conflicts = $this->revisionService->checkRevisionForConflicts($revision);

            if ($conflicts) {
                $this->throwStatus(409, json_encode($conflicts, JSON_PRETTY_PRINT));
            }
        }

        $this->revisionService->applyRevision($revision->getIdentifier(), $node->getParentPath());

        $this->view->assign('value', [
            'success' => true,
        ]);
    }

    public function deleteAction(Revision $revision = null): void
    {
        if (!$revision) {
            $this->throwStatus(404, 'Revision not found');
        }

        $this->revisionService->deleteRevision($revision->getIdentifier());

        $this->view->assign('value', [
            'success' => true,
        ]);
    }

    public function setLabelAction(Revision $revision = null, string $label = ''): void
    {
        if (!$revision) {
            $this->throwStatus(404, 'Revision not found');
        }

        $this->revisionService->setLabel($revision, $label);

        $this->view->assign('value', [
            'success' => true,
        ]);
    }
}

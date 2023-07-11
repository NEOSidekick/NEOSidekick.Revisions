<?php
declare(strict_types=1);

namespace NEOSidekick\Revisions\Controller;

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
use NEOSidekick\Revisions\Service\RevisionService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
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
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @throws StopActionException
     */
    public function getAction(NodeInterface $node = null): void
    {
        if (!$node) {
            $this->throwStatus(404, $this->translate('error.nodeNotFound', 'Page not found'));
        }

        $revisions = array_map(static function (Revision $revision) {
            return [
                'identifier' => $revision->getIdentifier(),
                'label' => $revision->getLabel(),
                'nodeIdentifier' => $revision->getNodeIdentifier(),
                'creator' => $revision->getCreator(),
                'creationDateTime' => $revision->getCreationDateTime(),
                'isEmpty' => $revision->isEmpty(),
                'isMoved' => $revision->isMoved(),
            ];
        }, $this->revisionService->getRevisions($node));

        $this->view->assign('value', [
            'revisions' => $revisions,
        ]);
    }

    public function applyAction(NodeInterface $node = null, Revision $revision = null, bool $force = false): void
    {
        if (!$node) {
            $this->throwStatus(404, $this->translate('error.nodeNotFound', 'Page not found'));
        }

        if (!$revision) {
            $this->throwStatus(404, $this->translate('error.revisionNotFound', 'Revision not found'));
        }

        if (!$force) {
            $conflicts = $this->revisionService->checkRevisionForConflicts($revision);

            if ($conflicts) {
                $this->throwStatus(409, $this->translate('error.revisionHasConflicts', 'Revision has conflicts'), json_encode($conflicts, JSON_PRETTY_PRINT));
            }
        }

        $result = $this->revisionService->applyRevision($revision->getIdentifier(), $node->getParentPath());

        if (!$result) {
            $this->throwStatus(500, $this->translate('error.revisionNotApplied', 'Failed to apply revision'));
        }

        $this->view->assign('value', [
            'success' => true,
        ]);
    }

    public function getDiffAction(NodeInterface $node = null, Revision $revision = null): void
    {
        if (!$node) {
            $this->throwStatus(404, $this->translate('error.nodeNotFound', 'Page not found'));
        }

        if (!$revision) {
            $this->throwStatus(404, $this->translate('error.revisionNotFound', 'Revision not found'));
        }

        $diff = $this->revisionService->compareRevision($revision, $node->getParentPath(), new HtmlArrayRenderer());

        $this->view->assign('value', [
            'diff' => $diff,
        ]);
    }

    /**
     * @throws StopActionException
     */
    public function deleteAction(Revision $revision = null): void
    {
        if (!$revision) {
            $this->throwStatus(404, $this->translate('error.revisionNotFound', 'Revision not found'));
        }

        $this->revisionService->deleteRevision($revision->getIdentifier());

        $this->view->assign('value', [
            'success' => true,
        ]);
    }

    /**
     * @throws StopActionException
     */
    public function setLabelAction(Revision $revision = null, string $label = ''): void
    {
        if (!$revision) {
            $this->throwStatus(404, $this->translate('error.revisionNotFound', 'Revision not found'));
        }

        $this->revisionService->setLabel($revision, $label);

        $this->view->assign('value', [
            'success' => true,
        ]);
    }

    protected function translate(string $id, string $fallback = '', array $arguments = []): string
    {
        try {
            return $this->translator->translateById($id, $arguments, null, null, 'Main', 'NEOSidekick.Revisions');
        } catch (\Exception $exception) {
        }
        return $fallback;
    }
}

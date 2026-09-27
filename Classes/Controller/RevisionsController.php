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
use NEOSidekick\Revisions\Exception\RevisionApplyDeniedException;
use NEOSidekick\Revisions\Exception\RevisionNotApplicableException;
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

        $nodeRevisions = $this->revisionService->getRevisions($node);
        $revisionsByIdentifier = [];
        foreach ($nodeRevisions as $revision) {
            $revisionsByIdentifier[$revision->getIdentifier()] = $revision;
        }

        $revisions = array_map(static function (Revision $revision) use ($revisionsByIdentifier) {
            $appliedRevision = null;
            if ($revision->getAppliedRevisionIdentifier() !== null) {
                // The applied revision is one of the same node's revisions, as long as it still exists
                $source = $revisionsByIdentifier[$revision->getAppliedRevisionIdentifier()] ?? null;
                $appliedRevision = [
                    'identifier' => $revision->getAppliedRevisionIdentifier(),
                    'label' => $source ? $source->getLabel() : '',
                    'creationDateTime' => $revision->getAppliedRevisionCreationDateTime(),
                ];
            }
            return [
                'identifier' => $revision->getIdentifier(),
                'label' => $revision->getLabel(),
                'nodeIdentifier' => $revision->getNodeIdentifier(),
                'creator' => $revision->getCreator(),
                'creationDateTime' => $revision->getCreationDateTime(),
                'isEmpty' => $revision->isEmpty(),
                'isMoved' => $revision->isMoved(),
                'appliedRevision' => $appliedRevision,
            ];
        }, $nodeRevisions);

        $this->view->assign('value', [
            'revisions' => $revisions,
        ]);
    }

    /**
     * @param array $resolutions By node identifier, sent in the JSON body, see RevisionService::validateRevision()
     * @throws StopActionException
     */
    public function applyAction(NodeInterface $node = null, Revision $revision = null, array $resolutions = []): void
    {
        if (!$node) {
            $this->throwStatus(404, $this->translate('error.nodeNotFound', 'Page not found'));
        }

        if (!$revision) {
            $this->throwStatus(404, $this->translate('error.revisionNotFound', 'Revision not found'));
        }

        // Checked before applying, which already recreates deleted assets while reading the revision
        $validation = $this->revisionService->validateRevision($revision, $resolutions);
        if (!$validation['isApplicable']) {
            $this->throwStatus(422, $this->translate('error.revisionNotApplicable', 'Revision cannot be applied'), json_encode(['rows' => $validation['rows'], 'errors' => $validation['errors']], JSON_PRETTY_PRINT));
        }

        try {
            $result = $this->revisionService->applyRevision($revision->getIdentifier(), $node->getParentPath(), $resolutions);
        } catch (RevisionApplyDeniedException $exception) {
            $this->throwStatus(403, $this->translate('error.revisionApplyDenied', 'Not allowed to apply the revision'), json_encode(['rows' => [], 'errors' => [$exception->getMessage()]], JSON_PRETTY_PRINT));
        } catch (RevisionNotApplicableException $exception) {
            $this->throwStatus(422, $this->translate('error.revisionNotApplicable', 'Revision cannot be applied'), json_encode(['rows' => $exception->getRows(), 'errors' => $exception->getErrors()], JSON_PRETTY_PRINT));
        }

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

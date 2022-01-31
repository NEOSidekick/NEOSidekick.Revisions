<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService;
use Neos\ContentRepository\Exception\ImportException;
use Neos\Flow\Annotations as Flow;
use CodeQ\Revisions\Domain\Model\Revision;
use CodeQ\Revisions\Domain\Repository\RevisionRepository;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Security\Context;

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

    public function createRevision(NodeInterface $node): ?Revision
    {
        $xmlWriter = $this->nodeExportService->export($node->getPath());

        $creator = $this->securityContext->canBeInitialized() ? $this->securityContext->getAccount() : null;

        $revision = new Revision(
            $node->getIdentifier(),
            $creator ? $creator->getAccountIdentifier() : 'CLI',
            $xmlWriter->flush()
        );

        try {
            $this->revisionRepository->add($revision);
        } catch (IllegalObjectTypeException $e) {
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
            return false;
        }

        try {
            $this->nodeImportService->import($revision->getContent(), $nodePath);
        } catch (ImportException $e) {
            return false;
        }
        return true;
    }

}

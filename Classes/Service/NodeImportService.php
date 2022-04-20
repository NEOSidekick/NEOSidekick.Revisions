<?php
declare(strict_types=1);

/**
 * This file is part of the CodeQ.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace CodeQ\Revisions\Service;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeImportService extends \Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService
{

    /** @var array<string> */
    protected $persistedNodeIdentifiers = [];

    protected $nodesInImport = [];

    protected $logNodesInImport = false;

    /**
     * Persist the nodedata like the parent class does but store the persisted node identifier for later use.
     * If `logNodesInImport` is set to true, the node data will be logged to the console instead of being persisted.
     *
     * @inheritDoc
     */
    protected function persistNodeData($nodeData): void
    {
        if ($this->logNodesInImport) {
            $this->nodesInImport[] = $nodeData;
        } else {
            $this->persistedNodeIdentifiers[] = $nodeData['identifier'];
            parent::persistNodeData($nodeData);
        }
    }

    /**
     * @return array<string>
     */
    public function getPersistedNodeIdentifiers(): array
    {
        return $this->persistedNodeIdentifiers;
    }

    public function getNodesInImport(\XMLReader $xmlReader, $targetPath, $resourceLoadPath = null): array
    {
        $this->persistedNodeIdentifiers = [];
        $this->nodesInImport = [];
        $this->logNodesInImport = true;
        try {
            $this->import($xmlReader, $targetPath, $resourceLoadPath);
        } catch (\Exception $e) {
            // do nothing
        }
        return $this->nodesInImport;
    }
}

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

    /**
     * Persist the nodedata like the parent class does but store the persisted node identifier for later use.
     *
     * @inheritDoc
     */
    protected function persistNodeData($nodeData): void
    {
        $this->persistedNodeIdentifiers[] = $nodeData['identifier'];
        parent::persistNodeData($nodeData);
    }

    /**
     * @return array<string>
     */
    public function getPersistedNodeIdentifiers(): array
    {
        return $this->persistedNodeIdentifiers;
    }

}

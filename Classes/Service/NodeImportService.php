<?php
declare(strict_types=1);

/**
 * This file is part of the NEOSidekick.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace NEOSidekick\Revisions\Service;

use Neos\ContentRepository\Exception\ImportException;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeImportService extends \Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService
{

    protected $nodesInImport = [];

    /**
     * Collects the node data instead of writing it into the live workspace with SQL,
     * revisions are applied through a workspace, see RevisionService::applyRevision()
     *
     * @inheritDoc
     */
    protected function persistNodeData($nodeData): void
    {
        $this->nodesInImport[] = $nodeData;
    }

    /**
     * Returns referenced objects that still exist unchanged instead of mapping the exported data onto them, which would
     * reset shared assets to their state in the revision and clear their thumbnails (see https://github.com/NEOSidekick/NEOSidekick.Revisions/issues/18)
     *
     * @inheritDoc
     */
    protected function convertElementToValue(\XMLReader $reader, $currentType, $currentEncoding, $currentClassName, $currentNodeIdentifier, $currentProperty)
    {
        $decodedJson = $currentType === 'object' && $currentEncoding === 'json' ? json_decode($reader->value, true) : null;
        if (!is_array($decodedJson)) {
            return parent::convertElementToValue($reader, $currentType, $currentEncoding, $currentClassName, $currentNodeIdentifier, $currentProperty);
        }

        $existingObject = $this->findExistingObject($decodedJson, $currentClassName);
        if ($existingObject !== null) {
            return $existingObject;
        }

        // A recreated object, e.g. a deleted image variant, must reference its still existing original without rewriting it
        $value = $this->propertyMapper->convert($this->reduceExistingObjectsToIdentity($decodedJson), $currentClassName, $this->propertyMappingConfiguration);
        if ($this->propertyMapper->getMessages()->hasErrors()) {
            throw new ImportException(sprintf('Could not convert element <%s> to %s for node %s', $currentProperty, $currentClassName, $currentNodeIdentifier), 1472992034);
        }
        $this->persistEntities($value);
        return $value;
    }

    protected function findExistingObject(array $source, ?string $className): ?object
    {
        if (!isset($source['__identity']) || $className === null) {
            return null;
        }
        return $this->persistenceManager->getObjectByIdentifier($source['__identity'], $className);
    }

    protected function reduceExistingObjectsToIdentity(array $source): array
    {
        foreach ($source as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $source[$key] = $this->findExistingObject($value, $value['__type'] ?? null) !== null
                ? ['__identity' => $value['__identity'], '__type' => $value['__type']]
                : $this->reduceExistingObjectsToIdentity($value);
        }
        return $source;
    }

    /**
     * @return array<array> The node data of all variants, parents before their children
     * @throws ImportException if the XML cannot be read completely
     */
    public function parseNodes(\XMLReader $xmlReader, string $targetPath): array
    {
        $this->nodesInImport = [];
        $this->import($xmlReader, $targetPath);
        return $this->nodesInImport;
    }

    public function getNodesInImport(\XMLReader $xmlReader, $targetPath, $resourceLoadPath = null): array
    {
        $this->nodesInImport = [];
        try {
            $this->import($xmlReader, $targetPath, $resourceLoadPath);
        } catch (\Exception $e) {
            // do nothing
        }
        return $this->nodesInImport;
    }
}

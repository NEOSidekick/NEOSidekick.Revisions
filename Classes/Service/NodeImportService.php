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
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class NodeImportService extends \Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService
{

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    protected $nodesInImport = [];

    /**
     * Only set while a revision is parsed to be applied, a diff never recreates deleted objects
     *
     * @var bool
     */
    protected $restoreDeletedAssets = false;

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

        if (!$this->restoreDeletedAssets) {
            return $this->findStandInForDeletedAsset($decodedJson);
        }
        $decodedJson = $this->restoreContentOfDeletedAssets($decodedJson);

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
     * Gives deleted assets whose content is still stored, because another resource has the same SHA1, a new resource
     * with that content, so they can be recreated. Assets whose content is gone are left to the regular conversion.
     */
    protected function restoreContentOfDeletedAssets(array $source): array
    {
        if (isset($source['__identity'], $source['__type'], $source['resource']['sha1']) && $this->findExistingObject($source, $source['__type']) === null) {
            $restoredResource = $this->importStoredContent($source['resource']);
            if ($restoredResource !== null) {
                $this->logger->info(sprintf('Recreating deleted asset %s from a revision with the stored content of "%s" (%s)', $source['__identity'], $restoredResource->getFilename(), $restoredResource->getSha1()));
                $source['resource'] = $restoredResource;
            }
        }
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $source[$key] = $this->restoreContentOfDeletedAssets($value);
            }
        }
        return $source;
    }

    protected function importStoredContent(array $exportedResource): ?PersistentResource
    {
        $collectionName = $exportedResource['collectionName'] ?? ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME;
        $resourcesWithSameContent = $this->resourceRepository->findBySha1AndCollectionName($exportedResource['sha1'], $collectionName);
        $stream = $resourcesWithSameContent !== [] ? $resourcesWithSameContent[0]->getStream() : false;
        if (!is_resource($stream)) {
            return null;
        }

        try {
            // An asset owns its resource exclusively, so the content is imported as a new resource; storage deduplicates it by SHA1
            $resource = $this->resourceManager->importResource($stream, $collectionName);
        } catch (\Exception $exception) {
            $this->logger->warning(sprintf('Could not restore the stored content %s: %s', $exportedResource['sha1'], $exception->getMessage()));
            return null;
        } finally {
            fclose($stream);
        }
        $resource->setFilename($exportedResource['filename'] ?? $resource->getFilename());
        return $resource;
    }

    /**
     * Finds an existing asset to show a deleted one in a diff: a deleted image variant is shown by its original,
     * a deleted asset by an asset with the same content. Without one, the value is shown as missing.
     */
    protected function findStandInForDeletedAsset(array $source): ?AssetInterface
    {
        if (isset($source['originalAsset']) && is_array($source['originalAsset'])) {
            $original = $this->findExistingObject($source['originalAsset'], $source['originalAsset']['__type'] ?? null);
            return $original instanceof AssetInterface ? $original : $this->findStandInForDeletedAsset($source['originalAsset']);
        }
        return isset($source['resource']['sha1']) ? $this->assetRepository->findOneByResourceSha1($source['resource']['sha1']) : null;
    }

    /**
     * @return array<array> The node data of all variants, parents before their children
     * @throws ImportException if the XML cannot be read completely
     */
    public function parseNodes(\XMLReader $xmlReader, string $targetPath): array
    {
        $this->nodesInImport = [];
        $this->restoreDeletedAssets = true;
        try {
            $this->import($xmlReader, $targetPath);
        } finally {
            $this->restoreDeletedAssets = false;
        }
        return $this->nodesInImport;
    }

    public function getNodesInImport(\XMLReader $xmlReader, $targetPath, $resourceLoadPath = null): array
    {
        $this->nodesInImport = [];
        $this->restoreDeletedAssets = false;
        try {
            $this->import($xmlReader, $targetPath, $resourceLoadPath);
        } catch (\Exception $e) {
            // do nothing
        }
        return $this->nodesInImport;
    }
}

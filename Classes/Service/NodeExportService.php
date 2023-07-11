<?php
declare(strict_types=1);

namespace NEOSidekick\Revisions\Service;

/**
 * This file is part of the NEOSidekick.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ImportExport\ImportExportPropertyMappingConfiguration;
use Neos\Flow\Annotations as Flow;

/**
 * Service for exporting content repository nodes as an XML structure
 *
 * This service extends the Neos core functionality but only exports a node with all its content type childnodes instead
 * of also including other children of type Neos.Neos:Document.
 *
 * @Flow\Scope("singleton")
 */
class NodeExportService extends \Neos\ContentRepository\Domain\Service\ImportExport\NodeExportService
{
    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    public function export(
        $startingPointNodePath = '/',
        $workspaceName = 'live',
        \XMLWriter $xmlWriter = null,
        $tidy = false,
        $endDocument = true,
        $resourceSavePath = 'SKIP',
        $nodeTypeFilter = null
    ): \XMLWriter
    {
        $this->propertyMappingConfiguration = new ImportExportPropertyMappingConfiguration($resourceSavePath);
        $this->exceptionsDuringExport = [];
        $this->exportedNodePaths = [];
        if ($startingPointNodePath !== '/') {
            $startingPointParentPath = substr($startingPointNodePath, 0, strrpos($startingPointNodePath, '/'));
            $this->exportedNodePaths[$startingPointParentPath] = true;
        }

        $this->xmlWriter = $xmlWriter;
        if ($this->xmlWriter === null) {
            $this->xmlWriter = new \XMLWriter();
            $this->xmlWriter->openMemory();
            $this->xmlWriter->setIndent($tidy);
            $this->xmlWriter->startDocument('1.0', 'UTF-8');
        }

        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select(
            'n.path AS path,'
            . ' n.identifier AS identifier,'
            . ' n.index AS sortingIndex,'
            . ' n.properties AS properties, '
            . ' n.nodeType AS nodeType,'
            . ' n.removed AS removed,'
            . ' n.hidden,'
            . ' n.hiddenBeforeDateTime AS hiddenBeforeDateTime,'
            . ' n.hiddenAfterDateTime AS hiddenAfterDateTime,'
            . ' n.creationDateTime AS creationDateTime,'
            . ' n.lastModificationDateTime AS lastModificationDateTime,'
            . ' n.lastPublicationDateTime AS lastPublicationDateTime,'
            . ' n.hiddenInIndex AS hiddenInIndex,'
            . ' n.accessRoles AS accessRoles,'
            . ' n.version AS version,'
            . ' n.parentPath AS parentPath,'
            . ' n.pathHash AS pathHash,'
            . ' n.dimensionsHash AS dimensionsHash,'
            . ' n.parentPathHash AS parentPathHash,'
            . ' n.dimensionValues AS dimensionValues,'
            . ' w.name AS workspace'
        )->distinct()
            ->from(NodeData::class, 'n')
            ->innerJoin('n.workspace', 'w', 'WITH', 'n.workspace=w.name')
            ->where('n.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->andWhere('n.path = :pathPrefix')
            ->setParameter('pathPrefix', $startingPointNodePath)
            ->orderBy('n.identifier', 'ASC')
            ->orderBy('n.path', 'ASC');

        $startingPointNodeData = $queryBuilder->getQuery()->getResult();

        $this->securityContext->withoutAuthorizationChecks(function () use ($startingPointNodePath, $startingPointNodeData, $workspace, $workspaceName, $nodeTypeFilter) {
            $nodes = $this->nodeService->findContentNodes($startingPointNodePath, $workspace, false);

            $combinedNodeDataList = array_reduce(
                $nodes,
                function ($nodeDataList, NodeData $nodeData) use ($workspaceName, $nodeTypeFilter) {
                    return array_merge($nodeDataList, $this->findNodeDataListToExport($nodeData->getPath(), $workspaceName, $nodeTypeFilter));
                },
                $startingPointNodeData
            );

            usort(
                $combinedNodeDataList,
                static function ($node1, $node2) {
                    return strcmp(
                        str_replace("/", "!", $node1['path']),
                        str_replace("/", "!", $node2['path'])
                    );
                }
            );

            $this->exportNodeDataList($combinedNodeDataList);
        });

        if ($endDocument) {
            $this->xmlWriter->endDocument();
        }

        $this->handleExceptionsDuringExport();

        return $this->xmlWriter;
    }

    protected function handleExceptionsDuringExport(): void
    {
        // Noop, we ignore the issue and the errors were already logged before
    }

    /**
     * @inheritDoc
     *
     * Adds special handling for boolean properties as false values are being dropped during export (see https://github.com/code-q-web-factory/NEOSidekick.Revisions/issues/13)
     */
    protected function writeConvertedElement(array &$data, $propertyName, $elementName = null, $declaredPropertyType = null): void
    {
        if ($declaredPropertyType !== 'boolean') {
            parent::writeConvertedElement($data, $propertyName, $elementName, $declaredPropertyType);
        } else if (array_key_exists($propertyName, $data) && $data[$propertyName] !== null) {
            $propertyValue = $data[$propertyName];
            $this->xmlWriter->startElement($elementName ?: $propertyName);
            $this->xmlWriter->writeAttribute('__type', gettype($propertyValue));
            $this->xmlWriter->text($propertyValue ? '1' : '0');
            $this->xmlWriter->endElement();
        }
    }

}

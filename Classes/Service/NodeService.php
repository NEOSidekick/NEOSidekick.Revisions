<?php

declare(strict_types=1);


/**
 * This file is part of the NEOSidekick.Revisions package.
 *
 * (c) 2022 Code Q
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace NEOSidekick\Revisions\Service;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeService
{

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @return array<NodeData>
     */
    public function findContentNodes(string $startingPointNodePath, Workspace $workspace, bool $recursive = true): array
    {
        $directChildNodes = $this->findChildrenNodes($startingPointNodePath, $workspace);

        if ($recursive) {
            $subNodes = array_map(
                function (NodeData $node) use ($workspace) {
                    return $this->findChildrenNodes($node->getPath(), $workspace, true);
                },
                $directChildNodes
            );

            return array_merge($directChildNodes, ...$subNodes);
        }

        return $directChildNodes;
    }

    /**
     * @return array<NodeData>
     */
    protected function findChildrenNodes(string $startingPointNodePath, Workspace $workspace, bool $recursive = false): array
    {
        // Get all possible dimensions and their values to prevent empty revisions that sometimes occur
        // when the CR mixes up the dimensions while fetching the nodes
        // See https://github.com/neos/neos-development-collection/blob/8.3/Neos.ContentRepository/Classes/Domain/Repository/NodeDataRepository.php#L1668
        $dimensionCombinations = $this->contentDimensionCombinator->getAllAllowedCombinations();
        $dimensionValues = [];
        foreach ($dimensionCombinations as $combination) {
            foreach ($combination as $dimensionName => $dimensionValue) {
                $dimensionValues[$dimensionName][] = $dimensionValue[0];
            }
        }

        return $this->nodeDataRepository->findByParentAndNodeType(
            $startingPointNodePath,
            'Neos.Neos:Content,Neos.Neos:ContentCollection',
            $workspace,
            $dimensionValues,
            false,
            $recursive
        );
    }
}

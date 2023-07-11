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
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

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
        return $this->nodeDataRepository->findByParentAndNodeType(
            $startingPointNodePath,
            'Neos.Neos:Content,Neos.Neos:ContentCollection',
            $workspace,
            null,
            false,
            $recursive
        );
    }

    /**
     * @param array<NodeData> $unknownNodes
     */
    public function removeNodes(array $unknownNodes): void
    {
        foreach ($unknownNodes as $unknownNode) {
            $this->logger->warning(sprintf('Removing unknown node "%s" during revision application', $unknownNode->getPath()));
            $this->nodeDataRepository->remove($unknownNode);
        }
    }
}

<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Domain\Repository;

/**
 * This file is part of the CodeQ.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Annotations as Flow;
use CodeQ\Revisions\Domain\Model\Revision;

/**
 * @method QueryResultInterface findByNodeIdentifier(string $nodeIdentifier)
 * @method Revision findByIdentifier(string $identifier)
 *
 * @Flow\Scope("singleton")
 */
class RevisionRepository extends Repository
{

    /**
     * @var array
     */
    protected $defaultOrderings = [
        'creationDateTime' => 'DESC'
    ];

    public function removeAllOlderThan(\DateTime $since): int
    {
        $query = $this->createQuery();
        $oldRevisions = $query->matching($query->lessThan('creationDateTime', $since))->execute();

        foreach ($oldRevisions as $oldRevision) {
            $this->remove($oldRevision);
        }

        return count($oldRevisions);
    }

}

<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Domain\Repository;

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

}

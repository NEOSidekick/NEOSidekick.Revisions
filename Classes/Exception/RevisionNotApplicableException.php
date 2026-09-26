<?php
declare(strict_types=1);

namespace NEOSidekick\Revisions\Exception;

/**
 * This file is part of the NEOSidekick.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The revision cannot be applied, not even when forced, e.g. because a node type no longer exists
 */
class RevisionNotApplicableException extends \Neos\Flow\Exception
{
    /**
     * @var array<string>
     */
    protected $problems;

    /**
     * @param array<string> $problems
     */
    public function __construct(array $problems)
    {
        parent::__construct(implode("\n", $problems), 1790500001);
        $this->problems = $problems;
    }

    /**
     * @return array<string>
     */
    public function getProblems(): array
    {
        return $this->problems;
    }
}

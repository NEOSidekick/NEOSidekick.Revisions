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
 * The revision cannot be applied without further resolutions, or not at all, e.g. because a node type no longer exists
 */
class RevisionNotApplicableException extends \Neos\Flow\Exception
{
    /**
     * @var array<array>
     */
    protected $rows;

    /**
     * @var array<string>
     */
    protected $errors;

    /**
     * @param array<array> $rows The nodes that need a resolution and those that got one, see RevisionService::validateRevision()
     * @param array<string> $errors Problems no resolution can fix
     */
    public function __construct(array $rows, array $errors)
    {
        $messages = $errors;
        foreach ($rows as $row) {
            if ($row['resolution'] === null) {
                $messages = array_merge($messages, array_column($row['problems'], 'message'));
            }
        }
        parent::__construct(implode("\n", $messages), 1790500001);
        $this->rows = $rows;
        $this->errors = $errors;
    }

    /**
     * @return array<array>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

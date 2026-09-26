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
 * The editor lacks a node privilege for a change the revision would make
 */
class RevisionApplyDeniedException extends \Neos\Flow\Exception
{
}

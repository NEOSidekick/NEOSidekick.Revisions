<?php
declare(strict_types=1);

namespace CodeQ\Revisions;

/**
 * This file is part of the CodeQ.Revisions package.
 *
 * (c) 2022 CodeQ
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use CodeQ\Revisions\Service\RevisionService;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Neos\Service\PublishingService;

class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap): void
    {
        $bootstrap->getSignalSlotDispatcher()->connect(ConfigurationManager::class, 'configurationManagerReady', function (ConfigurationManager $configurationManager) use ($bootstrap) {
            $isEnabled = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'CodeQ.Revisions.enabled');

            if ($isEnabled) {
                $dispatcher = $bootstrap->getSignalSlotDispatcher();
                $dispatcher->connect(
                    PublishingService::class, 'nodePublished',
                    RevisionService::class, 'registerNodeChange'
                );
                $dispatcher->connect(
                    Workspace::class, 'beforeNodePublishing',
                    RevisionService::class, 'registerNodeBeforePublishing'
                );
            }
        });
    }
}

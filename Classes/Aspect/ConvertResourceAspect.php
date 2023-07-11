<?php

declare(strict_types=1);

namespace NEOSidekick\Revisions\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\ArrayConverter;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * @Flow\Aspect
 */
class ConvertResourceAspect
{

    /**
     * Skips writing resource data anywhere if it should be skipped according to configuration
     *
     * @Flow\Around("method(Neos\Flow\Property\TypeConverter\ArrayConverter->convertFrom())")
     */
    public function skipResourceDataInConversion(JoinPointInterface $joinPoint)
    {
        $source = $joinPoint->getMethodArgument('source');
        /** @var ?PropertyMappingConfigurationInterface $configuration */
        $configuration = $joinPoint->getMethodArgument('configuration');

        if ($source instanceof PersistentResource && $configuration !== null) {
            $savePath = $configuration->getConfigurationValue(ArrayConverter::class, ArrayConverter::CONFIGURATION_RESOURCE_SAVE_PATH);

            if ($savePath === 'SKIP') {
                // We don't write the file content anywhere as we don't want to store it in the database and
                // we assume that all files are already present in the system.
                return [
                    'filename' => $source->getFilename(),
                    'collectionName' => $source->getCollectionName(),
                    'relativePublicationPath' => $source->getRelativePublicationPath(),
                    'mediaType' => $source->getMediaType(),
                    'sha1' => $source->getSha1(),
                    'hash' => $source->getSha1(),
                ];
            }
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

}

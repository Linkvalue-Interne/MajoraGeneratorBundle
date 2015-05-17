<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifierInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Abstract class for content modifiers.
 */
abstract class AbstractContentModifier implements ContentModifierInterface
{
    protected $kernelDir;
    protected $fileLocator;

    /**
     * abstract class setting up method
     *
     * @param string               $kernelDir
     * @param FileLocatorInterface $fileLocator
     */
    public function setUp(
        $kernelDir,
        FileLocatorInterface $fileLocator
    )
    {
        $this->kernelDir   = realpath($kernelDir);
        $this->fileLocator = $fileLocator;
    }

    /**
     * resolve given target file path
     *
     * @param  string $target
     * @param  string $basePath
     * @return string
     * @throws InvalidArgumentException if path cannot be resolved
     */
    protected function resolveTargetFilePath($target, $basePath)
    {
        switch (true) {

            // resource
            case strpos($target, '@') === 0:
                $targetPath = $this->fileLocator->locate($target);
                break;

            // kernel related
            case strpos($target, '/') === 0:
                $targetPath = sprintf('%s%s',
                    $this->kernelDir,
                    $target
                );
                break;

            // related file
            default:
                $targetPath = sprintf('%s/%s', $basePath, $target);
                break;
        }

        if (!is_writable($targetPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Unavailable to resolve "%s" target, resolved into "%s" file path which is unwritable.',
                $target,
                $targetPath
            ));
        }

        return $targetPath;
    }
}

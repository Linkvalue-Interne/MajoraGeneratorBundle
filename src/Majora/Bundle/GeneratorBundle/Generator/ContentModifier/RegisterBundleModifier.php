<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifier\AbstractContentModifier;
use Majora\Framework\Inflector\Inflector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Service for updating kernel from a bundle class.
 */
class RegisterBundleModifier extends AbstractContentModifier
{
    protected $filesystem;
    protected $logger;
    protected $resolver;

    /**
     * construct.
     *
     * @param Filesystem      $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->logger     = $logger;
        $this->filesystem = $filesystem;

        $this->resolver = new OptionsResolver();
        $this->resolver->setDefaults(array(
            'target'          => '/AppKernel.php',
            'kernel_filename' => 'AppKernel.php'
        ));
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile)
    {
        $options     = $this->resolver->resolve($data);
        $fileContent = $generatedFile->getContents();

        // current file is a bundle ?
        $isInBundle = preg_match(
            sprintf('/namespace (.*%s.*Bundle);/', $inflector->translate('MajoraNamespace')),
            $fileContent,
            $inBundleMatches
        );
        $isABundle = preg_match(
            sprintf(
                '/class (([\w]*)%s[\w]*Bundle) extends [\w]*Bundle/',
                $inflector->translate('MajoraNamespace')
            ),
            $fileContent,
            $isBundleMatches
        );
        if (!$isInBundle || !$isABundle) {
            $this->logger->notice(sprintf(
                'Try to register "%s" file into Kernel which isnt a bundle. Abording.',
                $generatedFile->getFilename()
            ));

            return $fileContent;
        }

        $bundleInclusion = sprintf('new %s\\%s(),',
            $inBundleMatches[1], $isBundleMatches[1]
        );

        $kernelFile = new SplFileInfo(
            $this->resolveTargetFilePath($options['target'], $generatedFile->getPath()),
            '', ''
        );

        $kernelContent = $kernelFile->getContents();

        // is bundle not already registered ?
        if (strpos($kernelContent, $bundleInclusion) !== false) {
            $this->logger->debug(sprintf(
                'Bundle "%s" is already registered. Abording.',
                $generatedFile->getFilename()
            ));
            return $fileContent;
        }

        $this->filesystem->dumpFile(
            $kernelFile->getPathname(),
            preg_replace(
                '/(Bundle\(\)\,)(\n[\s]+\);)/',
                sprintf("$1
            %s$2",
                    $bundleInclusion
                ),
                $kernelContent
            )
        );

        $this->logger->info(sprintf('file updated : %s',
            $kernelFile->getPathname()
        ));

        return $fileContent;
    }
}

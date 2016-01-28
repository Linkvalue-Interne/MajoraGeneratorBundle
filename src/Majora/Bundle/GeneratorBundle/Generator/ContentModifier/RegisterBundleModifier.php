<?php

namespace Majora\Bundle\GeneratorBundle\Generator\ContentModifier;

use Majora\Framework\Inflector\Inflector;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
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
    protected $environment;
    protected $debug;
    protected $resolver;

    /**
     * construct.
     *
     * @param Filesystem      $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $filesystem, LoggerInterface $logger, $environment, $debug)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->environment = $environment;
        $this->debug = $debug;

        $this->resolver = new OptionsResolver();
        $this->resolver->setDefaults(array(
            'target'          => '\\AppKernel',
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

        $kernelManipulator = new KernelManipulator(
            new $options['target']($this->environment, $this->debug)
        );

        try {
            $kernelManipulator->addBundle(sprintf('%s\\%s',
                $inBundleMatches[1],
                $isBundleMatches[1]
            ));
        } catch (\RuntimeException $e) {
            $this->logger->debug(sprintf(
                'Bundle "%s" is already registered. Abording.',
                $generatedFile->getFilename()
            ));

            return $fileContent;
        }

        $this->logger->info(sprintf('kernel updated : %s',
            $kernelManipulator->getFilename()
        ));

        return $fileContent;
    }
}

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
class RegisterBundleModifier extends AbstractPhpClassContentModifier
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
        $options    = $this->resolver->resolve($data);
        $bundleInfo = $this->retrieveBundleInfoFromGeneratedFile($generatedFile, $inflector);

        $kernelManipulator = new KernelManipulator(
            new $options['target']($this->environment, $this->debug)
        );

        try {
            $kernelManipulator->addBundle($bundleInfo->getFQCN());
        } catch (\RuntimeException $e) {
            $this->logger->debug(sprintf(
                'Bundle "%s" is already registered. Aborting.',
                $bundleInfo->getFQCN()
            ));

            return $generatedFile->getContents();
        }

        $this->logger->info(sprintf('kernel updated : %s',
            $kernelManipulator->getFilename()
        ));

        return $generatedFile->getContents();
    }
}

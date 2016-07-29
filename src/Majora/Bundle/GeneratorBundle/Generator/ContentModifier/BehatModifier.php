<?php

namespace Majora\Bundle\GeneratorBundle\Generator\ContentModifier;

use Majora\Framework\Inflector\Inflector;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Service for updating behat.yml
 */
class BehatModifier extends AbstractPhpClassContentModifier
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
            'target' => '/config/behat.yml',
        ));
        $this->resolver->setRequired(array(
            'target',
            'path',
            'context',
            'domain',
            'loader',
        ));
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile)
    {
        $options    = $this->resolver->resolve($data);
        $bundleInfo = $this->retrieveBundleInfoFromGeneratedFile($generatedFile, $inflector);

        $targetConfigFilepath = $this->resolveTargetFilePath(
            $options['target'],
            $generatedFile->getPath()
        );

        var_dump($options);
        $configsFile = new SplFileInfo($targetConfigFilepath, '', '');
        $configsContent = $configsFile->getContents();

        $namespaceArgs = explode('\\', $bundleInfo->getNamespace());

        $behatBundle = sprintf('suites:
        %1$s:
            type: symfony_bundle
            bundle: %1$s
            paths:
                - %2$s
            contexts:
                - %3$s:
                    domain: \'@%4$s\'
                    loader: \'@%5$s\'
                    em: \'@doctrine.orm.entity_manager\'',
            $bundleInfo->getClassName(),
            $options['path'],
            $options['context'],
            $options['domain'],
            $options['loader']
        );

        $this->filesystem->dumpFile(
            $configsFile->getPathname(),
            str_replace('suites:', $behatBundle, $configsContent)
        );

        $this->logger->info(sprintf('===========>file updated : %s',
            $configsFile->getPathname()
        ));

        return $generatedFile->getContents();
    }
}

<?php

namespace Majora\Bundle\GeneratorBundle\Generator\ContentModifier;

use Majora\Framework\Inflector\Inflector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Service for updating doctrine em config with current bundle.
 */
class RegisterDoctrineEmModifier extends AbstractContentModifier
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
            'target'                    => '/config/config.yml',
            'relative_schema_directory' => 'Resources/config/doctrine',
        ));
        $this->resolver->setRequired(array(
            'em', 'prefix', 'bundle', 'alias'
        ));
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile)
    {
        $options = $this->resolver->resolve($data);

        // retrieve target location
        $targetConfigFilepath = $this->resolveTargetFilePath(
            $options['target'],
            $generatedFile->getPath()
        );

        $emBundleDefinition = sprintf('
                    %s:
                        type: yml
                        dir: %s
                        prefix: %s
                        alias: %s
                        ',
            $options['bundle'],
            $options['relative_schema_directory'],
            $options['prefix'],
            $options['alias']
        );

        $configsFile = new SplFileInfo($targetConfigFilepath, '', '');
        $configsContent = $configsFile->getContents();

        // are configs not already registered ?
        if (strpos($configsContent, trim($emBundleDefinition)) !== false) {
            $this->logger->debug(sprintf(
                'Config file "%s" is already registered into "%s". Abording.',
                $generatedFile->getFilename(),
                $targetConfigFilepath
            ));

            return $generatedFile->getContents();
        }

        $this->filesystem->dumpFile(
            $configsFile->getPathname(),
            str_replace(
                sprintf('
        entity_managers:
            %s:
                mappings:',
                    $options['em']
                ),
                sprintf('
        entity_managers:
            %s:
                mappings:%s',
                    $options['em'],
                    $emBundleDefinition
                ),
                $configsContent
            )
        );

        $this->logger->info(sprintf('file updated : %s',
            $configsFile->getPathname()
        ));

        return $generatedFile->getContents();
    }
}

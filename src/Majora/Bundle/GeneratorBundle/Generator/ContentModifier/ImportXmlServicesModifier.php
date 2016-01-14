<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifier\AbstractContentModifier;
use Majora\Framework\Inflector\Inflector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Modifier which importe services xml files into another
 */
class ImportXmlServicesModifier extends AbstractContentModifier
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
            'target' => '../services.xml'
        ));
        $this->resolver->setRequired(array(
            'resource'
        ));
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile)
    {
        $options = $this->resolver->resolve($data);

        // retrieve target location
        $targetServicesFilepath = $this->resolveTargetFilePath(
            $options['target'],
            $generatedFile->getPath()
        );

        // build content
        $import = sprintf('<import resource="%s" />',
            $inflector->translate($options['resource'])
        );

        $servicesFile = new SplFileInfo($targetServicesFilepath, '', '');
        $servicesContent = $servicesFile->getContents();

        // are services not already registered ?
        if (strpos($servicesContent, $import) !== false) {
            $this->logger->debug(sprintf(
                'Service file "%s" is already registered into "%s". Abording.',
                $generatedFile->getFilename(),
                $targetServicesFilepath
            ));

            return $generatedFile->getContents();
        }

        $this->filesystem->dumpFile(
            $servicesFile->getRealpath(),
            str_replace(
                '    </imports>',
                sprintf("        %s\n    </imports>", $import),
                $servicesContent
            )
        );

        $this->logger->info(sprintf('file updated : %s',
            $servicesFile->getRealpath()
        ));

        return $generatedFile->getContents();
    }
}

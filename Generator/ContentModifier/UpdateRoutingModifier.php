<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifier\AbstractContentModifier;
use Majora\GeneratorBundle\Generator\Inflector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Service for updating main routing from a bundle routing file.
 */
class UpdateRoutingModifier extends AbstractContentModifier
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
    public function __construct(
        Filesystem      $filesystem,
        LoggerInterface $logger
    )
    {
        $this->filesystem  = $filesystem;
        $this->logger      = $logger;

        $this->resolver = new OptionsResolver();
        $this->resolver->setDefaults(array(
            'target' => '/config/routing.yml',
            'prefix' => null,
        ));
        $this->resolver->setRequired(array(
            'resource', 'route'
        ));
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile)
    {
        $options = $this->resolver->resolve($data);

        // retrieve target location
        $targetRoutingFilepath = $this->resolveTargetFilePath(
            $options['target'],
            $generatedFile->getPath()
        );

        // build content
        $routing = sprintf('
%s:
    resource: "%s"
    %s',
            $inflector->translate($options['route']),
            $inflector->translate($options['resource']),
            is_null($options['prefix']) ? '' : sprintf(
                "prefix: %s\n",
                $inflector->translate($options['prefix'])
            )
        );

        $routingFile = new SplFileInfo($targetRoutingFilepath, '', '');
        $routingContent = $routingFile->getContents();

        // is routing not already registered ?
        if (strpos($routingContent, trim($routing)) !== false) {
            $this->logger->debug(sprintf(
                'Routing file "%s" is already registered into "%s". Abording.',
                $generatedFile->getFilename(),
                $targetRoutingFilepath
            ));

            return $generatedFile->getContents();
        }

        $this->filesystem->dumpFile(
            $routingFile->getRealpath(),
            sprintf('%s%s', $routingContent, $routing)
        );

        $this->logger->info(sprintf('file updated : %s',
            $routingFile->getRealpath()
        ));

        return $generatedFile->getContents();
    }
}

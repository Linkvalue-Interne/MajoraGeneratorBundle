<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifierInterface;
use Majora\GeneratorBundle\Generator\Inflector;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Service for updating main routing from a bundle routing file.
 */
class UpdateRoutingModifier
    implements ContentModifierInterface
{
    protected $routingPath;
    protected $logger;

    protected $currentBundleClass;

    /**
     * construct.
     *
     * @param string          $routingPath
     * @param LoggerInterface $logger
     */
    public function __construct($routingPath, LoggerInterface $logger)
    {
        $this->routingPath = realpath($routingPath);
        $this->logger      = $logger;
    }

    /**
     * @see ContentModifierInterface::supports()
     */
    public function supports(SplFileInfo $fileinfo, $currentContent, Inflector $inflector)
    {
        $this->currentBundleClass = null;

        if (!preg_match(
            '/(.*\/[\w]*Bundle)\/.*\.yml/',
            $inflector->unixizePath($fileinfo->getRealpath()),
            $matches
        )) {
            return false;
        }

        $finder = iterator_to_array((new Finder())
            ->in($matches[1])
            ->depth('== 0')
            ->name('*Bundle.php')
        );

        $this->currentBundleClass = $inflector->translate(
            str_replace('.php', '', array_shift($finder)->getFilename())
        );

        return
            // is bundle not already referenced
            strpos(
                file_get_contents($this->routingPath),
                sprintf('%s/', $this->currentBundleClass)
            ) === false
        ;
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify($fileContent, Inflector $inflector)
    {
        file_put_contents(
            $this->routingPath,
            '
# '.$inflector->translate('MajoraNamespace').' bundle routing
'.$inflector->translate('majora_namespace').'_api_routing:
    resource: "@'.$this->currentBundleClass.'/Resources/config/routing_api.yml"
',
            FILE_APPEND
        );

        $this->logger->info(sprintf('file updated : %s',
            $this->routingPath
        ));

        return $fileContent;
    }
}

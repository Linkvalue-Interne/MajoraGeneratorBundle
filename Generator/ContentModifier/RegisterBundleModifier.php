<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifierInterface;
use Majora\GeneratorBundle\Generator\Inflector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Service for updating kernel from a bundle class.
 */
class RegisterBundleModifier
    implements ContentModifierInterface
{
    protected $kernelPath;
    protected $logger;

    protected $currentBundleNamespace;
    protected $currentBundleClass;
    protected $currentNamespace;

    /**
     * construct.
     *
     * @param string          $kernelPath
     * @param LoggerInterface $logger
     */
    public function __construct($kernelPath, LoggerInterface $logger)
    {
        $this->kernelPath = $kernelPath;
        $this->logger     = $logger;
    }

    /**
     * @see ContentModifierInterface::supports()
     */
    public function supports(SplFileInfo $fileinfo, $currentContent, Inflector $inflector)
    {
        $this->currentBundleNamespace = null;
        $this->currentBundleClass     = null;
        $this->currentNamespace       = null;

        if (!preg_match(
            sprintf('/namespace (.*%s.*Bundle);/', $inflector->translate('MajoraNamespace')),
            $currentContent,
            $matches
        )) {
            return false;
        }

        $this->currentBundleNamespace = $matches[1];

        if (!preg_match(
            sprintf('/class (([\w]*)%s[\w]*Bundle) extends /', $inflector->translate('MajoraNamespace')),
            $currentContent,
            $matches
        )) {
            return false;
        }

        $this->currentBundleClass = $matches[1];
        $this->currentNamespace   = $matches[2];

        return
            // is bundle not already registered
            strpos(
                file_get_contents($this->kernelPath),
                sprintf('%s\%s()', $this->currentBundleNamespace, $this->currentBundleClass)
            ) === false
        ;
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify($fileContent, Inflector $inflector)
    {
        file_put_contents(
            $this->kernelPath,
            str_replace(
                sprintf("// %s\n", $this->currentNamespace),
                sprintf("// %s
            new %s\%s(),\n",
                    $this->currentNamespace,
                    $this->currentBundleNamespace,
                    $this->currentBundleClass
                ),
                file_get_contents($this->kernelPath)
            )
        );

        $this->logger->info(sprintf('file updated : %s',
            $this->kernelPath
        ));

        return $fileContent;
    }
}

<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifierInterface;
use Majora\GeneratorBundle\Generator\Inflector;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Service alias creation content modifier.
 */
class ConfigurationNodeModifier
    implements ContentModifierInterface
{
    /**
     * @see ContentModifierInterface::supports()
     */
    public function supports(SplFileInfo $fileinfo, $currentContent, Inflector $inflector)
    {
        return
            // is a bundle extension
            strpos(
                $inflector->translate($fileinfo->getRealPath()),
                $inflector->directorize(
                    sprintf(
                        '/%sBundle/DependencyInjection/Configuration.php',
                        $inflector->translate('MajoraNamespace')
                    )
                )
            ) !== false
            &&
            // is entity not already aliased
            strpos(
                $currentContent,
                sprintf('// %s section', $inflector->translate('MajoraEntity'))
            ) === false
        ;
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify($fileContent, Inflector $inflector)
    {
        return str_replace(
        '$rootNode
            ->children()',
        '$rootNode
            ->children()

                // '.$inflector->translate('MajoraEntity').' section
                ->append($this->createEntitySection(\''.$inflector->translate('majora_entity').'\'))',
            $fileContent
        );
    }
}

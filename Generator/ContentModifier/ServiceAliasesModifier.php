<?php

namespace Majora\GeneratorBundle\Generator\ContentModifier;

use Majora\GeneratorBundle\Generator\ContentModifierInterface;
use Majora\GeneratorBundle\Generator\Inflector;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Service alias creation content modifier.
 */
class ServiceAliasesModifier
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
                $inflector->translate($fileinfo->getFilename()),
                sprintf('%sExtension.php', $inflector->translate('MajoraNamespace'))
            ) !== false
            &&
            // is entity not already aliased
            strpos(
                $currentContent,
                sprintf('// %s aliases', $inflector->translate('MajoraEntity'))
            ) === false
        ;
    }

    /**
     * @see ContentModifierInterface::modify()
     */
    public function modify($fileContent, Inflector $inflector)
    {
        return str_replace(
        '// aliases',
        '// aliases

        // '.$inflector->translate('MajoraEntity').' aliases
        $this->registerAliases($container, \'sir.'.$inflector->translate('majora_entity').'\', $config[\''.$inflector->translate('majora_entity').'\']);',
            $fileContent
        );
    }
}

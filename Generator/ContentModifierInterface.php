<?php

namespace Majora\GeneratorBundle\Generator;

use Majora\Framework\Inflector\Inflector;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Interface for generator content modifiers.
 */
interface ContentModifierInterface
{
    /**
     * modify given content with given inflector.
     *
     * @param SplFileInfo $generatedFile
     * @param array       $data
     * @param Inflector   $inflector
     * @param SplFileInfo $templateFile
     *
     * @return string the modified content
     */
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile);
}

<?php

namespace Majora\Bundle\GeneratorBundle\Tests\Stubs\Generator\ContentModifier;

use Majora\Bundle\GeneratorBundle\Generator\ContentModifier\AbstractPhpClassContentModifier;
use Majora\Framework\Inflector\Inflector;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Stub class to test AbstractPhpClassContentModifier concrete methods.
 */
class AbstractPhpClassContentModifierStub extends AbstractPhpClassContentModifier
{
    public function modify(SplFileInfo $generatedFile, array $data, Inflector $inflector, SplFileInfo $templateFile)
    {
        // this method should be tested in each AbstractContentModifier subclasses
    }

    /**
     * Increases visibility of retrieveBundleInfoFromGeneratedFile() method to be tested
     */
    public function publicRetrieveBundleInfoFromGeneratedFile($generatedFile, $inflector)
    {
        return $this->retrieveBundleInfoFromGeneratedFile($generatedFile, $inflector);
    }

    /**
     * Increases visibility of retrievePhpClassInfoFromGeneratedFile() method to be tested
     */
    public function publicRetrievePhpClassInfoFromGeneratedFile($generatedFile, $inflector)
    {
        return $this->retrievePhpClassInfoFromGeneratedFile($generatedFile, $inflector);
    }
}

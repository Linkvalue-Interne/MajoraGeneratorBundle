<?php

namespace Majora\Bundle\GeneratorBundle\Generator\ContentModifier;

use Majora\Bundle\GeneratorBundle\FileInfo\BundleInfo;
use Majora\Bundle\GeneratorBundle\FileInfo\PhpClassInfo;
use Majora\Bundle\GeneratorBundle\Generator\Exception\UnsupportedFileForContentModifierException;
use Majora\Framework\Inflector\Inflector;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Abstract class for content modifiers.
 */
abstract class AbstractPhpClassContentModifier extends AbstractContentModifier
{
    /**
     * Retrieve information of the Bundle which contains the given file.
     *
     * @param SplFileInfo $generatedFile
     * @param Inflector $inflector
     *
     * @return BundleInfo
     *
     * @throws UnsupportedFileForContentModifierException when file is not a PHP class file
     * @throws \UnexpectedValueException when we could not retrieve bundle info from file
     */
    protected function retrieveBundleInfoFromGeneratedFile(SplFileInfo $generatedFile, Inflector $inflector)
    {
        $this->assertFileIsPhpClass($generatedFile);

        $fileContent = $generatedFile->getContents();
        $bundleInfo = new BundleInfo();

        switch(true) {

            // Handle PHP files in bundles within their own namespace
            case preg_match(
                sprintf(
                    '/namespace (.*%s.*Bundle)/',
                    $inflector->translate('MajoraNamespace')
                ),
                $fileContent,
                $matches
            ) > 0:
                $bundleInfo->setNamespace($matches[1]);
                $bundleInfo->setClassName(
                    stripslashes(
                        str_replace(
                            sprintf('%s\Bundle', $inflector->translate('MajoraNamespace')),
                            $inflector->translate('MajoraNamespace'),
                            $matches[1]
                        )
                    )
                );
                break;

            // Handle PHP files in bundles at the root of the "src" directory
            case preg_match(
                '/namespace (.*Bundle)/',
                $fileContent,
                $matches
            ) > 0:
                $bundleInfo->setNamespace($matches[1]);
                $bundleInfo->setClassName($matches[1]);
                break;

            // Could not retrieve bundle info from file
            default:
                throw new \UnexpectedValueException(sprintf(
                    'Could not retrieve bundle information from "%s" file.',
                    $generatedFile->getFilename()
                ));

        }

        return $bundleInfo;
    }

    /**
     * Retrieve PhpClass information of the given file.
     *
     * @param SplFileInfo $generatedFile
     * @param Inflector $inflector
     *
     * @return PhpClassInfo
     *
     * @throws UnsupportedFileForContentModifierException when file is not a PHP class file
     * @throws \UnexpectedValueException when file is not a PHP class
     */
    protected function retrievePhpClassInfoFromGeneratedFile(SplFileInfo $generatedFile, Inflector $inflector)
    {
        $this->assertFileIsPhpClass($generatedFile);

        if(!preg_match(
            '/namespace (\w+(?:\\\\\w+)*).*class (\w+)/s',
            $generatedFile->getContents(),
            $matches
        )){
            throw new \UnexpectedValueException(sprintf(
                'Could not retrieve PHP class information from "%s" file.',
                $generatedFile->getFilename()
            ));
        }

        return (new PhpClassInfo())
            ->setNamespace($matches[1])
            ->setClassName($matches[2])
        ;
    }

    /**
     * @param SplFileInfo $file
     *
     * @throws UnsupportedFileForContentModifierException when file is not a PHP class file
     */
    private function assertFileIsPhpClass(SplFileInfo $file)
    {
        if(
            $file->getExtension() !== 'php'
            || !preg_match('/namespace \w+(?:\\\\\w+)*.*class \w+/s', $file->getContents())
        ){
            throw new UnsupportedFileForContentModifierException(sprintf(
                'This content modifier requires to be used on a PHP class file, "%s" is not a PHP class file.',
                $file->getFilename()
            ));
        }
    }
}

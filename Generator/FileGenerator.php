<?php

namespace Majora\GeneratorBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class which generate / update / delete code classes
 * according to a whole file structure.
 */
class FileGenerator
{
    protected $projectBasePath;
    protected $skeletonsPath;
    protected $targetPath;
    protected $filesystem;
    protected $logger;
    protected $contentModifiers;

    /**
     * construct.
     *
     * @param string          $skeletonsDir
     * @param string          $targetDir
     * @param Filesystem      $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        $skeletonsDir,
        $targetDir,
        Filesystem      $filesystem,
        LoggerInterface $logger
    ) {
        $this->skeletonsPath    = realpath($skeletonsDir);
        $this->targetPath       = realpath($targetDir);
        $this->filesystem       = $filesystem;
        $this->logger           = $logger;
        $this->contentModifiers = new ArrayCollection();
    }

    /**
     * register a content modifier for file generation.
     *
     * @param string                   $alias
     * @param ContentModifierInterface $contentModifier
     */
    public function registerContentModifier($alias, ContentModifierInterface $contentModifier)
    {
        $this->contentModifiers->set($alias, $contentModifier);
    }

    /**
     * generate targer file path from source path.
     *
     * @param SplFileInfo $fileinfo
     * @param Inflector   $inflector
     *
     * @return string
     */
    protected function generatePath(SplFileInfo $fileinfo, Inflector $inflector)
    {
        return $inflector->translate(sprintf('%s%s',
            $this->targetPath,
            str_replace($this->skeletonsPath, '', $fileinfo->getRealPath())
        ));
    }

    /**
     * parse metadata from given template content.
     *
     * @param string $templateFileContent
     *
     * @return array<alias, array>
     */
    protected function getMetadata($templateFileContent)
    {
        $regex                = '/majora_generator\.([a-z0-9_]+)\:\s*([\w]+)/';
        $templateFileMetadata = array(
            'force_generation' => false,
            'content_modifier' => array(),
        );

        if (!preg_match_all($regex, $templateFileContent, $matches, PREG_SET_ORDER)) {
            return $templateFileMetadata;
        }

        foreach ($matches as $match) {
            if (!array_key_exists($match[1], $templateFileMetadata)) {
                continue;
            }

            $templateFileMetadata[$match[1]][] = is_bool($match[2]) ?
                ((bool) $match[2]) === true : $match[2]
            ;
        }

        return $templateFileMetadata;
    }

    /**
     * generate dest dir.
     *
     * @param string $destDirPath
     */
    protected function generateDir($destDirPath)
    {
        // don't override dirs
        if ($this->filesystem->exists($destDirPath)) {
            return;
        }

        $this->filesystem->mkdir($destDirPath);
        $this->logger->info(sprintf('dir created : %s', $destDirPath));
    }

    /**
     * run content modifiers on given file content.
     *
     * @param string      $fileContent
     * @param SplFileInfo $templateFile
     * @param array       $modifiers
     * @param Inflector   $inflector
     *
     * @return string
     */
    public function modify($fileContent, SplFileInfo $templateFile, array $modifiers, Inflector $inflector)
    {
        foreach ($modifiers as $modifierAlias) {
            if (($modifier = $this->contentModifiers->get($modifierAlias))
                && $modifier->supports($templateFile, $fileContent, $inflector)
            ) {
                $fileContent = $modifier->modify($fileContent, $inflector);
            }
        }

        return $fileContent;
    }

    public function generate($entity, $namespace)
    {
        $finder    = new Finder();
        $inflector = new Inflector(array(
            'MajoraEntity'    => $entity,
            'MajoraNamespace' => $namespace,
        ));

        // create file tree
        foreach ($finder->in($this->skeletonsPath) as $templateFile) {
            $generatedFilePath = $this->generatePath($templateFile, $inflector);

            // directory
            if ($templateFile->isDir()) {
                $this->generateDir($generatedFilePath);

                continue;
            }

            // file
            $fileContent      = $templateFile->getContents();
            $alreadyGenerated = $this->filesystem->exists($generatedFilePath);

            $metadata         = $this->getMetadata($fileContent);
            $forceGeneration  = !empty($metadata['force_generation']);
            $modifyContent    = !empty($metadata['content_modifier']);

            // have to touch existing file ?
            if ($alreadyGenerated && !$forceGeneration && !$modifyContent) {
                continue;
            }

            // contents needs to be updated ?
            if ($alreadyGenerated && $modifyContent) {
                $fileContent = (new SplFileInfo($generatedFilePath, '', ''))->getContents();
            }

            $this->filesystem->dumpFile(
                $generatedFilePath,
                $this->modify(
                    $inflector->translate($fileContent),
                    $templateFile,
                    $metadata['content_modifier'],
                    $inflector
                )
            );

            $this->logger->info(sprintf('file %s : %s',
                $forceGeneration ? 'forced' : ($alreadyGenerated ? 'updated' : 'created'),
                $generatedFilePath
            ));
        }
    }
}

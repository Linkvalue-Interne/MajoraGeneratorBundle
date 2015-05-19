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
     * @param array           $contentModifiers
     */
    public function __construct(
        $skeletonsDir,
        $targetDir,
        Filesystem      $filesystem,
        LoggerInterface $logger,
        array           $contentModifiers
    ) {
        $this->skeletonsPath    = realpath($skeletonsDir);
        $this->targetPath       = realpath($targetDir);
        $this->filesystem       = $filesystem;
        $this->logger           = $logger;

        $this->contentModifiers = new ArrayCollection();
        foreach ($contentModifiers as $alias => $contentModifier) {
            $this->registerContentModifier($alias, $contentModifier);
        }
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
     * @param string      $skeletonPath
     * @param string      $targetPath
     * @param Inflector   $inflector
     *
     * @return string
     */
    protected function generatePath(SplFileInfo $fileinfo, $skeletonPath, $targetPath, Inflector $inflector)
    {
        return $inflector->translate(sprintf('%s%s',
            $targetPath,
            str_replace($skeletonPath, '', $fileinfo->getRealPath())
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
        $regex                = '/\@MajoraGenerator\((.+)\)/';
        $templateFileMetadata = array('force_generation' => false);
        if (!preg_match_all($regex, $templateFileContent, $matches, PREG_SET_ORDER)) {
            return $templateFileMetadata;
        }
        foreach ($matches as $match) {
            if (null === $metadata = json_decode($match[1], true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid annotation json data, error %s : %s',
                    json_last_error(),
                    json_last_error_msg()
                ));
            }

            $templateFileMetadata =  array_replace_recursive(
                $templateFileMetadata,
                $metadata
            );
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
     * @param SplFileInfo $generatedFile
     * @param SplFileInfo $templateFile
     * @param array       $modifiers
     * @param Inflector   $inflector
     *
     * @return string
     */
    public function modify(SplFileInfo $generatedFile, SplFileInfo $templateFile, array $modifiers, Inflector $inflector)
    {
        foreach ($modifiers as $modifierAlias => $modifierData) {
            if ($modifier = $this->contentModifiers->get($modifierAlias)) {
                $this->filesystem->dumpFile(
                    $generatedFile->getRealPath(),
                    $modifier->modify(
                        $generatedFile,
                        is_array($modifierData) ? $modifierData : array(),
                        $inflector,
                        $templateFile
                    )
                );
            }
        }
    }

    /**
     * generate classes from given namespace/entity, using given srcDir (or default one)
     * into given destDir (or default one)
     *
     * @param string $namespace
     * @param string $entity
     * @param string $skeletonsPath
     * @param string $targetPath
     * @param array  $excludedSkeletons
     */
    public function generate(
        $namespace,
        $entity,
        $skeletonsPath = null,
        $targetPath    = null,
        array $excludedSkeletons
    )
    {
        $skeletonsPath = $skeletonsPath ?: $this->skeletonsPath;
        $targetPath    = $targetPath ?: $this->targetPath;
        $finder        = new Finder();
        $inflector     = new Inflector(array(
            'MajoraEntity'    => $entity,
            'MajoraNamespace' => $namespace,
        ));
        $modifiersStack = array();

        // create file tree
        $finder->in($skeletonsPath);
        array_map(
            function($excludedSkeleton) use ($finder) {
                $finder->notPath($excludedSkeleton);
            },
            $excludedSkeletons
        );
        foreach ($finder as $templateFile) {

            $generatedFile = new SplFileInfo(
                $generatedFilePath = $this->generatePath(
                    $templateFile,
                    realpath($skeletonsPath),
                    realpath($targetPath),
                    $inflector
                ),
                '',
                ''
            );

            // directory ---------------------------------------------------------
            if ($templateFile->isDir()) {
                $this->generateDir($generatedFilePath);

                continue;
            }

            // file --------------------------------------------------------------
            $fileContent = $inflector->translate($templateFile->getContents());

            // always read template file metadata
            $metadata    = $this->getMetadata($fileContent);

            $forceGeneration = !empty($metadata['force_generation']);
            unset($metadata['force_generation']);

            $modifyContent = count($metadata);

            $alreadyGenerated = $this->filesystem->exists($generatedFilePath);

            // contents needs to be updated ?
            if ($alreadyGenerated && $modifyContent) {
                $fileContent = $generatedFile->getContents();
            }

            // stack content modifiers
            $modifiersStack[] = array(
                array($this, 'modify'),
                array($generatedFile, $templateFile, $metadata, $inflector)
            );

            // have to touch existing file ?
            if ($alreadyGenerated && !$forceGeneration) {
                continue;
            }

            $this->filesystem->dumpFile($generatedFilePath, $fileContent);

            $this->logger->info(sprintf('file %s : %s',
                $forceGeneration ? 'forced' : 'created',
                $generatedFilePath
            ));
        }

        // unstack all modifiers
        foreach ($modifiersStack as $modifierCallback) {
            call_user_func_array($modifierCallback[0], $modifierCallback[1]);
        }
    }
}

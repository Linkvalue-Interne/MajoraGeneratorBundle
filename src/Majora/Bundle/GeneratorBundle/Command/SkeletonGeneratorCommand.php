<?php

namespace Majora\Bundle\GeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Skeleton generator command.
 */
class SkeletonGeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('majora:generate:skeleton')
            ->setDescription(
                'Generate skeleton structure from available base skeletons'
            )
            ->addArgument('skeleton', InputArgument::REQUIRED, 'Base skeleton name (name of the directory to copy)')
            ->addOption('src', null, InputOption::VALUE_REQUIRED, 'Base skeleton directory path (absolute or relative from project root)')
            ->addOption('dest', null, InputOption::VALUE_REQUIRED, 'Target skeletons directory path (absolute or relative from project root)', 'skeletons')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Will replace existing skeleton')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fetch services
        $container = $this->getContainer();
        $logger = $container->get('logger');
        $fs = $container->get('filesystem');

        // Fetch command arguments/options
        $skeleton = $input->getArgument('skeleton');
        $src = $input->getOption('src')
            // handle absolute or relative path
            ? $fs->isAbsolutePath($input->getOption('src'))
                ? $input->getOption('src')
                : realpath(sprintf('%s/../%s',
                    $container->getParameter('kernel.root_dir'),
                    $input->getOption('src')
                ))
            // default path
            : realpath(sprintf('%s/../Resources/skeletons',
                __DIR__
            ))
        ;
        $dest = $fs->isAbsolutePath($input->getOption('dest'))
            ? $input->getOption('dest')
            : realpath(sprintf('%s/../%s',
                $container->getParameter('kernel.root_dir'),
                $input->getOption('dest')
            ))
        ;
        $force = $input->getOption('force');

        // Check if skeleton name exists
        if(!$fs->exists(realpath(sprintf('%s/%s', $src, $skeleton)))) {
            $logger->critical(sprintf('Base skeleton [%s] was not found at [%s]. Either try with another skeleton name or with another source path (--src option).', $skeleton, $src));
            return;
        }

        // Remove skeleton if exists
        if($fs->exists(realpath(sprintf('%s/%s', $dest, $skeleton)))) {
            if(!$force) {
                $logger->warning(sprintf('Nothing was generated because skeleton [%s] already exists. Use -f option to override existing skeleton.', $skeleton));
                return;
            }
            $fs->remove(realpath(sprintf('%s/%s', $dest, $skeleton)));
        }

        // Copy skeleton recursively
        $recursiveDirectoryIterator = new \RecursiveDirectoryIterator(realpath(sprintf('%s/%s', $src, $skeleton)), \RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIteratorIterator = new \RecursiveIteratorIterator($recursiveDirectoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach($recursiveIteratorIterator as $item) {
            if($item->isDir()) {
                $fs->mkdir(sprintf('%s%s%s%s%s', $dest, DIRECTORY_SEPARATOR, $skeleton, DIRECTORY_SEPARATOR, $recursiveIteratorIterator->getSubPathName()));
                continue;
            }
            $fs->copy($item, sprintf('%s%s%s%s%s', $dest, DIRECTORY_SEPARATOR, $skeleton, DIRECTORY_SEPARATOR, $recursiveIteratorIterator->getSubPathName()), true);
        }
        $logger->info(sprintf('Base skeleton [%s] successfully generated.', $skeleton));
    }
}

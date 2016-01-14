<?php

namespace Majora\Bundle\GeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generator command, boots generator from CLI.
 */
class GeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('majora:generate')
            ->setDescription(
                'Generate whole directory structure from skeletons for given entity and namespace'
            )
            ->addArgument('vendor', InputArgument::REQUIRED, 'Vendor to generate files into')
            ->addArgument('namespace', InputArgument::REQUIRED, 'Namespace to put entity into')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity to generate')
            ->addOption('skeletons', null, InputOption::VALUE_REQUIRED, 'Skeleton directory', null)
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Target generation directory', null)
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED, 'Coma separated skeleton dirs to exclude', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('majora.generator')->generate(
            $input->getArgument('vendor'),
            $input->getArgument('namespace'),
            $input->getArgument('entity'),
            $input->getOption('skeletons') ?
                realpath(sprintf('%s/../%s',
                    $this->getContainer()->getParameter('kernel.root_dir'),
                    $input->getOption('skeletons')
                )) :
                null
            ,
            $input->getOption('target') ?
                realpath(sprintf('%s/../%s',
                    $this->getContainer()->getParameter('kernel.root_dir'),
                    $input->getOption('target')
                )) :
                null
            ,
            $input->getOption('exclude') ?
                explode(',', $input->getOption('exclude')) :
                array()
        );
    }
}

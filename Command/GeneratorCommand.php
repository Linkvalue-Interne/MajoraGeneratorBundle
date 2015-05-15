<?php

namespace Majora\GeneratorBundle\Command;

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
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity to generate')
            ->addArgument('namespace', InputArgument::OPTIONAL, 'Namespace to put entity into (default : same as entity)')
            ->addOption('force', null, InputOption::VALUE_REQUIRED, 'Force file generation', false)
         ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('majora.generator')->generate(
            $input->getArgument('entity'),
            $input->getArgument('namespace') ? $input->getArgument('namespace') : $input->getArgument('entity')
        );
    }
}

<?php

namespace Alex\DoctrineExtraBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Alex\DoctrineExtraBundle\Graphviz\DoctrineMetadataGraph;
use Symfony\Component\Yaml\Yaml;

/**
 * Tool to generate graph from mapping informations.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class DoctrineMetadataGraphvizCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:mapping:graphviz')
            ->addOption(
              'no-reverse',
              null,
              InputOption::VALUE_NONE,
              'Do not output "reverse" associations'
            )
            ->addOption(
                'use-random-edge-color',
                null,
                InputOption::VALUE_NONE,
                'Use a random color for each generated edge'
            )
            ->addOption(
                'business-split-file',
                null,
                InputOption::VALUE_REQUIRED,
                'YAML file to split entities by business zones'
            )
            ->addOption(
                'font',
                null,
                InputOption::VALUE_REQUIRED,
                'Font name to use'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $businessConfigFilePath = $input->getOption('business-split-file');

        $businessConfig = null;
        if ($businessConfigFilePath !== null) {
            $businessConfig = Yaml::parseFile($businessConfigFilePath);
        }
        $em = $this->getContainer()->get('doctrine')->getManager();
        $graph = new DoctrineMetadataGraph($em, array(
          'includeReverseEdges' => !$input->getOption('no-reverse'),
          'useRandomEdgeColor' => $input->getOption('use-random-edge-color'),
          'business-config' => $businessConfig,
          'font' => $input->getOption('font')
        ));

        $output->writeln($graph->render());
    }
}

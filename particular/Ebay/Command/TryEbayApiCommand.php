<?php

namespace Particular\Ebay\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Attempts to connect to ebay with credentials.
 * Uses the COMMAND_DIR constant.
 */
class TryEbayApiCommand extends Command 
{
    protected function configure()
    {
        $this
            ->setName('tryapi')
            ->setDescription('Tries to connect to the Ebay API.')
            ->setHelp('Ebay API credentials must be stored in config.yaml in the bin directory.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $config = Yaml::parse(file_get_contents(COMMAND_DIR.'/config.yaml'));
        print_r($config);
    }
}

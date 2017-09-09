<?php

namespace Particular\Ebay\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class MkdirsCommand extends Command 
{
    protected function configure()
    {
        $this
            ->setName('mkdirs')
            ->setDescription('Creates a directory or range of SKU directories in the "incoming" directory.')
            ->setHelp('SKU directories are kept within directories indicating the current state of the SKU. The current states are "incoming", "active", and "sold".')
        ;

        $this
            ->addArgument('first', InputArgument::REQUIRED, 'First SKU directory.')
            ->addArgument('last', InputArgument::OPTIONAL, 'Last SKU directory.')
        ;
    }

    /**
     * Creates new directories in the "incoming" state directory.
     * Argument format for the directory names is pair of numbers for the first and last
     * directory to create, or a pair of numbers with an alphabetic suffix.
     *
     * [a-z]*[0-9]+
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $first = $input->getArgument('first');
        $last = $input->getArgument('last');
        $padding = false;

        if (preg_match('/(^[a-z]*)([0-9]*)/', $first, $matches)) 
        {
            $prefix = $matches[1];
            $low = intval($matches[2]);
            if ($matches[2][0] == '0') {
                $padding = true;
                $places = strlen($matches[2]);
            }
        }

        if (preg_match('/([a-z]*)([0-9]*)/', $last, $matches)) 
        {
            $prefix = $matches[1];
            $high = intval($matches[2]);
        }


        for($i = $low; $i <= $high; $i++) 
        {
            /* this should be in a config file */
            $path = COMMAND_DIR.'/../incoming/';
            if ($padding) {
                mkdir($s = sprintf("$path$prefix%0$places".'s', $i));
            } else {
                mkdir($s = $path.$prefix.$i);
            }
            echo "$s\n";
        }
    }
}

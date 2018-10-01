<?php

namespace Particular\Ebay\Command;

use Particular\Ebay\Traits;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MovedirsCommand extends Command 
{
    use Traits\StateDirectory;
    use Traits\SKUsWithNames;

    protected function configure()
    {
        $this
            ->setName('mvdirs')
            ->setDescription('Moves SKU directories into the state directories based on a list of SKUs piped into stdin.')
            ->setHelp('SKU directories contain information and photos of the SKU. The SKU directories are inside directories indicating the state of the SKU. The states are "incoming", "active", and "sold". mvdirs moves a SKU into a specified state.');

        $this
            ->addArgument('targetState', InputArgument::REQUIRED, 'State to which SKUs are assigned.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetState = $input->getArgument('targetState');

        while($line = fgets(STDIN)) {
            $line = trim($line);
            if (!$line) continue; // blank line

            $state = $this->locateState($line);
            if (!$state) continue; // can't find the sku in a state
            if ($state == $targetState) continue; // sku is already in that state
            
            $oldpath = COMMAND_DIR."/../$state/".$line;
            $newpath = COMMAND_DIR."/../$targetState/".$line;

            if (file_exists($newpath)) {
                echo "Warning: $newpath exists. Not moving $line.\n";
                continue; // collision moving the file
            }

            if (!rename($oldpath, $newpath)) {
                echo "Error moving $oldpath.\n";
            };
        }
    }

}


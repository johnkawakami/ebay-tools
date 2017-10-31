<?php

namespace Particular\Ebay\Command;

use Particular\Ebay\Traits;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeCommand extends Command 
{
    use Traits\StateDirectory;

    protected function configure()
    {
        $this
            ->setName('merge')
            ->setDescription('Moves SKU directories into the first SKU directory listed on the command line. The order is the reverse of the "mv" command.')        
            ->setHelp('SKU directories contain information and photos of the SKU. When individual items are combined into a "lot" for bulk sale, they can be combined into one SKU. Merging keeps the state directory clean by gathering photos into one directory. If only one argument, the target directory, is provided, then standard input is read for SKUs.');

        $this
            ->addArgument('targetSKU', InputArgument::REQUIRED, 'SKU that receives resources for the other SKUs.')
            ->addArgument('skus', InputArgument::IS_ARRAY, 'List of SKUs to move over to the first SKU.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetSKU = $input->getArgument('targetSKU');
        $skus = $input->getArgument('skus');

        // if we provide only the target sku, we read stdin for skus
        $read_stdin = (count($skus) == 0); 

        // we use this to open nautilus
        $path = COMMAND_DIR."/../".$this->locateState($targetSKU)."/$targetSKU/";

        foreach($skus as $sku) {
            $this->move($sku, $targetSKU);
        }

        if ($read_stdin) {
            while($line = fgets(STDIN)) {
                $line = trim($line);
                if (!$line) continue; // blank line

                $this->move($line, $targetSKU);
            }
        }

        exec("nautilus \"$path\"");
    }

    private function move($sku, $targetSKU) {
        $tss = $this->locateState($targetSKU);
        $ss = $this->locateState($sku);
        $oldpath = COMMAND_DIR."/../$ss/".$sku;
        $newpath = COMMAND_DIR."/../$tss/".$targetSKU.'/'.$sku;

        if (file_exists($newpath)) {
            echo "Warning: $newpath exists. Not moving $line.\n";
            return;
        }

        if ($sku == $targetSKU) {
            echo "Cannot move to itself.\n";
            return;
        }

        if (!rename($oldpath, $newpath)) {
            echo "Error moving $oldpath.\n";
        };

    }

}


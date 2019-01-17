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
    use Traits\SKUsWithNames;

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

    // fixme - this is just all wrong. it needs to be simpler and cleaner.
    // This needs to be more of a macro, like:
    //   find all the target sku dirs and consolidate into 'active'
    //     create a new dir if it doesn't already exist
    //   find all the source sku dirs and consolidate them into one place
    //     if a source sku dir doesn't exist, continue
    //   move the source sku dir into the target sku dir
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetSKU = $this->getCanonicalSKU($input->getArgument('targetSKU'));
        $skus = $input->getArgument('skus');

        // if we provide only the target sku, we read stdin for skus
        $read_stdin = (count($skus) == 0); 

        // we use this to open nautilus
        $path = COMMAND_DIR."/../".$this->locateState($targetSKU)."/$targetSKU/";
        $index = $path."/combined.txt";

        foreach($skus as $sku) {
            $this->move($sku, $targetSKU);
        }

        $moreSKUs = [];
        if ($read_stdin) {
            while($line = fgets(STDIN)) {
                $line = trim($line);
                if (!$line) continue; // blank line

                $sku = $this->getCanonicalSKU($line);

                $this->move($sku, $targetSKU);

                $moreSKUs[] = $line;
            }
        }

        if (!file_exists($index)) {
            file_put_contents($index, implode("\n", array_merge(array($targetSKU), $skus, $moreSKUs)));
        }

        if (file_exists($path)) 
        {
            exec("nautilus \"$path\"");
        }
    }

    /**
     * Move the sku into the targetSKU
     */
    public function move($sku, $targetSKU) {
        // move all the sku directories into the active state
        $sourcePath = $this->cleanSKUAndMoveInto($sku, 'active');

        // find the targetSKU's state
        $targetState = $this->locateState($targetSKU);

        // merge all the targetSKU directories into one
        $targetPath = $this->mergeDirsForSKU($this->makeTargetPath($targetState), $targetSKU);

        if ($sku == $targetSKU) {
            echo "Cannot move to itself.\n";
            return;
        }

        // sanity check - targetPath exists, and sourcePath exists
        if (is_dir($sourcePath) && is_dir($targetPath)) {
            $targetFilename = pathinfo($sourcePath, PATHINFO_BASENAME);
            if (!rename($sourcePath, $targetPath . DIRECTORY_SEPARATOR . $targetFilename)) {
                echo "Error moving $sourcePath.\n";
            };
        }

    }

}


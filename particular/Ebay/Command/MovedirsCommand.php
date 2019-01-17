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
    use Traits\SharedDirectory;

    protected function configure()
    {
        $this
            ->setName('mvdirs')
            ->setDescription('Moves SKU directories into the state directories based on a list of SKUs piped into stdin. Also merges each SKU directory into one SKU directory.')
            ->setHelp('SKU directories contain information and photos of the SKU. The SKU directories are inside directories indicating the state of the SKU. The states are "incoming", "active", and "sold". mvdirs moves a SKU into a specified state, and also copies down any files from shared SKU directories. Side effect is that all the shared SKU directories are merged into one shared SKU directory.');

        $this
            ->addArgument('targetState', InputArgument::REQUIRED, 'State to which SKUs are assigned.')
        ;
    }

    /**
     * The logic here should be changed. Rather than finding the state and then moving it,
     * figure out the best target filename, and then find the SKUs in all the states and
     * the shared folder, and then move or copy all the files into the target.
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetState = $input->getArgument('targetState');

        while($line = fgets(STDIN)) {
            $line = trim($line);
            if (!$line) continue; // blank line

            $sku = $this->getCanonicalSKU($line);

            $targetPath = $this->cleanSKUAndMoveInto($sku, $targetState);
            // echo "targetPath was $targetPath\n";

            // look in the shared directory to find a sku dir there
            // if it exists, copy the files down into the targetPath
            // we don't move the files - they stay in the shared directory
            if ($this->SKUExistsInPath($this->sharedPath(), $sku)) {
                $targetSharedDir = $this->mergeDirsForSKU($this->sharedPath(), $sku);
                $longest = $this->getLongestFilename(array($targetSharedDir, $targetPath));
                print_r($longest);
                $bestTarget = $this->makeTargetPath($targetState, $longest['filename']);
                // echo "bestTarget $bestTarget\n";
                $this->copyDirs($bestTarget, $targetSharedDir);
                $this->mergeDirsForSKU($this->sharedPath(), $sku);
            }
        }
    }


}


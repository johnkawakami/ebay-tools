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

            $paths = [];
            // consolidate sku directories in each state
            foreach($this->getStates() as $state) {
                $path = realpath(COMMAND_DIR."/../$state/");
                $paths[] = $this->mergeDirsForSKU($path, $sku);
            }
            $paths = array_filter($paths, "is_string");

            if (count($paths)==0) continue;

            // preserve the longest filename
            usort(
                $paths, function ($a, $b) {
                    $aname = pathinfo($a, PATHINFO_BASENAME);
                    $bname = pathinfo($b, PATHINFO_BASENAME);
                    return (strlen($aname) < strlen($bname)) ? 1 : -1;
                }
            );
            $longest = pathinfo($paths[0], PATHINFO_BASENAME);

            // we want to use this directory
            $targetPath = realpath(COMMAND_DIR."/../$targetState/") . 
                          DIRECTORY_SEPARATOR . $longest;
            if (!is_dir($targetPath)) {
                mkdir($targetPath);
            }
            //var_dump("targetPath", $targetPath);

            // create an array of source paths, excluding the targetPath
            $sources = array_diff($paths, array($targetPath));
            // var_dump("source", $sources);

            foreach ($sources as $source) {
                $this->mergeDirs($targetPath, $source);
                if (file_exists($source)) {
                    unlink($source);
                }
            }

            // now look in the shared directory to find a sku dir there
            // if it exists, copy the files down into the targetPath
            // fixme
        }
    }

}


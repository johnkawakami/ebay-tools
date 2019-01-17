<?php

namespace Particular\Ebay\Command;

use Particular\Ebay\Traits;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShareCommand extends Command 
{
    use Traits\SharedDirectory;
    use Traits\StateDirectory;
    use Traits\SKUsWithNames;

    protected function configure()
    {
        $this
            ->setName('share')
            ->setDescription('Share SKU directories into the shared directory.')
            ->setHelp('If the optional SKU is not specified, then STDIN is read for a list of SKUs.');

        $this
            ->addArgument('sku', InputArgument::OPTIONAL, 'SKU')
        ;
    }

    // fixme not yet written
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sku = $input->getArgument('sku');

        if ($sku) {
            $this->share($this->getCanonicalSKU($sku));
            return;
        }

        while($line = fgets(STDIN)) {
            $line = trim($line);
            $this->share($this->getCanonicalSKU($line));
        }
        return;
    }

}


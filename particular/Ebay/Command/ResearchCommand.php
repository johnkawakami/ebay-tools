<?php

namespace Particular\Ebay\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ResearchCommand extends Command 
{
    protected function configure()
    {
        $this
            ->setName('research')
            ->setDescription('Reads keywords from STDIN and opens Firefox to perform a search and a search for recent sales.')
            ->setHelp('This command helps to research prices on Ebay for multiple titles for a listing. Input is separated by newlines, commas, and tabs.  So a line with three fields delimted by tabs is treated as three inputs. A delay can be added to pause between processing lines.');

        $this
            ->addArgument('delay', InputArgument::OPTIONAL, 'Seconds to wait between processing lines.');

        $this
            ->addOption('low', null, null, 'Show only sold items sorted low to high.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delay = intval($input->getArgument('delay'));
        $show_low = $input->getOption('low');

        while($line = fgets(STDIN)) {
            $line = trim($line);
            if (!$line) continue; // blank line

            $parts = preg_split('/[,\t]/', $line);

            foreach($parts as $part) {

                if ($show_low) {
                    $url = "https://www.ebay.com/sch/i.html?_nkw=".urlencode($part)."&LH_Complete=1&LH_Sold=1&_sop=15";
                    exec("firefox \"$url\"");
                } else {
                    $url = "https://www.ebay.com/sch/i.html?_nkw=".urlencode($part);
                    exec("firefox \"$url\"");

                    $url = "https://www.ebay.com/sch/i.html?_nkw=".urlencode($part)."&LH_Complete=1&LH_Sold=1";
                    exec("firefox \"$url\"");
                }
            }

            sleep($delay);
        }
    }
}

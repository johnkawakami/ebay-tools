<?php

namespace Particular\Ebay\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SerpCommand extends Command 
{
    protected function configure()
    {
        $this
            ->setName('serp')
            ->setDescription('Reads keywords from STDIN and opens Firefox to perform a search on Google and Bing.')
            ->setHelp('This command helps to research search result rankings for existing listings. Input is separated by newlines, commas, and tabs.  So a line with three fields delimted by tabs is treated as three inputs. A delay can be added to pause between processing lines.');

        $this
            ->addArgument('delay', InputArgument::OPTIONAL, 'Seconds to wait between processing lines.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delay = intval($input->getArgument('delay'));

        while($line = fgets(STDIN)) {
            $line = trim($line);
            if (!$line) continue; // blank line

            $parts = preg_split('/[,\t]/', $line);

            foreach($parts as $part) {
                $url = "https://www.bing.com/search?q=".urlencode($part);
                exec("firefox \"$url\"");

                $url = "https://www.google.com/search?client=ubuntu&channel=fs&q=".urlencode($part);
                exec("firefox \"$url\"");
            }

            sleep($delay);
        }
    }
}

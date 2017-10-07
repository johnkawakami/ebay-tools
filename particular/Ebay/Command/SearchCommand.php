<?php

namespace Particular\Ebay\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SearchCommand extends Command 
{
    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Reads titles from STDIN and opens Firefox to perform the search.')
            ->setHelp('Use for doing searches on multiple titles for a listing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while($line = fgets(STDIN)) {
            $line = trim($line);
            if (!$line) continue; // blank line

            $url = "https://www.ebay.com/sch/i.html?_nkw=".urlencode($line);

            exec("firefox \"$url\"");
        }
    }
}

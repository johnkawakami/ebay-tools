<?php

namespace Particular\Ebay\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use DTS\eBaySDK\Shopping\Services;
use DTS\eBaySDK\Shopping\Types;
use DTS\eBaySDK\Credentials;

/**
 * Attempts to connect to ebay with credentials.
 * Uses the COMMAND_DIR constant.
 */
class TimeApiCommand extends Command 
{
    protected function configure()
    {
        $this
            ->setName('time')
            ->setDescription('Tries to connect to the Ebay Shopping API to get the time.')
            ->setHelp('Ebay API credentials must be stored in config.yaml in the bin directory.')
        ;
    }

    /**
     * Based on
     * https://github.com/davidtsadler/ebay-sdk-examples/blob/master/shopping/01-get-ebay-time.php
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Yaml::parse(file_get_contents(COMMAND_DIR.'/config.yaml'));
        $cs = $config['production']['credentials'];

        /**
         * Create credentials.
         */
        $credentials = new Credentials\Credentials($cs['appId'], $cs['certId'], $cs['devId']);
        /**
         * Create the service object.
         */
        $service = new Services\ShoppingService(['credentials'=>$credentials]);
        /**
         * Create the request object.
         */
        $request = new Types\GeteBayTimeRequestType();
        /**
         * Send the request.
         */
        $response = $service->geteBayTime($request);
        /**
         * Output the result of calling the service operation.
         */
        if (isset($response->Errors)) {
            foreach ($response->Errors as $error) {
                printf(
                    "%s: %s\n%s\n\n",
                    $error->SeverityCode === \DTS\eBaySDK\Shopping\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning',
                    $error->ShortMessage,
                    $error->LongMessage
                );
            }
        }
        if ($response->Ack !== 'Failure') {
            printf("The official eBay time is: %s\n", $response->Timestamp->format('H:i (\G\M\T) \o\n l jS F Y'));
        }
    }
}

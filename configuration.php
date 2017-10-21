<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$obj = Yaml::parse(file_get_contents(__DIR__.'/config.yaml'));

return $obj;

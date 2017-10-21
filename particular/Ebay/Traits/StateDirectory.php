<?php

namespace Particular\Ebay\Traits;

trait StateDirectory {
    private function locateState($sku) {
        $states = [ 'incoming', 'active', 'sold' ];
        foreach($states as $state) {
            $path = COMMAND_DIR."/../$state/$sku";
            if (file_exists($path)) {
                return $state;
            }
        }
        echo "Warning: $sku not found in any state.\n";
    }
}

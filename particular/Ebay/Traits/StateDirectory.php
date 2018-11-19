<?php
/**
 * PHP version 7.2
 *
 * @category Ebay
 * @package  Traits
 * @author   "John Kawakami" <johnk@riceball.com>
 * @license  GPL3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version  GIT: 0.0.0
 * @link     http://riceball.com
 */
namespace Particular\Ebay\Traits;

/**
 * Methods to access directories for listing photos, which
 * are grouped into "states".
 */
trait StateDirectory
{
    /**
     * Given a SKU, it finds it's directory within the 
     * states.
     *
     * @param string $sku the sku
     *
     * @return string the state name, not the entire path
     */
    public function locateState($sku)
    {
        foreach ($this->getStates() as $state) {
            $path = COMMAND_DIR."/../$state/";
            if ($this->SKUExistsInPath($path, $sku)) {
                return $state;
            }
        }
        echo "Warning: $sku not found in any state.\n";
        return null;
    }

    public function getStates()
    {
        return [ 'incoming', 'active', 'sold' ];
    }
}

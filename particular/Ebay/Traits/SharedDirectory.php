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
 * An add-on to SKU directories to handle sharing SKU directories
 * into a file sharing service like Dropbox. The use case is that
 * I needed to work on some files remotely, so I put the SKU directory
 * into a folder in Dropbox. Changes and edits are shared.
 *
 * Once I'm done working on it, I copy it down into the permanent
 * folder structure, and delete it from Dropbox.
 */
trait SharedDirectory
{
    public function sharedPath()
    {
        return realpath($_SERVER['HOME']."/Dropbox/Ebay Pictures/") . DIRECTORY_SEPARATOR;
    }

    /**
     * Utility function to share a SKU dir. This only shares
     * from the "incoming" or "active" state, and copies up
     * all the files.
     *
     * It doesn't share from the "sold" state.
     *
     * Has side effect of merging SKU dirs in a state.
     *
     * fixme hasn't been tested
     */
    public function share($sku)
    {
        $sku = strtolower($sku);
        //$targetDir = $this->sharedPath() . $sku;
        $states = array_diff($this->getStates(), ['sold']);
        foreach($states as $state) {
            echo "merging $state $sku\n";
            $path = $this->mergeDirsForSKU($this->statePath() . $state, $sku);
            if ($path) {
                $filename = pathinfo($path, PATHINFO_BASENAME);
                echo "copying $filename from $path\n";
                $this->copyDirs($this->sharedPath() . $filename, $path);
            }
        }
    }

    /**
     * Utility function to copy down changes from a shared
     * SKU dir, and then delete it.
     *
     * fixme not yet written
     */
    public function unshare($sku)
    {
    }
}

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
            $path = $this->statePath() . $state;
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

    public function statePath()
    {
        return realpath(COMMAND_DIR."/../") . DIRECTORY_SEPARATOR;
    }
    
    public function makeTargetPath($state, $filename = '')
    {
        return realpath(COMMAND_DIR."/../$state") .  DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gathers up all instances of a SKU directory in all states,
     * and merges their contents into a SKU directory in the target state.
     *
     * @param $sku string the sku
     * @param $targetSTate the target state
     *
     * @return $longest string|null returns the path
     */
    public function cleanSKUAndMoveInto($sku, $targetState) 
    {
        // echo "cleanSKUAndMoveInto $sku, $targetState\n";
        $paths = [];
        // consolidate sku directories in each state
        // gather a list of paths to merge
        foreach($this->getStates() as $state) {
            $paths[] = $this->mergeDirsForSKU($this->makeTargetPath($state), $sku);
        }
        $paths = array_filter($paths, "is_string");
        // echo "paths"; var_dump($paths);

        // merge the contents of the list of paths into the
        // target state
        $longest = $this->getLongestFilename(array_merge(array($sku), $paths));
        $targetPath = $this->makeTargetPath($targetState, $longest['filename']);
        // echo "targetPath is $targetPath\n";
        if (count($paths) > 0) {
            $this->mergeIntoTargetStateDirectory($paths, $targetPath);
            $newTargetPath = $this->mergeDirsForSKU($this->makeTargetPath($targetState), $sku);
            return $newTargetPath;
        }
        return null;
    }

    /**
     * Merge the array of paths into targetPath, which is a state directory.
     *
     * @param Array  $paths 
     * @param String $targetPath 
     *
     * @return void
     */
    private function mergeIntoTargetStateDirectory($paths, $targetPath)
    {
        if (!is_dir($targetPath)) {
            if (false === mkdir($targetPath)) {
                throw new Exception("Could not make $targetPath");
            }
        }
        $targetPath = realpath($targetPath);

        // create an array of source paths, excluding the targetPath
        $sources = array_diff($paths, array($targetPath));

        if (count($sources)>0) {
            foreach ($sources as $source) {
                // echo "Merging $source into $targetPath\n";
                $path = $this->mergeDirs($targetPath, $source);
                if ($path && file_exists($source)) {
                    unlink($source);
                }
            }
        }
    }
}

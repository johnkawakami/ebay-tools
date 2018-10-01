<?php
/**
 * PHP version 7.2
 *
 * Trait to allow SKUs to have long file names.
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
 * Implements methods to allow SKU directories to contain extra text.
 * This allows names like "i123 some trinkets".
 *
 * This was required because I started making directories in Dropbox,
 * while I was away from home.
 *
 * The mergeDirsForSKU() method is a utility that merges SKU
 * directories automatically, so we will have only one directory per SKU.
 * (In any given path or state.)
 * This should be called once you know the SKU and have a path.
 *
 * For example, if we're moving directories with "ebay mvdirs sold", 
 * the merge utility should be called on both the origin path and the sold path
 * for each sku.  This will merge any duplicate SKUs, before moving that directory
 * into it's final state.  It should be called in the final state, as well,
 * to de-duplicate SKUs there, as well.
 */

trait SKUsWithNames
{
    /**
     * Does a directory matching the sku exist in the path?
     *
     * @param string $path directory to search
     * @param string $sku  SKU to match
     *
     * @return boolean true if a directory matching the sku exists in the path
     */
    public function SKUExistsInPath( $path, $sku )
    {
        $sku = strtolower($sku);
        $dirs = $this->getAllDirsMatchingSKU($path, $sku);
        var_dump($dirs);
        return (count($dirs) > 0);
    }

    /**
     * Extracts the SKU from a filename.
     *
     * @param string $filename without the leading path
     *
     * @return string sku in canonical form
     */
    public function getSKUFromFilename( $filename ) 
    {
        preg_match("/^([a-z][0-9]+?) (.*)$/i", $filename, $matches);
        $sku = strtolower($matches[1]);
        return $sku;
    }

    /**
     * Renames an existing directory with a SKU in the filename
     * to use the canonical name, which is the SKU, lowercase.
     *
     * @param string $filepath path to the SKU directory.
     *
     * @return string same path, altered.
     */
    public function renameToCanonicalSKU( $filepath ) 
    {
        $parts = pathinfo($filepath);
        $sku = $this->getSKUFromFilename($parts['basename']);
        rename($filepath, $parts['dirname'] . DIRECTORY_SEPARATOR . $sku);
    }
    /**
     * Returns longest directory matching the SKU. So, if you
     * have dirs path/i505 and 'path/i505 and more' it returns
     * 'i505 and more'.
     *
     * @param string $path to the directory
     * @param string $sku  the sku to find
     *
     * @return mixed the longest matching sku directory
     * 
     * @throws RuntimeException when there are no directories matching the SKU
     */
    public function getLongestDirFromSKU( $path, $sku ) 
    {
        $sku = strtolower($sku);
        $dirs = $this->getAllDirsMatchingSKU($path, $sku);
        usort(
            $dirs, function ($a, $b) {
                return strlen($a) < strlen($b) ? 1 : -1 ; 
            }
        );
        if (isset($dirs[0])) {
            return $dirs[0];
        } else {
            throw new \RuntimeException($sku . ' does not exist in ' . $path);
        }
    }

    /**
     * Merges all directories, within a path, that match an SKU into a 
     * single directory.
     *
     * This should be called before moving an SKU directory, and after moving
     * an SKU directory.
     *
     * @param string $path to the directory
     * @param string $sku  the sku to merge
     *
     * @return void
     */
    public function mergeDirsForSKU( $path, $sku ) 
    {
        $dirs = $this->getAllDirsMatchingSKU($path, $sku);
        $longest = $this->getLongestDirFromSKU($path, $sku);
        $count = count($dirs);
        if ($count == 0) return;
        if ($count == 1) return;

        foreach ($dirs as $dir) {
            if ($dir !== $longest) {
                $this->mergeDirs(
                    $path.DIRECTORY_SEPARATOR.$longest, 
                    $path.DIRECTORY_SEPARATOR.$dir
                );
            }
        }
        return;
    }

    /**
     * Return an array of directories in a path that match a SKU.
     * Note that the result can be an empty array if none match.
     *
     * @param string $path to the directory
     * @param string $sku  the sku to merge
     *
     * @return array of directories
     */
    public function getAllDirsMatchingSKU( $path, $sku ) 
    {
        $sku = strtolower($sku);
        $dirs = new \CallbackFilterIterator(
            new \DirectoryIterator($path), 
            function ($current, $key, $iterator) use ($sku) {
                $result = (! $current->isDot()) && 
                    (stripos($current->getFilename(), $sku) !== false);
                return $result;
            }
        );
        $output = [];
        foreach ($dirs as $dir) {
            $output[] = $dir->getFilename();
        }
        return $output;
    }

    /**
     * Merges all the files from $path2 into $path1, and deletes $path2.
     *
     * @param string $path1 target directory's path
     * @param string $path2 directory to merge into $path1
     *
     * @return void
     */
    public function mergeDirs($path1, $path2)
    {
        $d = new \DirectoryIterator($path2);
        foreach ($d as $fileInfo) {
            if ($fileInfo->isDot()) continue;

            $fname = $fileInfo->getFilename();
            $name = $fname;

            // if the file exists, and it's the same file, delete one file
            if (file_exists($path1 . DIRECTORY_SEPARATOR . $fname)) {
                if ($this->_hashFile($path1 . DIRECTORY_SEPARATOR . $fname) === $this->_hashFile($path2 . DIRECTORY_SEPARATOR . $fname)) {
                    unlink($path2 . DIRECTORY_SEPARATOR . $fname);
                    continue;
                }

                $parts = pathinfo($fname);
                $filename = $parts['filename'];
                $extension = $parts['extension'];

                $counter = 1;
                while (file_exists($path1 . DIRECTORY_SEPARATOR . $name)) {
                    $name = $filename . ' ' . $counter . '.' . $extension;
                    $counter++;
                }
            }

            rename($path2 . DIRECTORY_SEPARATOR . $fname, $path1 . DIRECTORY_SEPARATOR . $name);
        }
        rmdir($path2);
    }

    /**
     * Reads a file and returns a hash value for it. Not secure.
     *
     * @param string $filepath path to a file to hash
     * 
     * @return string hash result
     */
    private function _hashFile( $filepath )
    {
        return crc32(file_get_contents($filepath, false, null, 0, 1024));
    }
}

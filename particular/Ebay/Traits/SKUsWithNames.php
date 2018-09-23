<?php

// todo - test this class

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
    public function SKUExistsInPath( $path, $sku ) {
        $sku = strtolower( $sku );
        $dirs = $this->getAllDirsMatchingSKU( $path, $sku );
        var_dump($dirs);
        return (count( $dirs ) > 0);
    }

    public function getSKUFromFilename( $filename ) {
       preg_match( "/^([a-z][0-9]+?) (.*)$/i", $filename, $matches );
       $sku = strtolower( $matches[1] );
       return $sku;
    }

    /**
     * Returns longest directory matching the SKU.
     */
    public function getLongestDirFromSKU( $path, $sku ) {
        $sku = strtolower( $sku );
        $dirs = $this->getAllDirsMatchingSKU( $path, $sku );
        usort($dirs, function($a, $b) { return strlen($a) < strlen($b) ? 1 : -1 ; });
        if (isset($dirs[0])) return $dirs[0];
        return FALSE;
        // fixme - use exceptions
    }

    /**
     * Merges all directories, within a path, that match an SKU into a single directory.
     *
     * This should be called before moving an SKU directory, and after moving
     * an SKU directory.
     */
    public function mergeDirsForSKU( $path, $sku ) {
        $dirs = $this->getAllDirsMatchingSKU( $path, $sku );
        $longest = $this->getLongestDirFromSKU( $path, $sku );
        $count = count( $dirs );
        if ( $count == 0 ) return;
        if ( $count == 1 ) return;

        foreach( $dirs as $dir ) {
            if ( $dir !== $longest ) {
                $this->mergeDirs( $path.DIRECTORY_SEPARATOR.$longest, $path.DIRECTORY_SEPARATOR.$dir );
            }
        }
        return;
    }

    /**
     * @return array of directories
     */
    public function getAllDirsMatchingSKU( $path, $sku ) {
        $sku = strtolower( $sku );
        $dirs = new \CallbackFilterIterator(new \DirectoryIterator( $path ), 
            function ($current, $key, $iterator) use ($sku) {
                $result = (! $current->isDot()) && (stripos( $current->getFilename(), $sku ) !== FALSE);
                return $result;
            }
        );
        $output = [];
        foreach( $dirs as $dir )
        {
            $output[] = $dir->getFilename();
        }
        return $output;
    }

    /**
     * Merges all the files from $path2 into $path1, and deletes $path2.
     */
    public function mergeDirs( $path1, $path2 ) {
        $d = new \DirectoryIterator( $path2 );
        foreach( $d as $fileInfo ) {
            if ($fileInfo->isDot()) continue;

            $fname = $fileInfo->getFilename();
            $name = $fname;

            // if the file exists, and it's the same file, delete one file
            if (file_exists( $path1 . DIRECTORY_SEPARATOR . $fname ))
            {
                if ($this->hash( $path1 .DIRECTORY_SEPARATOR . $fname ) ===
                    $this->hash( $path2 .DIRECTORY_SEPARATOR . $fname )) 
                {
                    unlink( $path2 . DIRECTORY_SEPARATOR . $fname );
                    continue;
                }

                $parts = pathinfo( $fname );
                $filename = $parts['filename'];
                $extension = $parts['extension'];

                $counter = 1;
                while (file_exists( $path1 . DIRECTORY_SEPARATOR . $name )) {
                    $name = $filename . ' ' . $counter . '.' . $extension;
                    $counter++;
                }
            }

            rename( $path2 . DIRECTORY_SEPARATOR . $fname, $path1 . DIRECTORY_SEPARATOR . $name );
        }
        rmdir( $path2 );
    }

    private function hash( $filepath )
    {
        return crc32( file_get_contents($filepath, FALSE, NULL, 0, 1024 ) );
    }
}

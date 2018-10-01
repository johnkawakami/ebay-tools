<?php
use PHPUnit\Framework\TestCase;

class TraitSKUsWithNamesTest extends TestCase 
{
    private $mock;
    private $active;

    public function setUp()
    {
        // create a data directory, and make a fake SKUs directory structure in there
        $dir = __DIR__.'/data/';

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($dir);
        } catch ( Exception $e ) {
            // ignore missing data dir
        }

        mkdir($dir);
        mkdir($dir.'active/');
        mkdir($dir.'active/i100');
        mkdir($dir.'active/I101');
        mkdir($dir.'active/i102');
        touch($dir.'active/i102/file1.txt');
        mkdir($dir.'active/I102 some name');
        touch($dir.'active/I102 some name/file2.txt');
        mkdir($dir.'active/I103 some name');
        file_put_contents($dir.'active/I103 some name/file1.txt', 'first copy');
        file_put_contents($dir.'active/I103 some name/file2.txt', 'first copy');
        mkdir($dir.'active/I103 name');
        file_put_contents($dir.'active/I103 name/file1.txt', 'second copy');
        file_put_contents($dir.'active/I103 name/file2.txt', 'first copy');

        $this->active = $dir.'active/';
        $this->mock = $this->getMockForTrait(Particular\Ebay\Traits\SKUsWithNames::class);
    }

    public function testGetSKU()
    {
        $name = $this->mock->getSKUFromFilename('I103 fake name');
        $this->assertEquals( $name, 'i103' );
    }
    public function testGetSKUDirs()
    {
        $s = $this->mock->getAllDirsMatchingSKU( $this->active, 'i102' );
        $this->assertEquals( 2, count($s) );

        $s = $this->mock->getLongestDirFromSKU( $this->active, 'i102' );
        $this->assertEquals( 'I102 some name', $s );
    }
    public function testMerge()
    {
        $this->mock->mergeDirs( $this->active.'i102', $this->active.'I102 some name' );
        $this->assertFalse( file_exists($this->active.'I102 some name') );
        $this->assertFileExists( $this->active.'i102/file2.txt' );
    }
    public function testMergeSKU()
    {
        $this->mock->mergeDirsForSKU( $this->active, 'I102' );
        $this->assertFalse( file_exists($this->active.'i102') );
        $this->assertFileExists( $this->active.'I102 some name/file1.txt' );
    }
    public function testMergeSKUWithDuplicates()
    {
        $this->mock->mergeDirsForSKU( $this->active, 'I103' );
        // merging deletes the shorter
        $this->assertFalse( file_exists($this->active.'I103 name') );
        // merging should retain the longest name
        $this->assertFileExists( $this->active.'I103 some name/file1.txt' );
        // the two copies of file1 are different, and should clobber each other
        $this->assertFileExists( $this->active.'I103 some name/file1 1.txt' );
        $this->assertFileExists( $this->active.'I103 some name/file2.txt' );
        // file2 is the same in both and should have been deleted
        $this->assertFalse( file_exists($this->active.'I103 some name/file2 1.txt') );
    }
    public function testRenameToCanonical()
    {
        $this->mock->renameToCanonicalSKU( $this->active.'I103 some name' );
        $this->assertFileExists( $this->active.'i103' );
    }
}

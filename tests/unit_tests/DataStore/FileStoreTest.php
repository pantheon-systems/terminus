<?php

namespace Pantheon\Terminus\UnitTests\DataStore;

use Pantheon\Terminus\DataStore\FileStore;

class FileStoreTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->tmp = tempnam(sys_get_temp_dir(), 'terminus_test_');
        unlink($this->tmp);

        $this->filestore = new FileStore($this->tmp);
    }

    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->tmp)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tmp, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $rm = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $rm($fileinfo->getRealPath());
            }

            rmdir($this->tmp);
        }
    }

    public function testGetSet()
    {
        // Test the empty state. The directory should not exist yet.
        $this->assertEquals([], $this->filestore->keys());

        $this->assertFalse($this->filestore->has('foo'));
        $this->assertFalse($this->filestore->has('bar'));

        // This should create the directory and
        $this->filestore->set('foo', '123');
        $this->filestore->set('bar', '456');

        // Create a new object to ensure that the previous one actually persisted the data.
        $this->filestore = new FileStore($this->tmp);

        $this->assertTrue($this->filestore->has('foo'));
        $this->assertTrue($this->filestore->has('bar'));

        $this->assertEquals('123', $this->filestore->get('foo'));
        $this->assertEquals('456', $this->filestore->get('bar'));

        // keys() makes no guarantee about the order of keys returned or their indices in the array.
        $actual = $this->filestore->keys();
        sort($actual);
        $this->assertEquals(['bar', 'foo'], $actual);

        $this->filestore->remove('foo');
        $this->assertFalse($this->filestore->has('foo'));
        $this->assertEquals(['bar'], array_values($this->filestore->keys()));
        
        // Key cleaning
        $this->filestore->set('foo/bar&baz!bop', '123');
        $this->assertTrue($this->filestore->has('foo/bar&baz!bop'));
        $this->assertEquals('123', $this->filestore->get('foo/bar&baz!bop'));
    }
}

<?php

use App\Interfaces\InterfaceFileStorage;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserFileStorageTest extends TestCase
{
    /**
     * User file storage manipulation object
     * @var \App\Components\UserFileStorage
     */
    private $userFileStorage;

    /**
     * /tmp dir emulation
     * @var string
     */
    private $tmpDir;

    /**
     * Path to test storage
     * @var string
     */
    private $storageDir;

    public function setUp()
    {
        parent::setUp();

        $this->userFileStorage = $this->app->make(InterfaceFileStorage::class);

        $this->tmpDir = $this->testsDir . '/data/files/tmp';
        $this->storageDir = $this->testsDir . '/data/files/storage';

        File::deleteDirectory($this->tmpDir);
        File::copyDirectory($this->testsDir . '/data/files/upload', $this->tmpDir);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->userFileStorage = null;

        File::deleteDirectory($this->tmpDir);
        File::cleanDirectory($this->storageDir);
    }

    /**
     * Test add file to storage
     *
     * @return void
     */
    public function testAddFile()
    {
        $this->userFileStorage->addFile($this->tmpDir . '/test-valid.txt');
        $this->assertFalse($this->userFileStorage->hasErrors());
        $this->assertTrue(file_exists($this->storageDir . '/test-valid.txt'));
    }

    /**
     * Test add invalid files to storage
     */
    public function testAddInvalidFiles()
    {
        $this->assertFalse($this->userFileStorage->addFile($this->tmpDir . '/test-invalid-size.txt'));
        $this->assertTrue($this->userFileStorage->hasErrors());
        $this->assertTrue(isset($this->userFileStorage->getErrors()['file']));
        $this->assertTrue(preg_match('#file is very large#i', current($this->userFileStorage->getErrors()['file'])) === 1);
        $this->userFileStorage->resetErrors();

        $this->assertFalse($this->userFileStorage->addFile($this->tmpDir . '/test-invalid-extension.bad'));
        $this->assertTrue($this->userFileStorage->hasErrors());
        $this->assertTrue(isset($this->userFileStorage->getErrors()['file']));
        $this->assertTrue(preg_match('#file extension is not allowed#i', current($this->userFileStorage->getErrors()['file'])) === 1);
        $this->userFileStorage->resetErrors();

        $this->assertFalse($this->userFileStorage->addFile($this->tmpDir . '/test-invalid-phrase.txt'));
        $this->assertTrue($this->userFileStorage->hasErrors());
        $this->assertTrue(isset($this->userFileStorage->getErrors()['file']));
        $this->assertTrue(preg_match('#file contains forbidden words#i', current($this->userFileStorage->getErrors()['file'])) === 1);
        $this->userFileStorage->resetErrors();
    }
}

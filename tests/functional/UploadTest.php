<?php

use App\Components\UserFileStorage;
use App\Interfaces\InterfaceFileStorage;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UploadTest extends TestCase
{
    use WithoutMiddleware;

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

        $this->tmpDir = $this->testsDir . '/data/files/tmp';
        $this->storageDir = $this->testsDir . '/data/files/storage';

        File::deleteDirectory($this->tmpDir);
        File::copyDirectory($this->testsDir . '/data/files/upload', $this->tmpDir);
    }

    public function tearDown()
    {
        parent::tearDown();

        File::deleteDirectory($this->tmpDir);
        File::cleanDirectory($this->storageDir);
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testUploadValidation()
    {
        File::deleteDirectory($this->tmpDir);
        File::copyDirectory($this->testsDir . '/data/files/upload', $this->tmpDir);

        // Upload file with not allowed large size
        $this->makeRequest('POST', route('upload'), [], [], [
            'file' => new Symfony\Component\HttpFoundation\File\UploadedFile(
                realpath($this->tmpDir . '/test-invalid-size.txt'),
                'test-invalid-size.txt',
                'text/plain',
                null,
                null,
                true
            ),
        ])
             ->see('file is very large');

        // Upload file with not allowed extension
        $this->makeRequest('POST', route('upload'), [], [], [
            'file' => new Symfony\Component\HttpFoundation\File\UploadedFile(
                realpath($this->tmpDir . '/test-invalid-extension.bad'),
                'test-invalid-extension.bad',
                'text/plain',
                null,
                null,
                true
            ),
        ])
             ->see('file extension is not allowed');

        // Upload file with forbidden words
        $this->makeRequest('POST', route('upload'), [], [], [
            'file' => new Symfony\Component\HttpFoundation\File\UploadedFile(
                realpath($this->tmpDir . '/test-invalid-phrase.txt'),
                'test-invalid-phrase.txt',
                'text/plain',
                null,
                null,
                true
            ),
        ])
             ->see('file contains forbidden words');

        File::deleteDirectory($this->tmpDir);
    }

    public function testUploadValidFileToStorage()
    {
        // Upload valid file
        $this->makeRequest('POST', route('upload'), [], [], ['file' => new Symfony\Component\HttpFoundation\File\UploadedFile(
            realpath($this->tmpDir . '/test-valid.txt'),
            'test-valid.txt',
            'text/plain',
            null,
            null,
            true
        )]);

        // Checking whether a file exists in storage
        $this->assertTrue(file_exists($this->storageDir . '/test-valid.txt'));
    }
}

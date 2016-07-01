<?php

use App\Components\AmqpWrapper;
use App\Components\UserFileStorage;
use App\Interfaces\InterfaceFileStorage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Tests root directory
     * @var string
     */
    protected $testsDir;

    public function setUp()
    {
        parent::setUp();

        $this->testsDir = realpath(__DIR__);
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $app->singleton(AMQPStreamConnection::class, function ($app) {
            $amqpStreamMock = $this->getMockBuilder(AMQPStreamConnection::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

            return $amqpStreamMock;
        });

        $app->singleton(AmqpWrapper::class, function ($app) {
            $amqpWrapperMock = $this->getMockBuilder(AmqpWrapper::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

            return $amqpWrapperMock;
        });

        $app->bind(InterfaceFileStorage::class, function ($app) {
            /* @var \App\Components\UserFileStorage $userFileStorageMock */
            $userFileStorageMock = $this->getMockBuilder(UserFileStorage::class)
                                        ->setMethods(['getValidationRules'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

            $userFileStorageMock->expects($this->any())
                                ->method('getValidationRules')
                                ->will($this->returnValue([
                                    [
                                        'extension' => 'txt',
                                        'max_size' => 10000,
                                        'stopwords' => 'invalid first phrase',
                                    ],
                                    [
                                        'extension' => 'txt',
                                        'max_size' => 999999,
                                        'stopwords' => 'invalid second phrase, invalid third phrase',
                                    ],
                                ]));

            $userFileStorageMock->setStoragePath($this->testsDir . '/data/files/storage');
            $userFileStorageMock->setValidator($this->app->make(\Illuminate\Contracts\Validation\Factory::class));
            $userFileStorageMock->initValidator();

            return $userFileStorageMock;
        });

        return $app;
    }
}

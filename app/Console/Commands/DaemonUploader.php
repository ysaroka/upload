<?php

namespace App\Console\Commands;

use App\Components\AmqpReceiver;
use App\Components\Uploader;
use App\UploadEntity;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Logging\Log;
use WebSocket\Client as WebSocketClient;

class DaemonUploader extends Command
{
    /**
     * Delay in seconds to reconnect to the AMQP server.
     */
    const AMQP_RECONNECT_DELAY = 5;

    /**
     * The interval in bytes call progress functions when uploading
     */
    const PROGRESS_BYTE_INTERVAL = 524288;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daemon:uploader';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uploader daemon.';

    /**
     * Logger object
     * @var \Illuminate\Contracts\Logging\Log
     */
    private $logger;

    /**
     * Laravel config object
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * AMQP receiver object
     * @var \App\Components\AmqpReceiver
     */
    private $amqpReceiver;

    /**
     * WebSocket client
     * @var \WebSocket\Client
     */
    private $wsClient;

    /**
     * Asynchronous uploader object
     * @var \App\Components\Uploader
     */
    private $uploader;

    /**
     * Create a new command instance.
     * @param \Illuminate\Contracts\Logging\Log $logger
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \App\UploadEntity $uploadEntity
     */
    public function __construct(Log $logger, Repository $config, UploadEntity $uploadEntity)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->config = $config;
        $this->uploadEntity = $uploadEntity;
    }

    /**
     * Execute the console command.
     * @param \App\Components\AmqpReceiver $amqpReceiver
     * @param \WebSocket\Client $wsClient
     * @param \App\Components\Uploader $uploader
     * @return mixed
     */
    public function handle(AmqpReceiver $amqpReceiver, WebSocketClient $wsClient, Uploader $uploader)
    {
        $this->amqpReceiver = $amqpReceiver;
        $this->wsClient = $wsClient;
        $this->uploader = $uploader;

        $this->runAmqpListener();
    }

    /**
     * Recieve AMQP message from upload queue event
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function onAMQPUploadMessage($message)
    {
        try {
            $message = json_decode($message->body, true);

            if (isset($message['file']) && isset($message['url'])) {
                $this->uploadProcess($message['file'], $message['url']);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ' | ' . $e->getMessage());
        }
    }

    /**
     * Recieve AMQP message from progress queue event
     * @param \PhpAmqpLib\Message\AMQPMessage $amqpMessage
     */
    public function onAMQPProgressMessage($amqpMessage)
    {
        try {
            $message = json_decode($amqpMessage->body, true);

            if (isset($message['status'])) {
                if ($message['status'] == 'success') {
                    $this->uploadEntity->create([
                        'server' => $message['server']['scheme'] . '://' . $message['server']['host'],
                        'original_name' => $message['original_filename'],
                        'upload_name' => rtrim($message['server']['path'], '\/') . DIRECTORY_SEPARATOR . $message['upload_name'],
                        'status' => 1,
                        'status_message' => $message['status_message'],
                    ]);
                } elseif (($message['status'] == 'error')) {
                    $this->uploadEntity->create([
                        'server' => $message['server']['scheme'] . '://' . $message['server']['host'],
                        'original_name' => $message['original_filename'],
                        'upload_name' => rtrim($message['server']['path'], '\/') . DIRECTORY_SEPARATOR . $message['upload_name'],
                        'status' => 0,
                        'status_message' => $message['status_message'],
                    ]);
                }

                $this->sendWSMessage($amqpMessage->body);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ' | ' . $e->getMessage());
        }
    }

    /**
     * Run AMQP listener
     */
    private function runAmqpListener()
    {
        try {
            $amqpReceiver = $this->getAmqpReceiver();

            $amqpReceiver->queueAddListener($this->config->get('amqp.queues.uploader.upload'), [
                $this,
                'onAMQPUploadMessage',
            ]);
            $amqpReceiver->queueAddListener($this->config->get('amqp.queues.uploader.progress'), [
                $this,
                'onAMQPProgressMessage',
            ]);
            $amqpReceiver->listen(' [*] Uploader daemon waiting for messages in AMQP queue "' . $this->config->get('amqp.queues.uploader.upload') . '". To exit press CTRL+C' . "\n");
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ' | ' . $e->getMessage());
            sleep(static::AMQP_RECONNECT_DELAY);
            $this->runAmqpListener();
        }
    }

    /**
     * $this->amqpConnection getter
     * @return \App\Components\AmqpReceiver
     */
    private function getAmqpReceiver()
    {
        $this->amqpReceiver->reconnect();

        return $this->amqpReceiver;
    }

    /**
     * Send message to WebSocket server
     * If server offline - attempt to reconnect
     * @param $message
     * @throws \WebSocket\BadOpcodeException
     */
    private function sendWSMessage($message)
    {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }

        try {
            $this->wsClient->send($message);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ' | ' . 'WebSocket client error. Reason: ' . $e->getMessage() . '.');
        }
    }

    private function uploadProcess($file, $url)
    {
        $amqp = "amqp://{$this->config->get('amqp.connection.user')}:{$this->config->get('amqp.connection.password')}@{$this->config->get('amqp.connection.host')}:{$this->config->get('amqp.connection.port')}/{$this->config->get('amqp.queues.uploader.progress')}";
        $this->uploader->detachUploadProcess($file, $url, $amqp);
    }
}

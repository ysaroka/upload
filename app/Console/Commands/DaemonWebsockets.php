<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Logging\Log;
use Ratchet\App as RatchetApp;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class DaemonWebsockets extends Command implements MessageComponentInterface
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daemon:websockets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'WebSockets daemon.';

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
     * WebSockets connection
     * @var \Ratchet\App
     */
    private $wsConnection;

    /**
     * Current connected clients
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * Create a new command instance.
     * @param \Illuminate\Contracts\Logging\Log $logger
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Log $logger, Repository $config)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->config = $config;

        $this->clients = new \SplObjectStorage();
    }

    /**
     * Execute the console command.
     *
     * @param \Ratchet\App $wsConnection
     * @return mixed
     */
    public function handle(RatchetApp $wsConnection)
    {
        $this->wsConnection = $wsConnection;

        $this->runWebsocketListener();
    }

    private function runWebsocketListener()
    {
        try {
            $this->wsConnection->route('/' . $this->config->get('daemons.wsserver.path'), $this, array('*'));

            echo ' [*] Websockets broker daemon waiting for messages. To exit press CTRL+C', "\n";

            $this->wsConnection->run();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ' | ' . $e->getMessage());
        }
    }

    /**
     * Client connect event
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    /**
     * Recieve message event
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Send messages to all clients except current
        foreach ($this->clients as $client) {
            /* @var \Ratchet\WebSocket\Version\RFC6455\Connection $client */
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }

    /**
     * Client disconnect event
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    /**
     * Error handler
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error(__METHOD__ . ' | ' . $e->getMessage());
        $conn->close();
    }
}

<?php
/**
 * Created: 2016-06-26
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace App\Components;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpReceiver
{
    /**
     * AMQP connection object
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    private $amqpConnection;

    /**
     * AMQP channel object
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel;

    /**
     * Declared queues array
     * @var array
     */
    private $declaredQueues = [];

    /**
     * AmqpReceiver constructor.
     * @param \PhpAmqpLib\Connection\AMQPStreamConnection $amqpConnection
     * @param \PhpAmqpLib\Message\AMQPMessage $amqpMessage
     */
    public function __construct(AMQPStreamConnection $amqpConnection, AMQPMessage $amqpMessage)
    {
        $this->amqpConnection = $amqpConnection;
        $this->channel = $this->amqpConnection->channel();
    }

    /**
     * Declare amqp queue
     * @param $queueName
     * @param bool|false $passive
     * @param bool|false $durable
     * @param bool|false $exclusive
     * @param bool|false $auto_delete
     */
    public function declareQueue($queueName, $passive = false, $durable = false, $exclusive = false, $auto_delete = false)
    {
        if (!in_array($queueName, $this->declaredQueues)) {
            $this->channel->queue_declare($queueName, $passive, $durable, $exclusive, $auto_delete);
            $this->declaredQueues[] = $queueName;
        }
    }

    /**
     * Add amqp queue to amqp channel
     * @param $queueName
     * @param $callback
     */
    public function queueAddListener($queueName, $callback)
    {
        $this->declareQueue($queueName);
        $this->channel->basic_consume($queueName, '', false, true, false, false, $callback);
    }

    /**
     * Listen declared queues
     * @param null $cliMessage
     */
    public function listen($cliMessage = null)
    {
        if (!empty($cliMessage)) {
            echo $cliMessage;
        }

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Reconnect amqp connection
     */
    public function reconnect()
    {
        if (!$this->amqpConnection->isConnected()) {
            $this->amqpConnection->reconnect();
            $this->channel = $this->amqpConnection->channel();
            $this->declaredQueues = [];
        }
    }

    /**
     * Disconnect amqp connection
     */
    public function disconnect()
    {
        $this->channel->close();
        $this->amqpConnection->close();
    }
}
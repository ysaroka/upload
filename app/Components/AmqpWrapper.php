<?php
/**
 * Created: 2016-06-26
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace App\Components;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpWrapper
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
     * AMQP message object
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    private $amqpMessage;

    /**
     * Declared queues array
     * @var array
     */
    private $declaredQueues = [];

    /**
     * AmqpWrapper constructor.
     * @param \PhpAmqpLib\Connection\AMQPStreamConnection $amqpConnection
     * @param \PhpAmqpLib\Message\AMQPMessage $amqpMessage
     */
    public function __construct(AMQPStreamConnection $amqpConnection, AMQPMessage $amqpMessage)
    {
        $this->amqpConnection = $amqpConnection;
        $this->reconnect();
        $this->amqpMessage = $amqpMessage;
        $this->channel = $amqpConnection->channel();
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
     * Send message to declared queue
     * @param $queueName
     * @param $message
     */
    public function sendMessage($queueName, $message)
    {
        $this->declareQueue($queueName);
        $this->amqpMessage->setBody($message);
        $this->channel->basic_publish($this->amqpMessage, '', $queueName);
    }

    /**
     * Return amqp connection object
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    public function getConnection() {
        return $this->amqpConnection;
    }

    /**
     * Reconnect amqp connection
     */
    public function reconnect()
    {
        if (!$this->amqpConnection->isConnected()) {
            $this->amqpConnection->reconnect();
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
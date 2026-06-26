<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    /**
     * Publish a message to RabbitMQ exchange.
     *
     * @param string $routingKey  The routing key for the message.
     * @param array  $data        The payload data to send as JSON.
     */
    public function publish(string $routingKey, array $data): void
    {
        $host  = env('RABBITMQ_HOST', '127.0.0.1');
        $port  = (int) env('RABBITMQ_PORT', 5672);
        $user  = env('RABBITMQ_USER', 'guest');
        $pass  = env('RABBITMQ_PASSWORD', 'guest');
        $vhost = env('RABBITMQ_VHOST', '/');

        $conn = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        $ch   = $conn->channel();

        $exchange = env('RABBITMQ_EXCHANGE', 'iae.central.exchange');
        $ch->exchange_declare($exchange, 'topic', false, true, false);

        $msg = new AMQPMessage(json_encode($data), ['content_type' => 'application/json']);
        $ch->basic_publish($msg, $exchange, $routingKey);

        $ch->close();
        $conn->close();
    }
}

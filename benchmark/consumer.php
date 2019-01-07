<?php

use Amp\Loop;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Client;
use PHPinnacle\Ridge\Config;
use PHPinnacle\Ridge\Message;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(function () {
    $client = new Client(Config::dsn('amqp://admin:admin123@172.23.0.2'));

    yield $client->connect();

    /** @var Channel $channel */
    $channel = yield $client->channel();

    yield $channel->queueDeclare('bench_queue');
    yield $channel->exchangeDeclare('bench_exchange');
    yield $channel->queueBind('bench_queue', 'bench_exchange');

    $t = null;
    $count = 0;

    yield $channel->consume(function (Message $message) use (&$t, &$count, $client) {
        if ($t === null) {
            $t = \microtime(true);
        }

        if ($message->content() === 'quit') {
            \printf('Pid: %s, Count: %s, Time: %.4f' . \PHP_EOL, \getmypid(), $count, \microtime(true) - $t);

            yield $client->disconnect();
        } else {
            ++$count;
        }

    }, 'bench_queue', '', false, true);
});
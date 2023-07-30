<?php


use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

require_once __DIR__.'/../vendor/autoload.php';


$urlStr = "";
$url = parse_url($urlStr);
$vhost = substr($url['path'], 1);

if($url['scheme'] === "amqps") {
    $ssl_opts = array(
        'capath' => '/etc/ssl/certs'
    );
    $connection = new AMQPSSLConnection($url['host'], 5671, $url['user'], $url['pass'], $vhost, $ssl_opts);
} else {
    $connection = new AMQPStreamConnection($url['host'], 5672, $url['user'], $url['pass'], $vhost);
}

//$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

/*
Назва черги: це довільна назва, яка використовуватиметься для ідентифікації черги
Пасивний: якщо встановлено значення true, сервер лише перевірятиме, чи можна створити чергу, false фактично намагатиметься створити чергу.
Довговічний: як правило, якщо сервер зупиняється або виходить з ладу, усі черги та повідомлення втрачаються… якщо ми не оголосимо чергу довговічною, у цьому випадку черга збережеться, якщо сервер буде перезапущено.
Ексклюзив: якщо встановлено значення true, чергу може використовувати лише з’єднання, яке її створило.
Автовидалення: якщо істинно, чергу буде видалено, коли в ній не залишиться повідомлень і немає підключених абонентів
*/
$channel->queue_declare('test_queue', false, true, false, false);
//$channel->queue_declare('test_queue', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done\n";
    $msg->ack();
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('test_queue', 'tag01', false, false, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();

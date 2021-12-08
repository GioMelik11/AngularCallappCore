<?php

set_time_limit(0);

require 'vendor/autoload.php';
require 'Configs/config.php';

use IO\Controller;

$loop = React\EventLoop\Factory::create();

$server = new Ratchet\App(APP_IP, APP_PORT, '0.0.0.0', $loop);
echo $GLOBALS['userId'];

$server->route('/', new Controller($loop), array('*'));
// $server->route('/Queue', new Queue($loop), array('*'));

$server->run();


// $Queue = new Queue();

// $server = IoServer::factory(new HttpServer(new WsServer($Queue)), 8080);

// $server->loop->addPeriodicTimer(5, function () use ($Queue) {
    
//     foreach($Queue->clients as $client)
//     {
//         $client->send(json_encode($Queue->phoneTaken));    
//     }
//     });
    
    
// $server->run();

<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Socket;

require 'C:\xampp\htdocs\wzrd\vendor\autoload.php';



$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Socket()
        )
    ),
    8080,
    "192.168.178.190"
);

$server->run();

$i = 0;

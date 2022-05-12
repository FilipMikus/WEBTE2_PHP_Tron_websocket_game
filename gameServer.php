<?php
use Workerman\Lib\Timer;
use Workerman\Worker;
require_once "/var/www/site131.webte.fei.stuba.sk/zadanie_6/vendor/autoload.php";

$configuration = [
    "ssl" => [
        "local_cert" => "/etc/ssl/certs/webte.fei.stuba.sk-chain-cert.pem",
        "local_pk" => "/etc/ssl/private/webte.fei.stuba.sk.key",
        "verify_peer" => false,
    ]
];

$ws_worker = new Worker("websocket://0.0.0.0:9000", $configuration);
$ws_worker->transport = 'ssl';
$ws_worker->count = 1;

$GLOBALS["playersId"] = [];
$GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
$GLOBALS["playerOnePosition"] = array(1, 2);
$GLOBALS["playerTwoPosition"] = array(48, 47);
$GLOBALS["playerOneDirection"] = array(1, 0);
$GLOBALS["playerTwoDirection"] = array(-1, 0);

$ws_worker->onWorkerStart = function ($ws_worker) {

    $ws_worker->onConnect = function ($connection) {
        $connection->onWebSocketConnect = function ($connection) {
            array_push($GLOBALS["playersId"], $connection->id);
            $GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
            $GLOBALS["playerOnePosition"] = array(1, 2);
            $GLOBALS["playerTwoPosition"] = array(48, 47);
            $GLOBALS["playerOneDirection"] = array(1, 0);
            $GLOBALS["playerTwoDirection"] = array(-1, 0);
        };
    };

    $ws_worker->onMessage = function ($connection, $data) {
        if($GLOBALS["playersId"][0] == $connection->id) {
            $GLOBALS["playerOneDirection"] = json_decode($data);
        } elseif($GLOBALS["playersId"][1] == $connection->id) {
            $GLOBALS["playerTwoDirection"] = json_decode($data);
        }
    };

    $ws_worker->onClose = function ($connection) {
        $pos = array_search($connection->id, $GLOBALS["playersId"]);
        array_splice($GLOBALS["playersId"], $pos, 1);
        $GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
        $GLOBALS["playerOnePosition"] = array(1, 2);
        $GLOBALS["playerTwoPosition"] = array(48, 47);
        $GLOBALS["playerOneDirection"] = array(1, 0);
        $GLOBALS["playerTwoDirection"] = array(-1, 0);
    };


    Timer::add(0.5, function () use ($ws_worker) {
        if(count($ws_worker->connections) != 0) {
            $GLOBALS["playerOnePosition"][0] += $GLOBALS["playerOneDirection"][0];
            $GLOBALS["playerOnePosition"][1] += $GLOBALS["playerOneDirection"][1];
            $GLOBALS["playerTwoPosition"][0] += $GLOBALS["playerTwoDirection"][0];
            $GLOBALS["playerTwoPosition"][1] += $GLOBALS["playerTwoDirection"][1];

            // Detekcia kolízie s mantinelmi hráč 1:
            if($GLOBALS["playerOnePosition"][0] == -1 || $GLOBALS["playerOnePosition"][0] == 50 ||
                $GLOBALS["playerOnePosition"][1] == -1 || $GLOBALS["playerOnePosition"][1] == 50) {
                $GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
                $GLOBALS["playerOnePosition"] = array(1, 2);
                $GLOBALS["playerTwoPosition"] = array(48, 47);
                $GLOBALS["playerOneDirection"] = array(1, 0);
                $GLOBALS["playerTwoDirection"] = array(-1, 0);
            }

            // Detekcia kolízie s mantinelmi hráč 2:
            if($GLOBALS["playerTwoPosition"][0] == -1 || $GLOBALS["playerTwoPosition"][0] == 50 ||
                $GLOBALS["playerTwoPosition"][1] == -1 || $GLOBALS["playerTwoPosition"][1] == 50) {
                $GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
                $GLOBALS["playerOnePosition"] = array(1, 2);
                $GLOBALS["playerTwoPosition"] = array(48, 47);
                $GLOBALS["playerOneDirection"] = array(1, 0);
                $GLOBALS["playerTwoDirection"] = array(-1, 0);
            }

            // Detekcia zahryznutia do seba alebo do súpera hráč 1:
            if($GLOBALS["gameCanvas"][$GLOBALS["playerOnePosition"][0]][$GLOBALS["playerOnePosition"][1]] == 1 ||
                $GLOBALS["gameCanvas"][$GLOBALS["playerOnePosition"][0]][$GLOBALS["playerOnePosition"][1]] == 2) {
                $GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
                $GLOBALS["playerOnePosition"] = array(1, 2);
                $GLOBALS["playerTwoPosition"] = array(48, 47);
                $GLOBALS["playerOneDirection"] = array(1, 0);
                $GLOBALS["playerTwoDirection"] = array(-1, 0);
            }

            // Detekcia zahryznutia do seba alebo do súpera hráč 2:
            if($GLOBALS["gameCanvas"][$GLOBALS["playerTwoPosition"][0]][$GLOBALS["playerTwoPosition"][1]] == 1 ||
                $GLOBALS["gameCanvas"][$GLOBALS["playerTwoPosition"][0]][$GLOBALS["playerTwoPosition"][1]] == 2) {
                $GLOBALS["gameCanvas"] = array_fill(0,50, array_fill(0, 50,0));
                $GLOBALS["playerOnePosition"] = array(1, 2);
                $GLOBALS["playerTwoPosition"] = array(48, 47);
                $GLOBALS["playerOneDirection"] = array(1, 0);
                $GLOBALS["playerTwoDirection"] = array(-1, 0);
            }

            $GLOBALS["gameCanvas"][$GLOBALS["playerOnePosition"][0]][$GLOBALS["playerOnePosition"][1]] = 1;
            $GLOBALS["gameCanvas"][$GLOBALS["playerTwoPosition"][0]][$GLOBALS["playerTwoPosition"][1]] = 2;

            foreach ($ws_worker->connections as $connection) {
                $connection->send(json_encode($GLOBALS["gameCanvas"]));
            }

        }

    });

};

Worker::runAll();

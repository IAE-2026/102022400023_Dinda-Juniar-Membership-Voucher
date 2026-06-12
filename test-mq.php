<?php
require 'vendor/autoload.php';
$vhosts = ['/', '/iae', 'iae', 'TEAM-06', '/TEAM-06', 'iae.central.exchange', 'smart_parking'];
foreach ($vhosts as $vhost) {
    try {
        $c = new PhpAmqpLib\Connection\AMQPStreamConnection('iae-sso.virtualfri.id', 5672, 'mahasiswa', 'rahasia', $vhost);
        echo "SUCCESS WITH VHOST: $vhost\n";
        break;
    } catch(Exception $e) {
        echo "FAILED $vhost: " . $e->getMessage() . "\n";
    }
}


<?php
require __DIR__ . '/../vendor/autoload.php';

$r = new ReflectionMethod('Firebase\\JWT\\JWT', 'decode');
foreach ($r->getParameters() as $p) {
    echo $p->getName() . ' => ' . ($p->isPassedByReference() ? 'byref' : 'byval') . PHP_EOL;
}

echo 'Method info:\n';
print_r($r);

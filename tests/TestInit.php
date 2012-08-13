<?php
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    echo "Please `./composer.phar install --dev` first!" . PHP_EOL;
    exit(2);
}
require_once $autoloader;

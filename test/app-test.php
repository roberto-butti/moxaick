<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use Rbit\Moxaick\Image;

$time_start = microtime(true);
$i = new Image("test/test.jpg");

$i->load_image();
$i->split_cells(60,80, false);
$i->unload_image();
$time_end = microtime(true);

$execution_time = ($time_end - $time_start);

echo '<b>Total Execution Time:</b> '.$execution_time.' Seconds';
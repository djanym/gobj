<?php

$time_start = microtime(true);

include __DIR__ . '/settings.php';

if ( ! defined( 'ABSPATH' ) ) {
    die('Error.');
}

include ABSPATH . 'handler.php';

if ( defined( 'DEBUG_MODE' ) ) {
    echo 'Total execution time in seconds: '.( microtime( true ) - $time_start );
}

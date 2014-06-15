<?php

$time_start = microtime(true);

include_once "./api.php";

function my_callback($param = array()) {
    return array(
        "data"  => array(
            1, 2, 3
        )
    );
}

$api = new \api\api();
$api->execute();

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo '<b>Total Execution Time:</b> '.$execution_time.' Mins';
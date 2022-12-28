<?php


function aitrillion_api_log($message = ''){

    $log = '------------'.date('Y-m-d H:i:s').'---------------'.PHP_EOL;
    $log .= $message;
    
    file_put_contents(AIT_PATH.'aitrillion-log.txt', $log.PHP_EOL, FILE_APPEND);
}

?>
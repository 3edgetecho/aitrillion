<?php


function aitrillion_api_log($message = ''){

    file_put_contents(AIT_PATH.'aitrillion-log.txt', '------------'.date('Y-m-d H:i:s').'---------------'.PHP_EOL, FILE_APPEND);

    file_put_contents(AIT_PATH.'aitrillion-log.txt', $message.PHP_EOL, FILE_APPEND);
}

?>
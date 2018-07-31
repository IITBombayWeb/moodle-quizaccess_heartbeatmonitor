<?php
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$phpws_result = socket_connect($socket, '127.0.0.1', 3000);
if(!$phpws_result) {
//     exec("node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js 2>&1", $output);
       // die('cannot connect '.socket_strerror(socket_last_error()).PHP_EOL);
}

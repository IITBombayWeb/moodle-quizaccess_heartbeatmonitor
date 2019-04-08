<?php

unset($HBCFG);
global $DB, $HBCFG;
$HBCFG = new stdClass();

$HBCFG->host = null;
$HBCFG->port = null;
$HBCFG->wwwroot = null;

// function hbmonconfig(){
//     global $DB, $HBCFG;

    $record = $DB->get_record('quizaccess_hbmon_node', array(), '*', MUST_EXIST);
    // $HBCFG->host = '10.102.6.135';
    // $HBCFG->host = 'localhost';
    // $HBCFG->port = 3000;

    $HBCFG->host = $record->nodehost;
    $HBCFG->port = $record->nodeport;

    // $HBCFG->wwwroot = 'http://10.102.6.135';
    $HBCFG->wwwroot = 'http://localhost';

    echo '<br> hbcfg ----cfg file-------------';
    print_object($HBCFG);

//     return $HBCFG;
// }
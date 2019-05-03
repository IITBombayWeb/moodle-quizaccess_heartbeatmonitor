<?php

unset($HBCFG);
global $DB, $HBCFG;

$HBCFG = new stdClass();

$record = $DB->get_record('quizaccess_hbmon_node', array('id' => 1), '*');
if($record) {
	$HBCFG->host = $record->nodehost;
	$HBCFG->port = $record->nodeport;
	$HBCFG->wwwroot = 'http://' . $HBCFG->host;
} else {
	$HBCFG->host = 'localhost';
        $HBCFG->port = 3000;
        $HBCFG->wwwroot = 'http://' . $HBCFG->host;
}

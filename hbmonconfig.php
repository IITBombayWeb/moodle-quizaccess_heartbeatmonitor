<?php

unset($HBCFG);
global $DB, $HBCFG;

$HBCFG = new stdClass();
$id = $DB->get_field_sql("SELECT MAX(id) FROM {quizaccess_hbmon_node}");

$record = $DB->get_record('quizaccess_hbmon_node', array('id' => $id), '*');
/*
if($record) {
	$HBCFG->host = $record->nodehost;
	$HBCFG->port = $record->nodeport;
	$HBCFG->wwwroot = 'http://' . $HBCFG->host;
} else {
	#$HBCFG->host = 'localhost';
	$HBCFG->host = '10.102.1.115';
        $HBCFG->port = 3000;
        $HBCFG->wwwroot = 'http://' . $HBCFG->host;
}
*/

	$HBCFG->host = '10.102.1.115';
        $HBCFG->port = 3000;
        $HBCFG->wwwroot = 'http://' . $HBCFG->host;


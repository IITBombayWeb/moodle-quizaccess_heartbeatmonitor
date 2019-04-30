<?php

unset($HBCFG);
global $DB, $HBCFG;

$HBCFG = new stdClass();

$record = $DB->get_record('quizaccess_hbmon_node', array(), '*', MUST_EXIST);

$HBCFG->host = $record->nodehost;
$HBCFG->port = $record->nodeport;
$HBCFG->wwwroot = 'http://' . $HBCFG->host;

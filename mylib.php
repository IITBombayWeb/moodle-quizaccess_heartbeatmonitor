<?php

defined('MOODLE_INTERNAL') || die();
//require_once('../../../../config.php');
require_once($CFG->dirroot . '/config.php');

function debuglog($file, $text) {
//	echo '<br><br><br>in debuglog<br>';
	$micro_date = microtime();
	$date_array = explode(" ", $micro_date);
	$date = date("Y-m-d H:i:s", $date_array[1]);

	$tmp = explode(".", $date_array[0]);

	$contents = $date . "." . $tmp[1] . ": " . $text;

//	echo "<br><br><br><br>I am here <br>";

	file_put_contents($file, $contents);

}




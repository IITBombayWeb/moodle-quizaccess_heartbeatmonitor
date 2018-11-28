<?php

$micro_date = microtime();
$date_array = explode(" ",$micro_date);
$date = date("Y-m-d H:i:s",$date_array[1]);
echo "Date: $date:" . $date_array[0]."<br>";
print_r($date_array);

echo $micro_date;
$text = "Inserted to DB";
$tmp = explode(".",$date_array[0]);

$content= $date . "." . $tmp[1] . ": " . $text;

echo $text . "\n";
echo $content . "\n";

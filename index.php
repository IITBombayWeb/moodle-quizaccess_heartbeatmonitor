<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This page handles listing of quiz overrides
 *
 * @package    mod_quiz
 * @copyright  2010 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
// require_once($CFG->dirroot.'/mod/quiz/lib.php');
// require_once($CFG->dirroot.'/mod/quiz/locallib.php');
// require_once($CFG->dirroot.'/mod/quiz/override_form.php');


$quizid     = required_param('quizid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$cmid       = required_param('cmid', PARAM_INT);
// $mode = optional_param('mode', '', PARAM_ALPHA); // One of 'user' or 'group', default is 'group'.

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
$quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

// Default mode is "user".
$mode = "user";

// Strings.
$pluginname = get_string('pluginname', 'quizaccess_heartbeatmonitor');

$url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));

$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

// Check the user has the required capabilities to access this plugin.
require_capability('mod/quiz:manage', $context);

// Page setup.
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pluginname);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));
echo $OUTPUT->heading(format_string($pluginname, true, array('context' => $context)));

echo '<br>';

// Display live users.
// Fetch records from database.
// mysql_connect('localhost','root','root123');
// mysql_select_db("trialdb");

$servername = "localhost";
$username   = "root";
$password   = "root123";
$dbname     = "trialdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";

$sql = 'SELECT * FROM livetable1';
$result = $conn->query($sql);

$table = new html_table();
$table->head = array('Roomid', 'Status', 'Time to consider', 'Live time', 'Dead time');
updatetime($result, $table);

function updatetime($result, $table) {
if ($result->num_rows > 0) {
    // Output data of each row.
    while($data = $result->fetch_assoc()) {
        //                 $table->data[] = array($data["roomid"], $data["status"], $data["timetoconsider"], $data["livetime"], $data["deadtime"]);

        $roomid          = $data["roomid"];
        $status          = $data["status"];
        $timetoconsider  = $data["timetoconsider"];
        $livetime        = $data["livetime"];
        $deadtime        = $data["deadtime"];

        //                 $now = new DateTime();
        //                 $currentTimestamp = $now->getTimestamp();
        $currentTimestamp = intval(microtime(true)*1000);

        if ($status == 'Live') {
            $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
        } else {
            $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
        }

//         $humanisedlivetime = humanise($livetime);
//         $humaniseddeadtime = humanise($deadtime);

        $humanisedlivetime = secondsToTime(intval($livetime / 1000));
        $humaniseddeadtime = secondsToTime(intval($deadtime / 1000));

//         if(isset($table->rowclasses['roomid'])) {
//             // remove prev. row and add updated row
// //             delete_field::$roomid;

//             $table->rowclasses['roomid'] = $roomid;
//             $row = new html_table_row();
//             $row->id = $roomid;
// //             $row->attributes['class'] = $roomid;

//             $cell1 = new html_table_cell($roomid);
//             $cell2 = new html_table_cell($status);
//             $cell3 = new html_table_cell($timetoconsider);
//             $cell4 = new html_table_cell($humanisedlivetime);
//             $cell5 = new html_table_cell($humaniseddeadtime);

//             $row->cells[] = $cell1;
//             $row->cells[] = $cell2;
//             $row->cells[] = $cell3;
//             $row->cells[] = $cell4;
//             $row->cells[] = $cell5;

//             $table->data[] = $row;
//         } else {
//             echo 'else';

            $table->rowclasses['roomid'] = $roomid;
            $row = new html_table_row();
            $row->id = $roomid;
//             $row->attributes['class'] = $roomid;

            $cell1 = new html_table_cell($roomid);
            $cell2 = new html_table_cell($status);
            $cell3 = new html_table_cell($timetoconsider);
            $cell4 = new html_table_cell($humanisedlivetime);
            $cell5 = new html_table_cell($humaniseddeadtime);

            $row->cells[] = $cell1;
            $row->cells[] = $cell2;
            $row->cells[] = $cell3;
            $row->cells[] = $cell4;
            $row->cells[] = $cell5;

            $table->data[] = $row;
//         }
    }
}
echo html_writer::table($table);

}

function secondsToTime($seconds) {
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');
}

function humanise($difference) {
    $days    = floor($difference / (1000 * 60 * 60 * 24));
    $hours   = floor(($difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    $minutes = floor(($difference % (1000 * 60 * 60)) / (1000 * 60));
    $seconds = floor(($difference % (1000 * 60)) / 1000);
    $time    = $days+' days, '+$hours+' hrs, '+$minutes+' mins, '+$seconds+' secs' ;
    return $time;
}
?>

<script>

// setInterval(function() {
//     $.get("/calculatetime", function(data) {
//         alert('hhi ');

//     });
// }, 1000);

function humanise(difference) {
    var days    = Math.floor(difference / (1000 * 60 * 60 * 24));
    var hours   = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((difference % (1000 * 60)) / 1000);
    var time    = days+' days, '+hours+' hrs, '+minutes+' mins, '+seconds+' secs' ;
    return time;
}

</script>

<?php
// echo 'hi';
$conn->close();

// Finish the page.
echo $OUTPUT->footer();
?>

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
 * Admin module for quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
// require_once($CFG->dirroot.'/mod/quiz/lib.php');
// require_once($CFG->dirroot.'/mod/quiz/locallib.php');
// require_once($CFG->dirroot.'/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimit_override_form.php');
require_once($CFG->dirroot . '/mod/quiz/override_form.php');


$quizid     = required_param('quizid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$cmid       = required_param('cmid', PARAM_INT);
// $mode = optional_param('mode', '', PARAM_ALPHA); // One of 'user' or 'group', default is 'group'.

echo '<br><br><br>';
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
$quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

// Default mode is "user".
$mode = "user";

// Strings.
$pluginname = get_string('pluginname', 'quizaccess_heartbeatmonitor');
$heading    = get_string('heading', 'quizaccess_heartbeatmonitor', $quiz->name);

$url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));
$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

// Check the user has the required capabilities to access this plugin.
require_capability('mod/quiz:manage', $context);

// Display live users.
// Fetch records from database.
$servername = "localhost";
$dbusername = "root";
$dbpassword = "root123";
$dbname     = "trialdb";

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";

$sql = 'SELECT * FROM livetable1';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
$result = $conn->query($sql);
$arr = array();
$roomid = null;

$table = new html_table();
$table->id = 'liveusers';
$table->caption = get_string('liveusers', 'quizaccess_heartbeatmonitor');
$table->head = array('', 'Socket room id', 'Current status', 'Status update on', 'Live time', 'Dead time');

if ($result->num_rows > 0) {
    // Output data of each row.
    while($data = $result->fetch_assoc()) {
        $roomid         = $data["roomid"];
        $arr            = explode("_", $roomid);
        $attemptid      = array_splice($arr, -1)[0];
        $quizid1        = array_splice($arr, -1)[0];
        $username       = implode("_", $arr);
//         echo 'un ' . $username;
        $user           = $DB->get_record('user', array('username'=>$username));
//         print_object($user);
        if($user) {
            $userid         = $user->id;
        }

        if($quizid1 == $quizid) {
            $status          = $data["status"];
            $timetoconsider  = $data["timetoconsider"];
            $livetime        = $data["livetime"];
            $deadtime        = $data["deadtime"];

            $currentTimestamp = intval(microtime(true)*1000);

            if ($status == 'Live') {
                $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
            } else {
                $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
            }

            $humanisedlivetime = secondsToTime(intval($livetime / 1000));
            $humaniseddeadtime = secondsToTime(intval($deadtime / 1000));

            $table->rowclasses['roomid'] = $roomid;
            $row = new html_table_row();
            $row->id = $roomid;
            $row->attributes['class'] = $roomid;

            $value = $roomid . '_' . $deadtime;
            $cell0 = new html_table_cell(
                        html_writer::empty_tag('input', array('type'  => 'checkbox',
                                                              'name'  => 'setoverride',
                                                              'value' => $value,
                                                              'class' => 'setoverride')));
            $cell0->id = 'select';

            $cell1 = new html_table_cell($roomid);
            $cell1->id = 'roomid';

            $cell2 = new html_table_cell($status);
            $cell2->id = 'status';

            $cell3 = new html_table_cell(userdate(intval($timetoconsider / 1000)));
            $cell3->id = 'timetoconsider';

            $cell4 = new html_table_cell($humanisedlivetime);
            $cell4->id = 'livetime';
            $cell4->attributes['value'] = $livetime;

            $cell5 = new html_table_cell($humaniseddeadtime);
            $cell5->id = 'deadtime';
            $cell5->attributes['value'] = $deadtime;

            $row->cells[] = $cell0;
            $row->cells[] = $cell1;
            $row->cells[] = $cell2;
            $row->cells[] = $cell3;
            $row->cells[] = $cell4;
            $row->cells[] = $cell5;

            $table->data[] = $row;
        }
    }
}

$sql1 = 'SELECT * FROM livetable1 WHERE status = "Live" AND deadtime <> 0';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
$result = $conn->query($sql1);
$deadtime1 = null;
$userid1 = null;
if ($result->num_rows > 0) {
    // Output data of each row.
    while($data = $result->fetch_assoc()) {
        $roomid1         = $data["roomid"];

        $arr            = explode("_", $roomid1);
        $attemptid      = array_splice($arr, -1)[0];
        $quizid1        = array_splice($arr, -1)[0];
        $username       = implode("_", $arr);

        $user           = $DB->get_record('user', array('username'=>$username));
        $userid1         = $user->id;

        if($quizid1 == $quizid) {
            $status1          = $data["status"];
            $timetoconsider1  = $data["timetoconsider"];
            $livetime1        = $data["livetime"];
            $deadtime1        = $data["deadtime"];
            echo $roomid1 . ' ' . $livetime1;
        }
    }
}

// Setup the form.
// $user = $DB->get_record('user', array('username'=>$username));
// $userid = 5;
$timelimit = $quiz->timelimit + $deadtime1;
$a = null;

$overrideediturl = new moodle_url('/mod/quiz/overrideedit.php');
$mform = new timelimit_override_form($overrideediturl, $cm, $quiz, $context, $userid1, $timelimit);

if(empty($table->data)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'quizaccess_heartbeatmonitor'), 'info');

} else {
    // Page setup.
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title($pluginname);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));

    echo html_writer::table($table);

    $mform->display();

    echo html_writer::div('', 'checkedusers');
}

function secondsToTime($seconds) {
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');
}
?>

<script>
function humanise(difference) {
    var days    = Math.floor(difference / (1000 * 60 * 60 * 24));
    var hours   = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((difference % (1000 * 60)) / 1000);
    var time    = days + ' d, ' + hours + ' h : ' + minutes + ' m : ' + seconds + ' s' ;
    return time;
}

function run_script() {
    $(document).ready(function() {
        var interval = setInterval(function() {
//             alert('hi');
            // Database should also be queried every sec.====================================

            // Update livetime and deadtime every sec.====================================
            $('tbody tr').each(function() {
        	    var status = $(this).find('#status').html();
        	    var timetoconsider = $(this).find('#timetoconsider').html();
        	    var livetime = $('#livetime').attr('value');
        	    var deadtime = $('#deadtime').attr('value');
        	    var timenow = new Date().getTime();
    //     	    alert(timenow + '  ' + timetoconsider + '  ' + livetime);
    //             alert(humanise((timenow - timetoconsider) + livetime));
    //             alert(humanise(livetime1));
    //             alert(humanise(timenow) + '  ' + humanise(timetoconsider) + '  ' + humanise(livetime));
                if (status == 'Live') {
    //                 livetime = parseInt(timenow - timetoconsider);
                    livetime = livetime + 1000;
                } else {
    //                 deadtime = parseInt(timenow - timetoconsider);
                    deadtime = deadtime + 1000;
                }
                var humanisedlivetime = humanise(livetime);
                var humaniseddeadtime = humanise(deadtime);

                $(this).find('#livetime').html(humanisedlivetime);
                $(this).find('#deadtime').html(humaniseddeadtime);

                // Remove checkbox, if status = 'Dead'.====================================
        	    if (status == 'Dead') {
        	    	$(this).find('#select').html('');
                }
            });

    	   // Get selected users.====================================
//     	    var users = new Array();
//     	    $('.setoverride:checked').each(function() {
//     	        users.push($(this).val());
//     	    });
// //             if(users.length > 0) {
//         	    $('.checkedusers').html('Selected Users<br>');
//         	    $('.checkedusers').append(users.join(" "));
// //             }
		}, 1000);
    });

}

function getusers() {
	$(document).ready(function() {
        var interval = setInterval(function() {
//             alert('hi');
            var users = [];
            var user;
            $('.setoverride:checked').each(function() {
            	 var $row = $(this).closest("tr");    // Find the row
            	 var roomid = $row.find("#roomid").text();
//             	 var user = {roomid:roomid};
                 var status = $row.find("#status").text(); // Find the name
                 var timetoconsider = $row.find("#timetoconsider").text(); // Find the surname
                 var livetime = $row.find("#livetime").text();
                 var deadtime = $row.find("#deadtime").text();

//                 users.push($(this).val());
//                  users.push(user);
                 users.push(roomid + '/' + status + '/' + deadtime);
            });
            $('.checkedusers').html('Selected users: <br>');
            $('.checkedusers').append(users.join(" "));
//             alert(users[0]);

//             return users[0];
//             $.post(window.location, {name: 'John'}, success: function(data) {
//                   alert('POST was successful. Server says: ' + data);
//                 });
        }, 1000);
	});
}

function divscript() {
	return $('.checkedusers').text();
}

</script>

<?php

// if(isset($livetime, $deadtime)) {
//     $result = null;
//     echo "<script type='text/javascript'>run_script();</script>";
//     echo "<script type='text/javascript'>getusers();</script>";

    echo 'why';
    function a() {
        echo "<script type='text/javascript'>divscript();</script>";
    }
    echo 'here';
    a();
    echo 'now';
// }
$conn->close();

// Finish the page.
echo $OUTPUT->footer();
?>
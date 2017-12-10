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
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/setoverride_form.php');


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
$heading    = get_string('heading', 'quizaccess_heartbeatmonitor', $quiz->name);

$overrideediturl = new moodle_url('/mod/quiz/overrideedit.php');//, array(
//         'quizid' => $quizid,
//         'courseid' => $courseid,
//         'cmid' => $cmid
// ));

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
echo $OUTPUT->heading($heading) . '<br>';

// Display live users.
// Fetch records from database.
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

$sql = 'SELECT * FROM livetable1';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
$result = $conn->query($sql);

$table = new html_table();
$table->caption = get_string('liveusers', 'quizaccess_heartbeatmonitor');
$table->head = array('', 'Roomid', 'Status', 'Time to consider', 'Live time', 'Dead time');

if ($result->num_rows > 0) {
    // Output data of each row.
    while($data = $result->fetch_assoc()) {
        $roomid          = $data["roomid"];
        $arr             = explode("_", $roomid);
        $attemptid       = $arr[count($arr) - 1];
        $quizid1         = $arr[count($arr) - 2];

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
            $row        = new html_table_row();
            $row->id    = $roomid;
            $row->attributes['class'] = $roomid;

            $cell0 = new html_table_cell(
                        html_writer::empty_tag('input', array('type'  => 'checkbox',
                                                              'name'  => $roomid,
                                                              'class' => 'setoverride')));
            $cell0->id = 'select';

            $cell1 = new html_table_cell($roomid);
            $cell1->id = 'roomid';

            $cell2 = new html_table_cell($status);
            $cell2->id = 'status';

            $cell3 = new html_table_cell($timetoconsider);
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

if(!empty($table->data)) {
    echo html_writer::table($table);
    $options = null;
    echo $OUTPUT->single_button($overrideediturl->out(true,
            array('action' => 'adduser', 'cmid' => $cm->id)),
            get_string('addnewuseroverride', 'quiz'), 'get', $options);

    $mform = new setoverride_form(null);
    $formdata = array ('quizid' => $quizid, 'courseid' => $courseid, 'cmid' => $cmid);
    $mform->set_data($formdata);
    $mform->display();

    // Implement overrides.
    if ($fromformdata = $mform->get_data()) {
        if (!empty($fromformdata->save)) {
            $overrideediturl->param('action', 'adduser');
            $overrideediturl->param('cmid', $cmid);
            redirect($overrideediturl);
        }
    }
} else {
    echo $OUTPUT->notification(get_string('nodatafound', 'quizaccess_heartbeatmonitor'), 'info');
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

function countDownTimer(livetime, deadtime) {
    $(document).ready(function() {
        var interval = setInterval(function() {
            // Database should also be queried every sec.
            // Update livetime and deadtime every sec.
    	    var status = $('#status').html();
    	    var timetoconsider = $('#timetoconsider').html();
//     	    var livetime = $('#livetime').html();
//     	    var deadtime = $('#deadtime').html();
    	    var currentTimestamp = new Date().getTime();
//     	    alert(currentTimestamp + '  ' + timetoconsider + '  ' + livetime);
//             alert(humanise((currentTimestamp - timetoconsider) + livetime));
//             alert(humanise(livetime1));
//             alert(humanise(currentTimestamp) + '  ' + humanise(timetoconsider) + '  ' + humanise(livetime));
            if (status == 'Live') {
//                 livetime = parseInt(currentTimestamp - timetoconsider);
                livetime = livetime + 1000;
            } else {
//                 deadtime = parseInt(currentTimestamp - timetoconsider);
                deadtime = deadtime + 1000;
            }
            var humanisedlivetime = humanise(livetime);
            var humaniseddeadtime = humanise(deadtime);
            $('#livetime').html(humanisedlivetime);
    	    $('#deadtime').html(humaniseddeadtime);
		}, 1000);
    });
}
</script>

<?php
if(isset($livetime, $deadtime)) {
    echo "<script type='text/javascript'>countDownTimer($livetime, $deadtime);</script>";
}
$conn->close();

// Finish the page.
echo $OUTPUT->footer();
?>
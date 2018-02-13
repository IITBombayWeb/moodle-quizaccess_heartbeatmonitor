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
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
// require_once($CFG->dirroot.'/mod/quiz/lib.php');
// require_once($CFG->dirroot.'/mod/quiz/locallib.php');
// require_once($CFG->dirroot.'/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimit_override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimit_override_form1.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/intermediate_form.php');
require_once($CFG->dirroot . '/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/new_form.php');



$quizid     = required_param('quizid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$cmid       = required_param('cmid', PARAM_INT);
// $mode = optional_param('mode', '', PARAM_ALPHA); // One of 'user' or 'group', default is 'group'.

// echo '<br><br><br>';
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

// $servername = "localhost";
// $dbusername = "root";
// $dbpassword = "root123";
// $dbname     = "trialdb";

// $servername = $CFG->dbhost;
// $dbusername = $CFG->dbuser;
// $dbpassword = $CFG->dbpass;
// $dbname     = $CFG->dbname;

// // Create connection
// $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
// // echo "Connected successfully";

$sql = 'SELECT * FROM {quizaccess_hbmon_livetable1}';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
// $result = $conn->query($sql);
$arr = array();
$roomid = null;

$table = new html_table();
$table->id = 'liveusers';
// $table->caption = get_string('liveusers', 'quizaccess_heartbeatmonitor');
$table->caption = 'Users attempting quiz';
$table->head = array('User', 'Socket room id', 'Current status', 'Status update on', 'Quiz time used up', 'Quiz time lost');

$result    = $DB->get_records_sql($sql);
// if ($result->num_rows > 0) {
// echo '<br><br><br>';

if (!empty($result)){
    // Output data of each row.
//     while($data = $result->fetch_assoc()) {
    foreach ($result as $record) {
//         echo '<br><br><br>';

//         $roomid         = $data["roomid"];
        $roomid         = $record->roomid;
        $arr            = explode("_", $roomid);
        $attemptid      = array_splice($arr, -1)[0];
        $qa             = $DB->get_record('quiz_attempts', array('id'=>$attemptid));

        // Error..since socket gets connected while reviewing the quiz.. but qa->state is finished..so conflict
        if($qa->state == 'finished') {
            $sql = 'DELETE FROM {quizaccess_hbmon_livetable1} WHERE roomid = "' . $roomid . '"';

            $table11 = 'quizaccess_hbmon_livetable1';
            $select = 'roomid = ?'; //is put into the where clause
            $params = array("' . $roomid . '");

            $DB->delete_records_select($table11, $select, $params);
//             $conn->query($sql);
            continue;
        }

        $quizid1        = array_splice($arr, -1)[0];
        $username       = implode("_", $arr);
        //         echo 'un ' . $username;
        $user           = $DB->get_record('user', array('username'=>$username));
        //         print_object($user);
        if($user) {
            $userid         = $user->id;
        }

        if($quizid1 == $quizid) {
//             $status          = $data["status"];
//             $timetoconsider  = $data["timetoconsider"];
//             $livetime        = $data["livetime"];
//             $deadtime        = $data["deadtime"];
            $status          = $record->status;
            $timetoconsider  = $record->timetoconsider;
            $livetime        = $record->livetime;
            $deadtime        = $record->deadtime;

            $currentTimestamp = intval(microtime(true)*1000);

            if ($status == 'Live') {
                $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
                $statustodisplay = '<font color="green"><i>Online</i></font>';
            } else {
                $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
                $statustodisplay = '<font color="red"><i>Offline</i></font>';
            }

            $humanisedlivetime = secondsToTime(intval($livetime / 1000));
            $humaniseddeadtime = secondsToTime(intval($deadtime / 1000));

            $table->rowclasses['roomid'] = $roomid;
            $row = new html_table_row();
            $row->id = $roomid;
            $row->attributes['class'] = $roomid;

            $value = $roomid . '_' . $deadtime;
            //             $cell0 = new html_table_cell();
//             $cell0 = new html_table_cell(
//                     html_writer::empty_tag('input', array('type'  => 'checkbox',
//                                                         'name'  => 'setoverride',
//                                                         'value' => $value,
//                                                         'class' => 'setoverride')));
            $cell0 = new html_table_cell($user->firstname .  ' ' . $user->lastname);
            $cell0->id = 'user';

            $cell1 = new html_table_cell($roomid);
            $cell1->id = 'roomid';

            $cell2 = new html_table_cell($statustodisplay);
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

// $sql1 = 'SELECT * FROM livetable1 WHERE status = "Live" AND deadtime <> 0';
// $result1 = $conn->query($sql1);
// $deadtime1 = null;
// $userid1 = null;
// if ($result1->num_rows > 0) {
//     // Output data of each row.
//     while($data = $result1->fetch_assoc()) {
//         $roomid1        = $data["roomid"];

//         $arr            = explode("_", $roomid1);
//         $attemptid      = array_splice($arr, -1)[0];
//         $quizid1        = array_splice($arr, -1)[0];
//         $username       = implode("_", $arr);

//         $user           = $DB->get_record('user', array('username'=>$username));
//         $userid1        = $user->id;

//         if($quizid1 == $quizid) {
//             $status1          = $data["status"];
//             $timetoconsider1  = $data["timetoconsider"];
//             $livetime1        = $data["livetime"];
//             $deadtime1        = $data["deadtime"];
//             //             echo $roomid1 . ' ' . $livetime1;
//         }
//         break;
//     }
// }

// Setup the form.
// $timelimit = $quiz->timelimit + intval($deadtime1 / 1000);
//     $overrideediturl = new moodle_url('/mod/quiz/overrideedit.php');
$processoverrideurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
$indexurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php');
$intermediaryurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/intermediary.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));


// $mform = new timelimit_override_form($overrideediturl, $cm, $quiz, $context, $userid1, $timelimit);
// come back to this page..fetch checked records and then redirect to processoverride as in ovrrdedit.php.

// if(isset($_POST["result"])) {
// $mform = new intermediate_form(null);
// $formdata = array ('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid);
// $mform->set_data($formdata);

// $mform = new timelimit_override_form($processoverrideurl, $cm, $quiz, $context, $userid1, $timelimit);
// }

// Page setup.
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pluginname);
$PAGE->set_heading($course->fullname);
// $PAGE->requires->jquery();
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));

$mform = new new_form($url, $cm, $quiz, $context);
if($fromform = $mform->get_data()) {

//         print_object($fromform->users);
    if($fromform->users) {
//         $users = $fromform->users;
        $users = '';
        $i = 1;
        echo '<br>You have selected : <br><br>';

        foreach ($fromform->users as $user) {
            $arr            = explode("_", $user);
            $attemptid      = array_splice($arr, -1)[0];
            $quizid1        = array_splice($arr, -1)[0];
            $username       = implode("_", $arr);

            $userdata       = $DB->get_record('user', array('username'=>$username));
            $userid1        = $userdata->id;

            echo $i . ' | ' . $userdata->firstname .  ' ' . $userdata->lastname . '<br>';
            $users .= $user . ' ';
            $i++;

        }
        $mform1 = new timelimit_override_form1($processoverrideurl, $cm, $quiz, $context, $users, 0);

        $mform1->display();
    }

//     $options = array();
//     echo $OUTPUT->single_button($url->out(true,
//             array('quizid' => $quiz->id, 'courseid' => $course->id, 'cmid' => $cm->id)),
//             'Back to index page', 'get', $options);
//     echo isset($_POST["result"]);
//     $obj = json_decode($_POST["result"], false);
//     print_object($obj->result);
//     echo 'hi';
//     print_object($fromform);
//     if(isset($_POST["result"])) {
//         echo '';
//     }
}  else if(empty($table->data)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'quizaccess_heartbeatmonitor'), 'info');

} else {
    // Display table.
    echo html_writer::table($table);
    echo '<br>';
//     echo '(Note: List contains users having non-zero "Quiz time lost" value only.)';
    $mform->display();
//     echo html_writer::div('', 'checkedusers');


//     echo "<script type='text/javascript'>run_script();</script>";
//     if(isset($_POST["result"])) {
//         $options['users'] = $_POST["result"];
//         $options = null;
//         echo $OUTPUT->single_button($intermediaryurl->out(true,
//                 array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid)),
//                 'New button', 'post', $options);
//     }
}

function secondsToTime($seconds) {
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');
}

// function getdiv(){
//     $dom = new DOMDocument('1.0');
//     @$dom->loadHTMLFile($url);
// //     $html = file_get_html($url);
//     $div = $dom->getElementById ("checkedusers");
// //     $displaybody = $html->find('div[id=checkedusers]', 0)->plaintext;
//     return $div;
// }

// $conn->close();
// Finish the page.
echo $OUTPUT->footer();

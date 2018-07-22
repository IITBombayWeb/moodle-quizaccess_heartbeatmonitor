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
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimit_override_form1.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/intermediate_form.php');
require_once($CFG->dirroot . '/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/new_form.php');


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

$url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));
$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

// Check the user has the required capabilities to access this plugin.
require_capability('mod/quiz:manage', $context);

// Display live users.
// Fetch records from database.

$sql = 'SELECT * FROM {quizaccess_hbmon_livetable1}';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
$arr = array();
$roomid = null;

$table = new html_table();
$table->id = 'liveusers';
// $table->caption = get_string('liveusers', 'quizaccess_heartbeatmonitor');
$table->caption = 'Users attempting quiz';
$table->head = array('User', 'Socket room id', 'Current status', 'Status update on', 'Quiz time used up', 'Quiz time lost');
// $table->head = array('User', 'Socket room id', 'Current status', 'Status update on', 'Quiz time used up', 'Quiz time lost', 'Total extra time granted');

$result    = $DB->get_records_sql($sql);

if (!empty($result)){
    // Output data of each row.
    foreach ($result as $record) {
        $roomid         = $record->roomid;
        $arr            = explode("_", $roomid);
        $attemptid      = array_splice($arr, -1)[0];
        $qa             = $DB->get_record('quiz_attempts', array('id'=>$attemptid));

        if(!$qa || $qa->state == 'finished') {
            $sql = 'DELETE FROM {quizaccess_hbmon_livetable1} WHERE roomid = "' . $roomid . '"';

            $table11 = 'quizaccess_hbmon_livetable1';
            $select = 'roomid = ?'; // Is put into the where clause.
            $params = array($roomid);

            $delete = $DB->delete_records_select($table11, $select, $params);
            continue;
        }

        $quizid1        = array_splice($arr, -1)[0];
        $username       = implode("_", $arr);
        $user           = $DB->get_record('user', array('username'=>$username));

        if($user) {
            $userid = $user->id;
        }

        if($quizid1 == $quizid) {
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

// Setup the form.
$processoverrideurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
$indexurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php');

// $mform = new timelimit_override_form($overrideediturl, $cm, $quiz, $context, $userid1, $timelimit);
// come back to this page..fetch checked records and then redirect to processoverride as in ovrrdedit.php.

// Page setup.
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pluginname);
$PAGE->set_heading($course->fullname);
// $PAGE->requires->jquery();
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));

$mform = new new_form($url, $cm, $quiz, $context);
if($fromform = $mform->get_data()) {
    if($fromform->users) {
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
}  else if(empty($table->data)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'quizaccess_heartbeatmonitor'), 'info');

} else {
    // Display table.
    echo html_writer::table($table);
    echo '<br>';
    $mform->display();
}

function secondsToTime($seconds) {
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');
}

// Finish the page.
echo $OUTPUT->footer();

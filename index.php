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
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/hbmonconfig.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimit_override_form1.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/intermediate_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/startnode_form.php');
require_once($CFG->dirroot . '/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/new_form.php');


$quizid     = required_param('quizid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$cmid       = required_param('cmid', PARAM_INT);

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
$table->id = get_string('liveusers', 'quizaccess_heartbeatmonitor');
$table->caption = get_string('usersattemptingquiz', 'quizaccess_heartbeatmonitor');
$table->head = array(get_string('user', 'quizaccess_heartbeatmonitor'),
                        get_string('socketroomid', 'quizaccess_heartbeatmonitor'),
                        get_string('currentstatus', 'quizaccess_heartbeatmonitor'),
                        get_string('statusupdate', 'quizaccess_heartbeatmonitor'),
                        get_string('timeutilized', 'quizaccess_heartbeatmonitor'),
                        get_string('timelost', 'quizaccess_heartbeatmonitor'));
// $table->head = array('User', 'Socket room id', 'Current status', 'Status update on', 'Quiz time used up', 'Quiz time lost', 'Total extra time granted');

$result    = $DB->get_records_sql($sql);

if (!empty($result)){
    // Output data of each row.
    foreach ($result as $record) {
        $roomid    = $record->roomid;
        $roomdata  = explode("_", $roomid);
        $attemptid = array_splice($roomdata, -1)[0];
        $quiza     = $DB->get_record('quiz_attempts', array('id'=>$attemptid));

        if(!$quiza || $quiza->state == 'finished') {
//             $sql = 'DELETE FROM {quizaccess_hbmon_livetable1} WHERE roomid = "' . $roomid . '"';
            $hblivetable = 'quizaccess_hbmon_livetable1';
            $select = 'roomid = ?'; // Is put into the where clause.
            $params = array($roomid);
            $delete = $DB->delete_records_select($hblivetable, $select, $params);
            continue;
        }

        $roomquizid = array_splice($roomdata, -1)[0];
        $username   = implode("_", $roomdata);
        $user       = $DB->get_record('user', array('username'=>$username));

        if($user) {
            $userid = $user->id;
        }

        if($roomquizid == $quizid) {
            $status          = $record->status;
            $timetoconsider  = $record->timetoconsider;
            $livetime        = $record->livetime;
            $deadtime        = $record->deadtime;

            $currenttimestamp = intval(microtime(true));

            if ($status == 'Live') {
                $livetime = ($currenttimestamp - $timetoconsider) + $livetime;
                $statustodisplay = get_string('online', 'quizaccess_heartbeatmonitor');
            } else {
                $deadtime = ($currenttimestamp - $timetoconsider) + $deadtime;
                $statustodisplay = get_string('offline', 'quizaccess_heartbeatmonitor');
            }

//             $humanisedlivetime = secondsToTime(intval($livetime));
//             $humaniseddeadtime = secondsToTime(intval($deadtime));

            $humanisedlivetime = format_time($livetime);
            $humaniseddeadtime = format_time($deadtime);

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

            $cell3 = new html_table_cell(userdate(intval($timetoconsider)));
//             $cell3 = new html_table_cell(format_time($timetoconsider));
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

// Page setup.
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pluginname);
$PAGE->set_heading($course->fullname);
// $PAGE->requires->jquery();
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));

$startnode_form = new startnode_form($url, $quiz, $course, $cm);
// $startnode_form->set_data($node_up);
static $node_up = 0;

// Manage node server.
if($nodestatus = $startnode_form->get_data()) {
    $outputfile = $CFG->dirroot . "/mod/quiz/accessrule/heartbeatmonitor/exec_output.text";
    $pidfile = $CFG->dirroot . "/mod/quiz/accessrule/heartbeatmonitor/exec_pid.text";

    if($nodestatus->submitbutton == 'Start') {
        // Start node.
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);

        if(!$phpws_result) {
            $cmd = "node " . $CFG->dirroot . "/mod/quiz/accessrule/heartbeatmonitor/server.js";
            file_put_contents($outputfile, '');
            file_put_contents($pidfile, '');
            exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));

            while(trim(file_get_contents($outputfile)) == false) {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);
                if(!$phpws_result) {
                    sleep(1);
                } else {
                    $node_up = 1;
                    break;
                }
            }

            if(!$node_up) {
                $node_err = nl2br(file_get_contents($outputfile));
                echo $OUTPUT->notification('Error:<br>' . $node_err);
            }
        }
    } else {
        // Stop node.
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);
        if($phpws_result) {
            $cmd = "kill " . file_get_contents($pidfile);
            exec($cmd);
        }
    }
}

$mform = new new_form($url, $cm, $quiz, $context);
if($fromform = $mform->get_data()) {
    if($fromform->users) {
        $users = '';
        $i = 1;
        echo get_string('youhaveselected', 'quizaccess_heartbeatmonitor');

        foreach ($fromform->users as $user) {
            $arr        = explode("_", $user);
            $attemptid  = array_splice($arr, -1)[0];
            $roomquizid = array_splice($arr, -1)[0];
            $username   = implode("_", $arr);

            $userdata   = $DB->get_record('user', array('username'=>$username));
            $roomuserid = $userdata->id;

            echo $i . ' | ' . $userdata->firstname .  ' ' . $userdata->lastname . get_string('br', 'quizaccess_heartbeatmonitor');
            $users .= $user . ' ';
            $i++;
        }
        $mform1 = new timelimit_override_form1($processoverrideurl, $cm, $quiz, $context, $users, 0);
        $mform1->display();
    }
} else {
    $startnode_form->display();
    if(empty($table->data)) {
        echo get_string('h4open', 'quizaccess_heartbeatmonitor');
        echo html_writer::nonempty_tag('liveuserstblcaption', get_string('liveusers', 'quizaccess_heartbeatmonitor'));
        echo get_string('h4close', 'quizaccess_heartbeatmonitor');
        echo $OUTPUT->notification(get_string('nodatafound', 'quizaccess_heartbeatmonitor'), 'info');
    } else {
        echo get_string('h4open', 'quizaccess_heartbeatmonitor');
        echo html_writer::nonempty_tag('liveuserstblcaption', get_string('liveusers', 'quizaccess_heartbeatmonitor'));
        echo get_string('h4close', 'quizaccess_heartbeatmonitor');

        // Display table.
        echo html_writer::table($table);
        echo get_string('br', 'quizaccess_heartbeatmonitor');
        $mform->display();
    }
}

function secondsToTime($seconds) {
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');
}

// Finish the page.
echo $OUTPUT->footer();

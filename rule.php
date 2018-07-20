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
 * Implementaton of the quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/createoverride.php');

/**
 * A rule implementing heartbeat monitor.
 *
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_heartbeatmonitor extends quiz_access_rule_base {

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        // This rule is always used, even if the quiz has no open or close date.
        return new self($quizobj, $timenow);
    }

    public function prevent_access() {
        global $CFG, $PAGE, $_SESSION, $DB;

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url('http://127.0.0.1:3000/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );

        // Use this to delete user-override when the attempt finishes.
//         $this->current_attempt_finished();

        $sessionkey = sesskey();
        $userid     = $_SESSION['USER']->id;
        $username   = $_SESSION['USER']->username;

        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
        $context    = $this->quizobj->get_context();

//         print_object($this->quizobj);   // For quiz timeopen, timeclose etc.

        $quiz = $this->quizobj->get_quiz();

//     	$url = 'http://127.0.0.1:3000/';
//     	$ch = curl_init($url);
//     	curl_setopt($ch, CURLOPT_NOBODY, true);
//     	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//     	curl_exec($ch);
//     	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     	curl_close($ch);
//     	if (200 == $retcode) {

        $sql_fetch_attemptid = 'SELECT *
                                    FROM {quiz_attempts}
                                    WHERE userid = ' . $userid . '
                                    AND quiz = ' . $quizid . '
                                    ORDER BY id DESC
                                    LIMIT 1 ';
        $records_fetch_attemptid = $DB->get_record_sql($sql_fetch_attemptid);

        if ($records_fetch_attemptid){
            $attemptid = $records_fetch_attemptid->id;

            // code here
            $qa = $DB->get_record('quiz_attempts', array('id'=>$attemptid));
            $state = $qa->state;

            $roomid = $username . '_' . $quizid . '_' . $attemptid;
            STATIC $flag = 0;

            // Error..since socket gets connected while reviewing the quiz.. but qa->state is finished..so conflict
            if($qa->state != 'finished') {
                $PAGE->requires->js_init_call('client', array($quizid, $userid, $username, $attemptid, $sessionkey, json_encode($CFG)));
            }

            //======================================================================================================
            if($qa->state != 'finished') {
                // If deadtime is there, then create override.
                $select_sql = 'SELECT *
                                    FROM {quizaccess_hbmon_livetable1}
                                    WHERE roomid = "' . $roomid . '"' .
                                    /*  AND status = "Live" */
                                    'AND deadtime > 60000';
                $records = $DB->get_records_sql($select_sql);

                if (!empty($records)){
                    foreach ($records as $record) {
                        if($roomid == $record->roomid){
//                             $this->create_override($roomid, $cmid, $quiz);
                        }
                        break;
                    }
                }
            } else {
                // Reset override to quiz timelimit. Initially, we were deleting the override.
                /*
                 $sql = 'SELECT *
                            FROM {quiz_overrides}
                            WHERE quiz = :quizid
                            AND userid = :userid
                            ORDER BY id DESC
                            LIMIT 1';
                 $params['quizid'] = $quiz->id;
                 $params['userid'] = $userid;
                 $override = $DB->get_record_sql($sql, $params);
                 if ($override) {
                     quiz_delete_override($quiz, $override->id);

                 }
                */
                $roomid = $username . '_' . $quizid . '_' . $attemptid;
                $select_sql = 'SELECT *
                                    FROM {quizaccess_hbmon_livetable1}
                                    WHERE roomid = "' . $roomid . '"';
                                //    AND status = "Dead"';
                                //    AND deadtime > 60000';
                $records = $DB->get_records_sql($select_sql);

                if (!empty($records)){
                    foreach ($records as $record) {
                        if($roomid == $record->roomid){
//                             $this->create_override($roomid, $cmid, $quiz, $state);
                        }
                    }
                }
            }
        }
        //======================================================================================================
//         return false;
    }

    protected function create_override($roomid, $cmid, $quiz, $state = null) {
        global $DB, $CFG;

        $context = context_module::instance($cmid);
        $groupmode = null;
        $action = null;
        $override = null;

        // Add or edit an override.
//         require_capability('mod/quiz:manageoverrides', $context);

        // Creating a new override.
        $data = new stdClass();

        // Merge quiz defaults with data.
        $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
        foreach ($keys as $key) {
            if (!isset($data->{$key}) || $reset) {
                $data->{$key} = $quiz->{$key};
            }
        }

        // True if group-based override.
//         $action = null;
//         $groupmode = !empty($data->groupid) || ($action === 'addgroup' && empty($overrideid));

        // Setup the form data required for processing as in overrideedit.php file.
        $fromform = new stdClass();
        $fromform->action = 'adduser';
        $fromform->cmid = $cmid;
        $fromform->quiz = $quiz->id;
        $fromform->_qf__quiz_override_form = 1;
        $fromform->mform_isexpanded_id_override = 1;
        $fromform->userid = '';
        $fromform->password = '';
//         $fromform['timeopen'] = 1505125800;
        $fromform->timeopen = $quiz->timeopen;
        $fromform->timeclose = $quiz->timeclose;
        $fromform->timelimit = 0;
        $fromform->attempts = $quiz->attempts;
        $fromform->submitbutton = 'Save';

        if($state === 'finished') {
            $myobj = new createoverride();
            $myobj->reset_timelimit_override($cmid, $roomid, $fromform, $quiz);
        } else {
            $myobj = new createoverride();
            $myobj->my_override($cmid, $roomid, $fromform, $quiz);
        }
    }
}


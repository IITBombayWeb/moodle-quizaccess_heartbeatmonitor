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

    public function setup_attempt_page($page) {
        global $CFG, $PAGE, $_SESSION, $DB;

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url('http://127.0.0.1:3000/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );

        $attemptid  = required_param('attempt', PARAM_INT);

        // use this to delete user-override when the attempt finishes
//        $this->current_attempt_finished();

        if($attemptid) {

//         $attemptid  = required_param('attempt', PARAM_INT);
        $sessionkey = sesskey();
        $userid     = $_SESSION['USER']->id;
        $username   = $_SESSION['USER']->username;
        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
//         echo '<br><br><br> quiz cmid '.$cmid;
        $context    = $this->quizobj->get_context();

//         print_object($this->quizobj);   // for quiz timeopen, timeclose etc.
        $quiz = $this->quizobj->get_quiz();
//     	$url = 'http://127.0.0.1:3000/';
//     	$ch = curl_init($url);
//     	curl_setopt($ch, CURLOPT_NOBODY, true);
//     	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//     	curl_exec($ch);
//     	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     	curl_close($ch);
//     	if (200 == $retcode) {

        // code here
            $qa = $DB->get_record('quiz_attempts', array('id'=>$attemptid));
            echo '<br><br><br> qa state: ' . $qa->state;

            $roomid = $username . '_' . $quizid . '_' . $attemptid;
            $state = $qa->state;

            STATIC $flag = 0;
            // Error..since socket gets connected while reviewing the quiz.. but qa->state is finished..so conflict
            if($qa->state != 'finished') {
                echo '<br>in if not finished qa state 1';
                echo '<br>ts before : ' . (int)(microtime(true)*1000);
        	    $PAGE->requires->js_init_call('client', array($quizid, $userid, $username, $attemptid, $sessionkey, json_encode($CFG)));
//         	    sleep(20);
        	    echo '<br>ts : ' . (int)(microtime(true)*1000); //

            }


            if($qa->state != 'finished') {
                echo '<br>in if not finished qa state 2';
                $select_sql11 = 'SELECT *
                        FROM {quizaccess_hbmon_livetable1}
                        WHERE roomid = "' . $roomid . '"';
                $records11 = $DB->get_records_sql($select_sql11);

                print_object($records11);

        	    $select_sql = 'SELECT *
                        FROM {quizaccess_hbmon_livetable1}
                        WHERE roomid = "' . $roomid . '"
                                AND status = "Live"
                                AND deadtime > 60000';
        	    $records = $DB->get_records_sql($select_sql);

        	    if (!empty($records)){
        	        foreach ($records as $record) {
        	            if($roomid == $record->roomid){
    //     	                if($qa->state != 'finished') {
            	                echo '<br>in record if rule.php';
            	                $this->create_override($roomid, $cmid, $quiz);
    //         	                $flag = 1;
//         	                } else {
    //     	                    echo '<br>in record else rule.php';
    //     	                    $this->create_override($roomid, $cmid, $quiz, $state);
    //     	                }
        	            }
        	            break;
        	        }
        	    }

//     	    if ($flag == 1){
//     	        echo 'in recordif rule.php';
//     	        $this->create_override($roomid, $cmid, $quiz, $state);
//     	        $flag = 0;
//     	    }
            } else {
                echo '<br> in else';
            /*
//                 $sql = 'SELECT o.*
//                             FROM {quiz_overrides} o
//                             JOIN {user} u ON o.userid = u.id
//                             WHERE o.quiz = :quizid
//                                 AND u.id = :userid
//                             ORDER BY o.id DESC
//                             LIMIT 1';
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
                    print_object($override);
    //                 echo '<br><br><br> qa state: ' . $qa->state;
                    quiz_delete_override($quiz, $override->id);

                }
//                 */
                echo '<br><br><br> qa state: ' . $qa->state;
                $roomid = $username . '_' . $quizid . '_' . $attemptid;
                echo '<br>rum : ' . $roomid;
                $select_sql = 'SELECT *
                        FROM {quizaccess_hbmon_livetable1}
                        WHERE roomid = "' . $roomid . '"';
//                                 AND status = "Dead"';
//                                 AND deadtime > 60000';
                $records = $DB->get_records_sql($select_sql);
                print_object($records);

                if (!empty($records)){
                    foreach ($records as $record) {
                        if($roomid == $record->roomid){
                            echo '<br>in record else rule.php';
                            $this->create_override($roomid, $cmid, $quiz, $state);
                        }
                    }
                }
            }
//     	}
        }
    }

    protected function create_override($roomid, $cmid, $quiz, $state = null) {
        global $DB, $CFG;
        $context = context_module::instance($cmid);
//         echo '<br><br><br> cr-ovrr cmid '.$cmid;
//         $varcmid = $cmid;
        $groupmode = null;
        $action = null;
//         include_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
//         echo '<br><br><br> cr-ovrr after cmid '.$cmid;
        $override = null;
//         $cmid = $varcmid;

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
//         echo '<br><br><br>rule cmid ' . $cmid;
//         print_object($fromform);
        if($state === 'finished') {
            echo 'in if state finished';
            $myobj = new createoverride();
            $myobj->reset_timelimit_override($cmid, $roomid, $fromform, $quiz);
        } else {
            $myobj = new createoverride();
            $myobj->my_override($cmid, $roomid, $fromform, $quiz);
//             my_override($cmid, $roomid, $fromform, $quiz);
        }
    }

//     public function my_override($roomid, $fromfrom) {
//         if($roomid) {
// //             $arr            = explode("_", $roomid);
// //             $attemptid      = array_splice($arr, -1)[0];
// //             $quizid1        = array_splice($arr, -1)[0];
// //             $username       = implode("_", $arr);
// //             echo 'un ' . $username;
// //             $userdata       = $DB->get_record('user', array('username'=>$username));
// //             if($user) {
// //                 $userid         = $userdata->id;
// //             }
//             $sql = 'SELECT * FROM {quizaccess_hbmon_livetable1} WHERE roomid = "' . $roomid . '" AND status = "Live" AND deadtime > 60000';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
//             $result    = $DB->get_records_sql($sql);

//             if (!empty($result)){
//                 foreach ($result as $record) {
//                     $roomid         = $record->roomid;
//                     $arr            = explode("_", $roomid);
//                     $attemptid      = array_splice($arr, -1)[0];
//                     $quizid1        = array_splice($arr, -1)[0];
//                     $username       = implode("_", $arr);
//                     $user           = $DB->get_record('user', array('username'=>$username));
//                     if($user) {
//                         $userid         = $user->id;
//                     }
//                     if($quizid1 == $quiz->id) {
//                         $status          = $record->status;
//                         $timetoconsider  = $record->timetoconsider;
//                         $livetime        = $record->livetime;
//                         $deadtime        = $record->deadtime;

//                         $currentTimestamp = intval(microtime(true)*1000);

//                         if ($status == 'Live') {
//                             $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
//                         } else {
//                             $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
//                         }

//                         $seconds = intval($livetime / 1000);
//                         $dtF = new DateTime('@0');
//                         $dtT = new DateTime("@$seconds");
//                         $lt =  $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');

//                         $seconds1 = intval($deadtime / 1000);
//                         $dtF1 = new DateTime('@0');
//                         $dtT1 = new DateTime("@$seconds1");
//                         $dt =  $dtF1->diff($dtT1)->format('%a d, %h h : %i m : %s s');

//                         $humanisedlivetime = $lt;
//                         $humaniseddeadtime = $dt;
//                         $timelimit = $quiz->timelimit + intval($deadtime / 1000);
//                         $livetime = $livetime + $deadtime;
//                         $deadtime = 0;
//                         $updatelivetablesql = 'UPDATE {quizaccess_hbmon_livetable1} SET deadtime = 0 WHERE roomid = "' . $roomid . '"';
// //                         echo '<br><br><br>' . $updatelivetablesql;
// //                         $result1 = $conn->query($updatelivetablesql);

// //                         $table11 = 'quizaccess_hbmon_livetable1';
// //                         $dataobject = array('id' => 1, 'roomid' => $roomid, 'deadtime' => 0);
// //                         $result1 = $DB->update_record($table11, $dataobject, $bulk=false);
// //                         echo '<br><br><br>update res ' . $result1;
//                         $params = array();
//                         $result1 = $DB->execute($updatelivetablesql);
// //                         print_object($result1);

//                         $fromform->userid = $userid;
//                         $fromform->timelimit = $timelimit;

//                         // Process the data.
//                         $fromform->quiz = $quiz->id;

//                         // Replace unchanged values with null.
//                         foreach ($keys as $key) {
//                             if ($fromform->{$key} == $quiz->{$key}) {
//                                 $fromform->{$key} = null;
//                             }
//                         }

//                         // See if we are replacing an existing override.
//                         $userorgroupchanged = false;
//                         if (empty($override->id)) {
//                             $userorgroupchanged = true;
//                         } else if (!empty($fromform->userid)) {
//                             $userorgroupchanged = $fromform->userid !== $override->userid;
//                         } else {
//                             $userorgroupchanged = $fromform->groupid !== $override->groupid;
//                         }

//                         if ($userorgroupchanged) {
//                             $conditions = array(
//                                             'quiz'      => $quiz->id,
//                                             'userid'    => empty($fromform->userid)? null : $fromform->userid,
//                                             'groupid'   => empty($fromform->groupid)? null : $fromform->groupid);
//                             if ($oldoverride = $DB->get_record('quiz_overrides', $conditions)) {
//                                 // There is an old override, so we merge any new settings on top of
//                                 // the older override.
//                                 foreach ($keys as $key) {
//                                     if (is_null($fromform->{$key})) {
//                                         $fromform->{$key} = $oldoverride->{$key};
//                                     }
//                                 }
//                                 // Set the course module id before calling quiz_delete_override().
//                                 $quiz->cmid = $cmid;
//                                 quiz_delete_override($quiz, $oldoverride->id);
//                             }
//                         }

//                         // Set the common parameters for one of the events we may be triggering.
//                         $params = array(
//                                     'context' => $context,
//                                     'other' => array(
//                                                     'quizid' => $quiz->id
//                                         )
//                                         );
//                         if (!empty($override->id)) {
//                             $fromform->id = $override->id;
//                             $DB->update_record('quiz_overrides', $fromform);

//                             // Determine which override updated event to fire.
//                             $params['objectid'] = $override->id;
//                             if (!$groupmode) {
//                                 $params['relateduserid'] = $fromform->userid;
//                                 $event = \mod_quiz\event\user_override_updated::create($params);
//                             } else {
//                                 $params['other']['groupid'] = $fromform->groupid;
//                                 $event = \mod_quiz\event\group_override_updated::create($params);
//                             }

//                             // Trigger the override updated event.
//                             $event->trigger();
//                         } else {
//                             unset($fromform->id);
//                             $fromform->id = $DB->insert_record('quiz_overrides', $fromform);

//                             // Determine which override created event to fire.
//                             $params['objectid'] = $fromform->id;
//                             if (!$groupmode) {
//                                 $params['relateduserid'] = $fromform->userid;
//                                 $event = \mod_quiz\event\user_override_created::create($params);
//                             } else {
//                                 $params['other']['groupid'] = $fromform->groupid;
//                                 $event = \mod_quiz\event\group_override_created::create($params);
//                             }

//                             // Trigger the override created event.
//                             $event->trigger();
//                         }

//                         quiz_update_open_attempts(array('quizid'=>$quiz->id));
//                         if ($groupmode) {
//                             // Priorities may have shifted, so we need to update all of the calendar events for group overrides.
//                             quiz_update_events($quiz);
//                         } else {
//                             // User override. We only need to update the calendar event for this user override.
//                             quiz_update_events($quiz, $fromform);
//                         }
//                     }
//                 }
//             }
//         }
//     }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
                $mform->addElement('select', 'honestycheckrequired',
                        get_string('honestycheckrequired', 'quizaccess_heartbeatmonitor'),
                        array(
                                0 => get_string('notrequired', 'quizaccess_heartbeatmonitor'),
                                1 => get_string('honestycheckrequiredoption', 'quizaccess_heartbeatmonitor'),
                        ));
                $mform->addHelpButton('honestycheckrequired',
                        'honestycheckrequired', 'quizaccess_heartbeatmonitor');

    }
    public static function save_settings($quiz) {
        global $DB;
//         if (empty($quiz->honestycheckrequired)) {
//             $DB->delete_records('quizaccess_honestycheck', array('quizid' => $quiz->id));
//         } else {
//             if (!$DB->record_exists('quizaccess_honestycheck', array('quizid' => $quiz->id))) {
//                 $record = new stdClass();
//                 $record->quizid = $quiz->id;
//                 $record->honestycheckrequired = 1;
//                 $DB->insert_record('quizaccess_honestycheck', $record);
//             }
//         }
    }
    public static function delete_settings($quiz) {
        global $DB;
//         $DB->delete_records('quizaccess_honestycheck', array('quizid' => $quiz->id));
    }
    public static function get_settings_sql($quizid) {
//         return array(
//                 'honestycheckrequired',
//                 'LEFT JOIN {quizaccess_honestycheck} honestycheck ON honestycheck.quizid = quiz.id',
//                 array());
    }
    public function is_preflight_check_required($attemptid) {
        echo '<br><br>in preflight<br>';
        return false;
    }
}


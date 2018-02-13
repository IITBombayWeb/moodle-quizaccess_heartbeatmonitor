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
 * This page handles editing and creation of quiz overrides
 *
 * @package   mod_quiz
 * @copyright 2010 Matt Petro
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// require_once(__DIR__ . '/../../config.php');
require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/quiz/lib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimit_override_form1.php');



$cmid = optional_param('cmid', 0, PARAM_INT);
$overrideid = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$reset = optional_param('reset', false, PARAM_BOOL);
echo '<br><br><br>';
$override = null;
// if ($overrideid) {

//     if (! $override = $DB->get_record('quiz_overrides', array('id' => $overrideid))) {
//         print_error('invalidoverrideid', 'quiz');
//     }
//     if (! $quiz = $DB->get_record('quiz', array('id' => $override->quiz))) {
//         print_error('invalidcoursemodule');
//     }
//     list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');

// } else
if ($cmid) {
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
    $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

} else {
    print_error('invalidcoursemodule');
}
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

$url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
if ($action) {
    $url->param('action', $action);
}
// if ($overrideid) {
//     $url->param('id', $overrideid);
// } else {
    $url->param('cmid', $cmid);
// }

$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

// Add or edit an override.
require_capability('mod/quiz:manageoverrides', $context);

// if ($overrideid) {
//     // Editing an override.
//     $data = clone $override;
// } else {
    // Creating a new override.
    $data = new stdClass();
// }

// Merge quiz defaults with data.
$keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
foreach ($keys as $key) {
    if (!isset($data->{$key}) || $reset) {
        $data->{$key} = $quiz->{$key};
    }
}

// If we are duplicating an override, then clear the user/group and override id
// since they will change.
// if ($action === 'duplicate') {
//     $override->id = null;
//     $override->userid = null;
//     $override->groupid = null;
// }

// True if group-based override.
$groupmode = !empty($data->groupid) || ($action === 'addgroup' && empty($overrideid));

$overridelisturl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/showoverrides.php', array('cmid'=>$cm->id));
// if (!$groupmode) {
    $overridelisturl->param('mode', 'user');
// }

// Setup the form.
// $mform = new quiz_override_form($url, $cm, $quiz, $context, $groupmode, $override);
$users = array();
    $mform = new timelimit_override_form1($url, $cm, $quiz, $context, $users, $override);

$mform->set_data($data);

// if ($mform->is_cancelled()) {
//     redirect($overridelisturl);

// } else if (optional_param('resetbutton', 0, PARAM_ALPHA)) {
//     $url->param('reset', true);
//     redirect($url);

// } else
$indexurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quiz->id, 'courseid'=>$course->id, 'cmid'=>$cmid));

if($mform->is_cancelled()) {
    redirect($indexurl);
}
if ($fromform = $mform->get_data()) {

    echo '<br><br>in p-ovrde';
    // Here loop over all the users for creating overrides.

    print_object($fromform);

    // Display live users.
    // Fetch records from database.
//     $servername = "localhost";
//     $dbusername = "root";
//     $dbpassword = "root123";
//     $dbname     = "trialdb";

//     $servername = $CFG->dbhost;
//     $dbusername = $CFG->dbuser;
//     $dbpassword = $CFG->dbpass;
//     $dbname     = $CFG->dbname;

//     // Create connection
//     $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

//     // Check connection
//     if ($conn->connect_error) {
//         die("Connection failed: " . $conn->connect_error);
//     }
//     echo "Connected successfully";

    $roomids = array();
    $roomids = explode(" ", $fromform->users);
    print_object($roomids);
    foreach ($roomids as $roomid) {
//         $roomid         = $data["roomid"];

        if($roomid) {

//             $arr            = explode("_", $roomid);
//             $attemptid      = array_splice($arr, -1)[0];
//             $quizid1        = array_splice($arr, -1)[0];
//             $username       = implode("_", $arr);
//             //         echo 'un ' . $username;
//             $userdata           = $DB->get_record('user', array('username'=>$username));
//             if($user) {
//                 $userid         = $userdata->id;
//             }

            $sql = 'SELECT * FROM {quizaccess_hbmon_livetable1} WHERE roomid = "' . $roomid . '"';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
//             echo 'sql' . $sql . 'hi';
            $result    = $DB->get_records_sql($sql);
//             $result = $conn->query($sql);
//             print_object($result);
            if (!empty($result)){
//             if ($result->num_rows > 0) {
                // Output data of each row.
                foreach ($result as $record) {
//                 while($data = $result->fetch_assoc()) {
                    $roomid         = $record->roomid;
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
                    if($quizid1 == $quiz->id) {
                        $status          = $record->status;
                        $timetoconsider  = $record->timetoconsider;
                        $livetime        = $record->livetime;
                        $deadtime        = $record->deadtime;

                        $currentTimestamp = intval(microtime(true)*1000);

                        if ($status == 'Live') {
                            $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
//                             $statustodisplay = '<font color="green"><i>Online</i></font>';
                        } else {
                            $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
//                             $statustodisplay = '<font color="red"><i>Offline</i></font>';
                        }

                        $seconds = intval($livetime / 1000);
                        $dtF = new DateTime('@0');
                        $dtT = new DateTime("@$seconds");
                        $lt =  $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');

                        $seconds1 = intval($deadtime / 1000);
                        $dtF1 = new DateTime('@0');
                        $dtT1 = new DateTime("@$seconds1");
                        $dt =  $dtF1->diff($dtT1)->format('%a d, %h h : %i m : %s s');

                        $humanisedlivetime = $lt;
                        $humaniseddeadtime = $dt;
                        $timelimit = $quiz->timelimit + intval($deadtime / 1000);
                        $fromform->userid = $userid;
                        $fromform->timelimit = $timelimit;
//                         print_object($fromform);


                        // Process the data.
                        $fromform->quiz = $quiz->id;

                        // Replace unchanged values with null.
                        foreach ($keys as $key) {
                            if ($fromform->{$key} == $quiz->{$key}) {
                                $fromform->{$key} = null;
                            }
                        }

                        // See if we are replacing an existing override.
                        $userorgroupchanged = false;
                        if (empty($override->id)) {
                            $userorgroupchanged = true;
                        } else if (!empty($fromform->userid)) {
                            $userorgroupchanged = $fromform->userid !== $override->userid;
                        } else {
                            $userorgroupchanged = $fromform->groupid !== $override->groupid;
                        }

                        if ($userorgroupchanged) {
                            $conditions = array(
                                    'quiz' => $quiz->id,
                                    'userid' => empty($fromform->userid)? null : $fromform->userid,
                                    'groupid' => empty($fromform->groupid)? null : $fromform->groupid);
                            if ($oldoverride = $DB->get_record('quiz_overrides', $conditions)) {
                                // There is an old override, so we merge any new settings on top of
                                // the older override.
                                foreach ($keys as $key) {
                                    if (is_null($fromform->{$key})) {
                                        $fromform->{$key} = $oldoverride->{$key};
                                    }
                                }
                                // Set the course module id before calling quiz_delete_override().
                                $quiz->cmid = $cm->id;
                                quiz_delete_override($quiz, $oldoverride->id);
                            }
                        }

                        // Set the common parameters for one of the events we may be triggering.
                        $params = array(
                            'context' => $context,
                            'other' => array(
                                        'quizid' => $quiz->id
                            )
                        );
                        if (!empty($override->id)) {
                            $fromform->id = $override->id;
                            $DB->update_record('quiz_overrides', $fromform);

                            // Determine which override updated event to fire.
                            $params['objectid'] = $override->id;
                            if (!$groupmode) {
                                $params['relateduserid'] = $fromform->userid;
                                $event = \mod_quiz\event\user_override_updated::create($params);
                            } else {
                                $params['other']['groupid'] = $fromform->groupid;
                                $event = \mod_quiz\event\group_override_updated::create($params);
                            }

                            // Trigger the override updated event.
                            $event->trigger();
                        } else {
                            unset($fromform->id);
                            $fromform->id = $DB->insert_record('quiz_overrides', $fromform);

                            // Determine which override created event to fire.
                            $params['objectid'] = $fromform->id;
                            if (!$groupmode) {
                                $params['relateduserid'] = $fromform->userid;
                                $event = \mod_quiz\event\user_override_created::create($params);
                            } else {
                                $params['other']['groupid'] = $fromform->groupid;
                                $event = \mod_quiz\event\group_override_created::create($params);
                            }

                            // Trigger the override created event.
                            $event->trigger();
                        }

                        quiz_update_open_attempts(array('quizid'=>$quiz->id));
                        if ($groupmode) {
                            // Priorities may have shifted, so we need to update all of the calendar events for group overrides.
                            quiz_update_events($quiz);
                        } else {
                            // User override. We only need to update the calendar event for this user override.
                            quiz_update_events($quiz, $fromform);
                        }
                    }
                }
            }

        }


    }


    // Process the data.
//     $fromform->quiz = $quiz->id;

//     // Replace unchanged values with null.
//     foreach ($keys as $key) {
//         if ($fromform->{$key} == $quiz->{$key}) {
//             $fromform->{$key} = null;
//         }
//     }

//     // See if we are replacing an existing override.
//     $userorgroupchanged = false;
//     if (empty($override->id)) {
//         $userorgroupchanged = true;
//     } else if (!empty($fromform->userid)) {
//         $userorgroupchanged = $fromform->userid !== $override->userid;
//     } else {
//         $userorgroupchanged = $fromform->groupid !== $override->groupid;
//     }

//     if ($userorgroupchanged) {
//         $conditions = array(
//                 'quiz' => $quiz->id,
//                 'userid' => empty($fromform->userid)? null : $fromform->userid,
//                 'groupid' => empty($fromform->groupid)? null : $fromform->groupid);
//         if ($oldoverride = $DB->get_record('quiz_overrides', $conditions)) {
//             // There is an old override, so we merge any new settings on top of
//             // the older override.
//             foreach ($keys as $key) {
//                 if (is_null($fromform->{$key})) {
//                     $fromform->{$key} = $oldoverride->{$key};
//                 }
//             }
//             // Set the course module id before calling quiz_delete_override().
//             $quiz->cmid = $cm->id;
//             quiz_delete_override($quiz, $oldoverride->id);
//         }
//     }

//     // Set the common parameters for one of the events we may be triggering.
//     $params = array(
//         'context' => $context,
//         'other' => array(
//             'quizid' => $quiz->id
//         )
//     );
//     if (!empty($override->id)) {
//         $fromform->id = $override->id;
//         $DB->update_record('quiz_overrides', $fromform);

//         // Determine which override updated event to fire.
//         $params['objectid'] = $override->id;
//         if (!$groupmode) {
//             $params['relateduserid'] = $fromform->userid;
//             $event = \mod_quiz\event\user_override_updated::create($params);
//         } else {
//             $params['other']['groupid'] = $fromform->groupid;
//             $event = \mod_quiz\event\group_override_updated::create($params);
//         }

//         // Trigger the override updated event.
//         $event->trigger();
//     } else {
//         unset($fromform->id);
//         $fromform->id = $DB->insert_record('quiz_overrides', $fromform);

//         // Determine which override created event to fire.
//         $params['objectid'] = $fromform->id;
//         if (!$groupmode) {
//             $params['relateduserid'] = $fromform->userid;
//             $event = \mod_quiz\event\user_override_created::create($params);
//         } else {
//             $params['other']['groupid'] = $fromform->groupid;
//             $event = \mod_quiz\event\group_override_created::create($params);
//         }

//         // Trigger the override created event.
//         $event->trigger();
//     }

//     quiz_update_open_attempts(array('quizid'=>$quiz->id));
//     if ($groupmode) {
//         // Priorities may have shifted, so we need to update all of the calendar events for group overrides.
//         quiz_update_events($quiz);
//     } else {
//         // User override. We only need to update the calendar event for this user override.
//         quiz_update_events($quiz, $fromform);
//     }

    if (!empty($fromform->submitbutton)) {
        redirect($overridelisturl);
    }

//     // The user pressed the 'again' button, so redirect back to this page.
//     $url->remove_params('cmid');
//     $url->param('action', 'duplicate');
//     $url->param('id', $fromform->id);
//     redirect($url);

}

// Print the form.
$pagetitle = get_string('editoverride', 'quiz');
$PAGE->navbar->add($pagetitle);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));

$mform->display();

echo $OUTPUT->footer();

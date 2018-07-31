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
 * Create user override.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
// require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');


/**
 * A rule implementing heartbeat monitor.
 *
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class createoverride {

    public function my_override($cmid, $roomid, $fromform, $quiz) {
        global $DB;
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
//         $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Creating a new override.
//         $data = new stdClass();

        // Merge quiz defaults with data.
//         $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
//         foreach ($keys as $key) {
//             if (!isset($data->{$key}) || $reset) {
//                 $data->{$key} = $quiz->{$key};
//             }
//         }

        if($roomid) {
            // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
            $select_sql = 'SELECT *
                                FROM {quizaccess_hbmon_livetable1}
                                WHERE roomid = "' . $roomid . '"'.
                            //    AND status = "Live"
                                'AND deadtime > 60';
            $records = $DB->get_records_sql($select_sql);

            if (!empty($records)){
                // Process data of each row.
                foreach ($records as $record) {
                    $roomid         = $record->roomid;
                    $arr            = explode("_", $roomid);
                    $attemptid      = array_splice($arr, -1)[0];
                    $my_quizid      = array_splice($arr, -1)[0];
                    $username       = implode("_", $arr);

                    $user = $DB->get_record('user', array('username'=>$username));
//                     $qa = $DB->get_record('quiz_attempts', array('id'=>$attemptid));

                    if($user) {
                        $userid = $user->id;
                    } else {
//                         $userid = null;
                        break;
                    }

                    if($my_quizid == $quiz->id) {
                        $status          = $record->status;
                        $timetoconsider  = $record->timetoconsider;
                        $livetime        = $record->livetime;
                        $deadtime        = $record->deadtime;

//                         $currentTimestamp = intval(microtime(true)*1000);
                        $currentTimestamp = time();

                        if ($status == 'Live') {
                            $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
                        } else {
                            $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
                        }

//                         $timelimit = $quiz->timelimit + intval($deadtime / 1000);
                        $timelimit = $quiz->timelimit + $deadtime;

                        //--------------------------------------------------------------------------

//                         echo '<br>-- fromfrom obj --';
//                         print_object($fromform);
//                         echo '<br>-- fromfrom obj --';
//                         print_object($quiz);

                        // Process the data.
                        $fromform->quiz = $quiz->id;
                        $fromform->userid = $userid;
                        $fromform->timelimit = $timelimit;

                        // Replace unchanged values with null.
                        $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
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
                                    'quiz'      => $quiz->id,
                                    'userid'    => empty($fromform->userid)? null : $fromform->userid,
                                    'groupid'   => empty($fromform->groupid)? null : $fromform->groupid);
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
                                'context'   => $context,
                                'other'     => array(
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
                            // if (!$groupmode) {
                                $params['relateduserid'] = $fromform->userid;
                                $event = \mod_quiz\event\user_override_created::create($params);
                            // } else {
                                // $params['other']['groupid'] = $fromform->groupid;
                                // $event = \mod_quiz\event\group_override_created::create($params);
                            // }

                            // Trigger the override created event.
                            $event->trigger();
                        }

                        quiz_update_open_attempts(array('quizid'=>$quiz->id));
                        // if ($groupmode) {
                        // Priorities may have shifted, so we need to update all of the calendar events for group overrides.
                            // quiz_update_events($quiz);
                        // } else {
                        // User override. We only need to update the calendar event for this user override.
                            quiz_update_events($quiz, $fromform);
                        // }

                        //======================================================
                        // Reset database record.
                        $livetime = $livetime + $deadtime;
                        $deadtime = 0;
                        $update_sql = 'UPDATE {quizaccess_hbmon_livetable1}
                                            SET deadtime = 0
                                            WHERE roomid = "' . $roomid . '"';
                        $update_sql_result = $DB->execute($update_sql);
                    }
                }
            }
        }
    }

    public function reset_timelimit_override($cmid, $roomid, $fromform, $quiz) {
        global $DB;
        $context = context_module::instance($cmid);

        $arr            = explode("_", $roomid);
        $attemptid      = array_splice($arr, -1)[0];
        $my_quizid      = array_splice($arr, -1)[0];
        $username       = implode("_", $arr);

        $user = $DB->get_record('user', array('username'=>$username));
        $quiz = $DB->get_record('quiz', array('id'=>$my_quizid));
//         $qa = $DB->get_record('quiz_attempts', array('id'=>$attemptid));
        $userid = $user->id;

        // Process the data.
        $fromform->quiz = $quiz->id;
        $fromform->userid = $userid;
        $fromform->timelimit = $quiz->timelimit;

        // Replace unchanged values with null.
        $keys = array('timeopen', 'timeclose', 'attempts', 'password');
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
                    'quiz'      => $quiz->id,
                    'userid'    => empty($fromform->userid)? null : $fromform->userid,
                    'groupid'   => empty($fromform->groupid)? null : $fromform->groupid);
            if ($oldoverride = $DB->get_record('quiz_overrides', $conditions)) {
                // There is an old override, so we merge any new settings on top of
                // the older override.
                foreach ($keys as $key) {
                    if (is_null($fromform->{$key})) {
                        $fromform->{$key} = $oldoverride->{$key};
                    }
                }
                // Set the course module id before calling quiz_delete_override().
                $quiz->cmid = $cmid;
                quiz_delete_override($quiz, $oldoverride->id);
            }
        }

        // Set the common parameters for one of the events we may be triggering.
        $params = array(
                    'context'   => $context,
                    'other'     => array(
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
            // if (!$groupmode) {
                $params['relateduserid'] = $fromform->userid;
                $event = \mod_quiz\event\user_override_created::create($params);
            // } else {
                // $params['other']['groupid'] = $fromform->groupid;
                // $event = \mod_quiz\event\group_override_created::create($params);
            // }

            // Trigger the override created event.
            $event->trigger();
        }

        quiz_update_open_attempts(array('quizid'=>$quiz->id));
        // if ($groupmode) {
        // Priorities may have shifted, so we need to update all of the calendar events for group overrides.
            // quiz_update_events($quiz);
        // } else {
        // User override. We only need to update the calendar event for this user override.
            quiz_update_events($quiz, $fromform);
        // }
    }
}
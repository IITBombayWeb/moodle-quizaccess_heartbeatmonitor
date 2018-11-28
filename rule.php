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

require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/hbmonconfig.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimitoverride.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/mylib.php');

//require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/rulelogs.txt');



/**
 * A rule implementing heartbeat monitor.
 *
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_heartbeatmonitor extends quiz_access_rule_base {

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->hbmonrequired)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }
/*
    public function debuglog($file, $text) {
        echo '<br><br><br>in debuglog<br>';
        $micro_date = microtime();
        $date_array = explode(" ", $micro_date);
        $date = date("Y-m-d H:i:s", $date_array[1]);

        $tmp = explode(".", $date_array[0]);

        $contents = $date . "." . $tmp[1] . ": " . $text;

        echo "<br><br><br><br>I am here <br>";

        file_put_contents($file, $contents);

    }
*/

    public function prevent_access() {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $HBCFG;

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($HBCFG->wwwroot . ':' . $HBCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );
//         echo '<br><br><br>-- In prevent access --';

//         // Use this to delete user-override when the attempt finishes.
//         $this->current_attempt_finished();
//         print_object($this->get_superceded_rules());

        // User details.
        $sessionkey = sesskey();
        $userid     = $USER->id;
        $username   = $USER->username;

        // Quiz details.
        $quiz       = $this->quizobj->get_quiz();
        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
//         $context    = $this->quizobj->get_context();
//         print_object($this->quizobj);   // Contains quiz timeopen, timeclose etc.

        // Try connecting to the node server.
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);

	//$myfile = fopen("rulelogs.txt", "a");
	$myfile = $CFG->dirroot . "/mod/quiz/accessrule/heartbeatmonitor/padebug.log";
	//echo "<br><br><br> myfile: " . $myfile;
/*
        // If unfinished attempt.
        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
            $unfinishedattemptid = $unfinishedattempt->id;
            $unfinished = $unfinishedattempt->state == quiz_attempt::IN_PROGRESS;
	    
	    $txt = "Prev atmpt: unfinished ". $USER->username;		
	    debuglog($myfile, $txt);

	    //echo $a;
            if ($unfinished) {
                $attemptid  = $unfinishedattempt->id;
                $attemptobj = quiz_attempt::create($attemptid);

                // Check that this attempt belongs to this user.
                if ($attemptobj->get_userid() != $USER->id) {
                    throw new moodle_quiz_exception($attemptobj->get_quizobj(), 'notyourattempt');
                } else {
                    $roomid = $username . '_' . $quizid . '_' . $attemptid;
           	    
		    debuglog($myfile, "P:a " . $roomid );

                    // If node server is down.
                    if(!$phpws_result) {
                        $fetchtimesql = 'SELECT *
                                            FROM {quizaccess_hbmon_livetable}
                                            WHERE roomid = "' . $roomid . '"';
                        $record = $DB->get_record_sql($fetchtimesql);

                        if (!empty($record)){
                            $status           = $record->status;
                            $timetoconsider   = $record->timetoconsider;
                            $livetime         = $record->livetime;
                            $timeserver       = $record->timeserver;
                            $currenttimestamp = intval(microtime(true));

                            if ($status == 'Live') {
                                // ttc = ndwn ==== llt of prev tsvr
                                $fetchtimeserversql = 'SELECT *
                                                            FROM {quizaccess_hbmon_timeserver}
                                                            WHERE timeserverid = ' . $timeserver;
                                $record1 = $DB->get_record_sql($fetchtimeserversql);

                                if (!empty($record1)){
//                                     $livetime = ($currenttimestamp - $record1->lastlivetime) + $livetime;
                                    $livetime = ($record1->lastlivetime - $timetoconsider) + $livetime;
//                                     $livetime = ($currenttimestamp - $timetoconsider) + $livetime;

    //                                 echo '<br><br><br>-- Updating --';
//                                     $update_sql = 'UPDATE mdl_quizaccess_hbmon_livetable SET status = "Dead",
//                                                         timetoconsider = ' . $currenttimestamp . ',
//                                                         livetime = ' . $livetime . '
//                                                         WHERE roomid = "' . $roomid . '"';

                                    $update_sql = 'UPDATE {quizaccess_hbmon_livetable} SET status = "Dead",
                                                       timetoconsider = ' . $record1->lastlivetime . ',
                                                       livetime = ' . $livetime . '
                                                       WHERE   roomid = "' . $roomid . '"';

                                    $update_sql_result = $DB->execute($update_sql);
                                }
                            }
                        }

                        return get_string('servererr', 'quizaccess_heartbeatmonitor');
                    } else {
			if($attemptid) {
                        $PAGE->requires->js_init_call('client', array($quizid, $userid, $username, $attemptid, $sessionkey, json_encode($HBCFG)));

			$txt1 = "P:a " . $username . " " . $attemptid .  " after init call ----- ";
			debuglog($myfile, $txt1);
                        
			$hbmonmodesql = "SELECT hbmonmode
                                            FROM {quizaccess_enable_hbmon}
                                            WHERE quizid = $quizid";
                        $hbmonmode = $DB->get_field_sql($hbmonmodesql);
                        if ($hbmonmode) {
//                             echo '<br>-- In rule crtovrrde -- <br>-- qa state - ' . $quiza->state;
                            // If deadtime is there, then create override.
                            $select_sql = 'SELECT *
                                                FROM {quizaccess_hbmon_livetable}
                                                WHERE roomid = "' . $roomid . '"' .
                                                /*  AND status = "Live" */
  /*                                              'AND deadtime > 60';
                            $records = $DB->get_records_sql($select_sql);

                            if (!empty($records)){
                                foreach ($records as $record) {
                                    if($roomid == $record->roomid){
//                                         echo '<br>-- In rule crtovrrd 2 --';
                                        $this->create_user_override($roomid, $cmid, $quiz);
                                    }
                                    break;
                                }
                            }
                        }
			}
                    }
                }
            }
            return false;
        } else {
            if(!$phpws_result) {
                // If new attempt.
                return get_string('servererr', 'quizaccess_heartbeatmonitor');
            }
        }
//	fclose($myfile); */
    }

    public function setup_attempt_page($page) {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $HBCFG;

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($HBCFG->wwwroot . ':' . $HBCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );

        // Use this to delete user-override when the attempt finishes.
//         $this->current_attempt_finished();
//         echo '<br><br><br>-- In setup attempt --';

        $sessionkey = sesskey();
        $userid     = $USER->id;
        $username   = $USER->username;

        $quiz       = $this->quizobj->get_quiz();
        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
//         $context    = $this->quizobj->get_context();
	
	//$myfile = fopen("rulelogs.txt", "w") or die("Unable to open file!");
	//fwrite($myfile, "Setup atmpt: " . $username . " " . round(microtime(true) * 1000) . " bf sql");

	//echo 'sub atmpt';
        $myfile = $CFG->dirroot . "/mod/quiz/accessrule/heartbeatmonitor/sadebug.log";
	$txt = "Setup atmpt: " . $username .  " bf sql ----- ";
	debuglog($myfile, $txt);
/*
        // To do - Get attempt id from url here.
        $sql = 'SELECT *
                    FROM {quiz_attempts}
                    WHERE userid = :userid
                    AND quiz = :quizid
                    ORDER BY id DESC
                    LIMIT 1';
        $params['quizid'] = $quizid;
        $params['userid'] = $userid;
        $attempt = $DB->get_record_sql($sql, $params);
	
	debuglog($myfile, "Setup atmpt: " . $username . " " . $attempt->id . " af sql ----- ");
        
	if($attempt) {
            $attemptid  = $attempt->id;
            $quiza      = $DB->get_record('quiz_attempts', array('id' => $attemptid));
            $state      = $quiza->state;
//             echo '<br>-- qa state - ' . $quiza->state;

            if($quiza->state == 'finished') {
//                 echo '<br>-- qa state - ' . $quiza->state;
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
               /* $roomid = $username . '_' . $quizid . '_' . $attemptid;
        	debuglog($myfile, "Setup atmpt: " . $roomid .  " bf sql2 ----- ");
	        
		$select_sql = 'SELECT *
                                    FROM {quizaccess_hbmon_livetable}
                                    WHERE roomid = "' . $roomid . '"';
                                    // AND status = "Dead"';
                                    // AND deadtime > 60000';
                $records = $DB->get_records_sql($select_sql);

                if (!empty($records)){
                    foreach ($records as $record) {
                        if($roomid == $record->roomid){
                            $this->create_user_override($roomid, $cmid, $quiz, $state);
                        }
                    }
                }
            }
        }*/
//	fclose($myfile);
    }

    protected function create_user_override($roomid, $cmid, $quiz, $state = null) {
        global $DB, $CFG;

//         $context = context_module::instance($cmid);
//         $groupmode = null;
//         $action = null;
//         $override = null;

        // Add or edit an override.
//         require_capability('mod/quiz:manageoverrides', $context);

        // Creating a new override.
//         $data = new stdClass();

        // Merge quiz defaults with data.
//         $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
//         foreach ($keys as $key) {
//             if (!isset($data->{$key}) || $reset) {
//                 $data->{$key} = $quiz->{$key};
//             }
//         }

        // True if group-based override.
//         $action = null;
//         $groupmode = !empty($data->groupid) || ($action === 'addgroup' && empty($overrideid));

        // Setup the form data required for processing as in overrideedit.php file.
        $override = new stdClass();
//         $override->action = 'adduser';
        $override->cmid = $cmid;
        $override->quiz = $quiz->id;
//         $override->_qf__quiz_override_form = 1;
//         $override->mform_isexpanded_id_override = 1;
        $override->userid = '';
        $override->password = '';
        $override->timeopen = $quiz->timeopen;
        $override->timeclose = $quiz->timeclose;
        $override->timelimit = 0;
        $override->attempts = $quiz->attempts;
//         $override->submitbutton = 'Save';

        $dataobj = new timelimitoverride();

        if($state === 'finished') {
            // $dataobj->reset_timelimit_override($cmid, $roomid, $override, $quiz);
        } else {
//             echo '<br>-- in create ovrrde --<br>';
//             print_object($quiz);
            $dataobj->create_timelimit_override($cmid, $roomid, $override, $quiz);
        }
    }

    public function get_superceded_rules() {
        return array();
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $hbmonsettingsarray   = array();

        $hbmonsettingsarray[] = $mform->createElement('select', 'hbmonrequired',
                get_string('hbmonrequired', 'quizaccess_heartbeatmonitor'), array(
                        0 => get_string('notrequired', 'quizaccess_heartbeatmonitor'),
                        1 => get_string('hbmonrequiredoption', 'quizaccess_heartbeatmonitor')
                ));

//         $hbmonsettingsarray[] = $mform->createElement('advcheckbox', 'allowifunassigned', '', 'Allow Unmapped', '', array(0, 1));
//         $mform->disabledIf('allowifunassigned', 'hbmonrequired', 'neq', 1);

        $radioarray = array();
        $hbmonsettingsarray[]= $mform->createElement('radio', 'hbmonmode', '', get_string('automatic', 'quizaccess_heartbeatmonitor'), 1);
        $hbmonsettingsarray[]= $mform->createElement('radio', 'hbmonmode', '', get_string('manual', 'quizaccess_heartbeatmonitor'), 0);
//         $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
//         $hbmonsettingsarray[] = $radioarray;
        $mform->disabledIf('hbmonmode', 'hbmonrequired', 'neq', 1);
	$mform->setDefault('hbmonmode', 1);

        $mform->addGroup($hbmonsettingsarray, 'enablehbmon', get_string('hbmonrequired', 'quizaccess_heartbeatmonitor'), array(' '), false);
        $mform->addHelpButton('enablehbmon', 'hbmonrequired', 'quizaccess_heartbeatmonitor');
        $mform->setAdvanced('enablehbmon', true);
    }

    public static function validate_settings_form_fields(array $errors,
            array $data, $files, mod_quiz_mod_form $quizform) {
        return $errors;
    }

    public static function get_browser_security_choices() {
        return array();
    }

    public static function save_settings($quiz) {
        global $DB;
        if (empty($quiz->hbmonrequired)) {
            $DB->delete_records('quizaccess_enable_hbmon', array('quizid' => $quiz->id));
        } else {
            if (!$DB->record_exists('quizaccess_enable_hbmon', array('quizid' => $quiz->id))) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->hbmonrequired = 1;
                $record->hbmonmode = $quiz->hbmonmode;
                $DB->insert_record('quizaccess_enable_hbmon', $record);
            } else {
                $select = "quizid = $quiz->id";
                $id = $DB->get_field_select('quizaccess_enable_hbmon', 'id', $select);
                $record = new stdClass();
                $record->id = $id;
                $record->hbmonmode = $quiz->hbmonmode;
                $DB->update_record('quizaccess_enable_hbmon', $record);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_enable_hbmon', array('quizid' => $quiz->id));
    }

    public static function get_settings_sql($quizid) {
        return array(
                'hbmonrequired',
                'LEFT JOIN {quizaccess_enable_hbmon} enable_hbmon ON enable_hbmon.quizid = quiz.id',
                array()
        );
    }

     public static function get_extra_settings($quizid) {
         return array();
     }
}





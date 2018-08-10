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
        global $DB;

        if (empty($quizobj->get_quiz()->hbmonrequired)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    public function prevent_access() {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $hbCFG;

        echo '<br><br><br>';
        echo '<br>-- host --'.$hbCFG->host;
        echo '<br>-- port --'.$hbCFG->port;
        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($hbCFG->wwwroot . ':' . $hbCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );
//         $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/server.js') );

        //===========================================================
        // Check node server status, if hbmon is enabled.
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $phpws_result = @socket_connect($socket, $hbCFG->host, $hbCFG->port);
        echo '<br><br>-- phpws --<br>';
        print_object($phpws_result);
        if(!$phpws_result) {
            return 'Time server is not on. Please contact your instructor.';
        } else {

        // Use this to delete user-override when the attempt finishes.
//         $this->current_attempt_finished();
        echo '<br><br><br>-- In prevent access --';
//         print_object($this->get_superceded_rules());

        //===========================================================

        // Testing node server status using php web sockets.
//         $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//         $phpws_result = socket_connect($socket, '127.0.0.1', 3000);
//         if(!$phpws_result) {
//             exec("node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js 2>&1", $output);
// //             die('cannot connect '.socket_strerror(socket_last_error()).PHP_EOL);
//         } else {
//             $bytes = socket_write($socket, "Hello World");
//             echo "wrote ".number_format($bytes).' bytes to socket'.PHP_EOL;
//         }
//         $bytes = socket_write($socket, "Hello World");
//         echo "wrote ".number_format($bytes).' bytes to socket'.PHP_EOL;

        //===========================================================

        echo '<br>-- shell output -- ';
        // Start node server.
//         $shell_output = shell_exec("node var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js");
//         print_object($shell_output);

//         exec("node $CFG->wwwroot" . "/mod/quiz/accessrule/heartbeatmonitor/server.js &", $output);
//         print_object($output);

//         exec("node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js 2>&1", $output);
//         print_object($output);

//         if(!$phpws_result) {
// //             die('cannot connect '.socket_strerror(socket_last_error()).PHP_EOL);
//             exec("node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js 2>&1", $output);
//             print_object($output);
//         }

        //===========================================================

        $sessionkey = sesskey();
//         $userid     = $_SESSION['USER']->id;
//         $username   = $_SESSION['USER']->username;
        $userid     = $USER->id;
        $username   = $USER->username;

        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
//         $context    = $this->quizobj->get_context();

//         print_object($this->quizobj);   // Contains quiz timeopen, timeclose etc.

        $quiz = $this->quizobj->get_quiz();
//         echo '<br>-- prev acc qz obj --<br>';
//         print_object($this->quizobj);

//     	$url = 'http://127.0.0.1:3000/';
//     	$ch = curl_init($url);
//     	curl_setopt($ch, CURLOPT_NOBODY, true);
//     	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//     	curl_exec($ch);
//     	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     	curl_close($ch);
//     	if (200 == $retcode) {
//=========================================================================================================
//         $sql_fetch_attemptid = 'SELECT *
//                                     FROM {quiz_attempts}
//                                     WHERE userid = ' . $userid . '
//                                     AND quiz = ' . $quizid . '
//                                     ORDER BY id DESC
//                                     LIMIT 1 ';
//         $records_fetch_attemptid = $DB->get_record_sql($sql_fetch_attemptid);
        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
        $unfinishedattemptid = $unfinishedattempt->id;
        $unfinished = $unfinishedattempt->state == quiz_attempt::IN_PROGRESS;

//         if ($records_fetch_attemptid){
//         if ($unfinished) {
        $attemptid  = $unfinishedattempt->id;   //$records_fetch_attemptid->id;

        // No need now. Since, qa is used just for checking state, which is now obtained through unfinattpt obj.
        $attemptobj = quiz_attempt::create($attemptid);
        // Check that this attempt belongs to this user.
        if ($attemptobj->get_userid() != $USER->id) {
            throw new moodle_quiz_exception($attemptobj->get_quizobj(), 'notyourattempt');
        }
//         $qa = quiz_attempt::create($attemptid);


//             $qa         = $DB->get_record('quiz_attempts', array('id'=>$attemptid));
//             $state      = $qa->state;
            $roomid     = $username . '_' . $quizid . '_' . $attemptid;
//             echo '<br>-- qa state - ' . $qa->state;

//             if($qa->state != 'finished') {
                $PAGE->requires->js_init_call('client', array($quizid, $userid, $username, $attemptid, $sessionkey, json_encode($CFG)));

                $hbmonmodesql = "SELECT hbmonmode
                                    FROM {quizaccess_enable_hbmon}
                                    WHERE quizid = $quizid";
                $hbmonmode = $DB->get_field_sql($hbmonmodesql);
                if ($hbmonmode) {
//                     echo '<br>-- In rule crtovrrde -- <br>-- qa state - ' . $qa->state;
                    // If deadtime is there, then create override.
                    $select_sql = 'SELECT *
                                        FROM {quizaccess_hbmon_livetable1}
                                        WHERE roomid = "' . $roomid . '"' .
                                        /*  AND status = "Live" */
                                        'AND deadtime > 60';
                    $records = $DB->get_records_sql($select_sql);

                    if (!empty($records)){
                        foreach ($records as $record) {
                            if($roomid == $record->roomid){
                                echo '<br>-- In rule crtovrrd 2 --';
                                $this->create_override($roomid, $cmid, $quiz);
                            }
                            break;
                        }
                    }
//                 }
            }
        }
//         return false;
        }
    }

    public function setup_attempt_page($page) {
        global $CFG, $PAGE, $_SESSION, $DB;

//         // If heartbeat monitoring is on 'auto' mode.
//         $hbmonmodesql = "SELECT hbmonmode
//                             FROM {quizaccess_enable_hbmon}
//                             WHERE quizid = $quizid";
//         $hbmonmode = $DB->get_field_sql($hbmonmodesql);
//         if ($hbmonmode) {

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($hbCFG->wwwroot . ':' . $hbCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );

        // Use this to delete user-override when the attempt finishes.
//         $this->current_attempt_finished();
//         echo '<br><br><br>-- In setup attempt --';

//         $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//         $phpws_result = socket_connect($socket, '127.0.0.1', 3000);

//         if(!$phpws_result) {
//             exec("node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js 2>&1", $output);
//             print_object($output);
//         }

        $sessionkey = sesskey();
        $userid     = $_SESSION['USER']->id;
        $username   = $_SESSION['USER']->username;

        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
        $context    = $this->quizobj->get_context();

//         print_object($this->quizobj);   // Contains quiz timeopen, timeclose etc.

//         $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
// //         $phpws_result = socket_connect($socket, '127.0.0.1', 3000);

// //         if(!$phpws_result) {
//         if(!($phpws_result = socket_connect($socket, '127.0.0.1', 3000))) {
// //             die('cannot connect '.socket_strerror(socket_last_error()).PHP_EOL);
// //             exec("node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js &");
// //             print_object($output);
//             $cmd = 'node /var/www/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js &';
//             $res = shell_exec($cmd);
//             print_object($res);
//         } else {
//             $bytes = socket_write($socket, "Hello World");
//             echo "wrote ".number_format($bytes).' bytes to socket'.PHP_EOL;
//         }

        $quiz = $this->quizobj->get_quiz();

        // To do - Get attempt id from url here.
        $sql_fetch_attemptid = 'SELECT *
                                    FROM {quiz_attempts}
                                    WHERE userid = ' . $userid . '
                                    AND quiz = ' . $quizid . '
                                    ORDER BY id DESC
                                    LIMIT 1 ';
        $records_fetch_attemptid = $DB->get_record_sql($sql_fetch_attemptid);

        if ($records_fetch_attemptid){
            $attemptid  = $records_fetch_attemptid->id;
            $qa         = $DB->get_record('quiz_attempts', array('id'=>$attemptid));
            $state      = $qa->state;
            $roomid     = $username . '_' . $quizid . '_' . $attemptid;
//             echo '<br>-- qa state - ' . $qa->state;

            if($qa->state == 'finished') {
//                 echo '<br>-- qa state - ' . $qa->state;
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
                                    // AND status = "Dead"';
                                    // AND deadtime > 60000';
                $records = $DB->get_records_sql($select_sql);

                if (!empty($records)){
                    foreach ($records as $record) {
                        if($roomid == $record->roomid){
                            $this->create_override($roomid, $cmid, $quiz, $state);
                        }
                    }
                }
            }
        }
//         }
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
        $fromform->timeopen = $quiz->timeopen;
        $fromform->timeclose = $quiz->timeclose;
        $fromform->timelimit = 0;
        $fromform->attempts = $quiz->attempts;
        $fromform->submitbutton = 'Save';

        if($state === 'finished') {
            $myobj = new createoverride();
            $myobj->reset_timelimit_override($cmid, $roomid, $fromform, $quiz);
        } else {
//             echo '<br>-- in create ovrrde --<br>';
//             print_object($quiz);
            $myobj = new createoverride();
            $myobj->my_override($cmid, $roomid, $fromform, $quiz);
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
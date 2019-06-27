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
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/override.php');


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

    // Timelimit override functions. Since, we are superceeding this rule.
    public function description() {
        return get_string('quiztimelimit', 'quizaccess_timelimit',
                format_time($this->quiz->timelimit));
    }

    public function debuglog($fn, $msgarg = '', $record = null) {
        global $CFG;
        //         date('D M d Y H'); date('l jS \of F Y h:i:s A')
        $msg = $msgarg;
        $log = null;

        $fileName = $CFG->dirroot . "/mod/quiz/accessrule/heartbeatmonitor/phplogs.text";
        $fp = fopen($fileName,"a+");
        if( $fp == false )
        {
            echo ( "Error in opening file" );
            exit();
        }
        if (isset($record->roomid)) {
//             echo '<br><br><br>in debuglog record obj';
//             print_object($record);

            $log = "\ndebug: " . date('D M d Y H:i:s'). " GMT+0530 (IST) " . (microtime(True)*10000) . ", " .
                    "rule.php | " . $fn;
            if ($msg !== '') {
                $log .= ", " . $msg . " " . $record->roomid . " " . $record->status;
            } else {
                $log .= ", " . $record->roomid . " " . $record->status;
            }
        } else {
            $log = "\ndebug: " . date('D M d Y H:i:s'). " GMT+0530 (IST) " . (microtime(True)*10000) . ", " .
                    "rule.php | " . $fn;
            if ($msg !== '' && $msg !== "\n")
                $log .= ", " . $msg;
        }
        fwrite($fp, $log);
    }

    public function time_left_display($attempt, $timenow) {
        // If this is a teacher preview after the time limit expires, don't show the time_left
        $endtime = $this->end_time($attempt);
        if ($attempt->preview && $timenow > $endtime) {
            return false;
        }
        return $endtime - $timenow;
    }

    public function is_preflight_check_required($attemptid) {
        // Warning only required if the attempt is not already started.
        return $attemptid === null;
    }

    public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform,
            MoodleQuickForm $mform, $attemptid) {
                $mform->addElement('header', 'honestycheckheader',
                        get_string('confirmstartheader', 'quizaccess_timelimit'));
                $mform->addElement('static', 'honestycheckmessage', '',
                        get_string('confirmstart', 'quizaccess_timelimit', format_time($this->quiz->timelimit)));
    }

    public function get_superceded_rules() {
        return array('overridedmo' , 'timelimit');
    }

    public function prevent_access() {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $HBCFG;
        $fn = 'prevent_access';
        $this->debuglog('', "\n");
        $this->debuglog($fn);

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($HBCFG->wwwroot . ':' . $HBCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js'), true );

        // User details.
        $sessionkey = sesskey();
        $userid     = $USER->id;
        $username   = $USER->username;

        // Quiz details.
        $quiz       = $this->quizobj->get_quiz();
        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();

        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
            $unfinishedattemptid = $unfinishedattempt->id;
            $unfinished = $unfinishedattempt->state == quiz_attempt::IN_PROGRESS;

            if ($unfinished) {
                $attemptid  = $unfinishedattempt->id;
                $attemptobj = quiz_attempt::create($attemptid);

                // Check that this attempt belongs to this user.
                if ($attemptobj->get_userid() != $USER->id) {
                    throw new moodle_quiz_exception($attemptobj->get_quizobj(), 'notyourattempt');
                } else {
                    $roomid = $username . '_' . $quizid . '_' . $attemptid;
                    $node_up = $this->check_node_server_status($unfinishedattempt);
                    if($node_up) {
                        $this->debuglog($fn, 'call to client.js');
                        $PAGE->requires->js_init_call('client', array($roomid, json_encode($HBCFG)));
                        return false;
                    } else {
                        return 'Heartbeat time server error. Please contact your site admin.';
                    }
                }
            }
        }
    }

    public function end_time($attempt) {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $HBCFG;
        $fn = 'end_time';
        $this->debuglog('', "\n");
        $this->debuglog($fn);

        $node_up = $this->check_node_server_status($attempt);
        $this->debuglog($fn, 'node status: ' . $node_up);

        if($node_up) {
            if(isset($attempt->id)) {
                $deadtime = $this->get_deadtime($attempt);
                $this->debuglog($fn, 'deadtime: ' . $deadtime);

                if (!is_null($deadtime)) {
                    $this->debuglog($fn, 'in 1st if');

                    $roomid = $this->construct_roomid($attempt->id);
                    $this->debuglog($fn, 'roomid: ' . $roomid);

                    $record = $this->get_livetable_data($roomid);
                    $this->debuglog($fn, 'record:' , $record);

//                     $tsrecord = $this->get_timeserver_data($record->timeserver);

//                     $tssql = "SELECT * FROM {quizaccess_hbmon_timeserver} ORDER BY timeserverid DESC LIMIT 1";
//                     $tsrecord2 = $DB->get_record_sql($tssql);

//                     if (!empty($tsrecord2) && $tsrecord2->timeserverid == $record->timeserver) {
                        // User down.
//                         $this->debuglog($fn, 'ts: ' . $tsrecord2->timeserverid . ' roomts: ' . $record->timeserver);

                        if ($record->status == 'Dead') {
                            $this->debuglog($fn, 'in 2nd if');
                            $params = array(
                                        'status' => "'Live'",
                                        'deadtime' => $deadtime,
                                        'timetoconsider' => time(),
                                        'roomid' => $roomid
                                        );
                            $this->update_livetable_data($params);
                        }

                        if ($deadtime > 60) {
                            $this->create_override_auto($attempt, $deadtime);
                            $params = array(
                                        'deadtime' => 0,
                                        'extratime' => $record->extratime + $deadtime,
                                        'roomid' => $roomid
                                        );
                            $this->update_livetable_data($params);
                        }
//                     } else {
                        // Server down.
//                 }
                }
            }
//             return $attempt->timestart + $this->quiz->timelimit;
        }
        return $attempt->timestart + $this->quiz->timelimit;
    }

    protected function construct_roomid($attemptid) {
        global $USER;
        $username   = $USER->username;
        $quizid     = $this->quizobj->get_quizid();
        $roomid     = $username . '_' . $quizid . '_' . $attemptid;
        return $roomid;
    }

    protected function check_node_server_status($attempt = null) {
        global $HBCFG, $OUTPUT;

        /*
        try {
            // Try connecting to the node server.
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);
        } catch (Exception $e) {
            if (!is_null($attempt)) {
                $roomid = $this->construct_roomid($attempt->id);
                $this->process_node_server_down($roomid);
            }
            throw new moodle_exception('servererr', 'quizaccess_heartbeatmonitor', $this->quizobj->view_url());
        }
        */
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);
        if (!$phpws_result) {
            if (!empty($attempt->id)) {
                $roomid = $this->construct_roomid($attempt->id);
                $this->process_node_server_down($roomid);
                return 0;
            }
        }
        return 1;
    }

    protected function process_node_server_down($roomid) {
        $record = $this->get_livetable_data($roomid);   // worry later about handling debug if multiple records found
        $fn = 'process_node_server_down';
//         $this->debuglog('', "\n");

        if (!empty($record)) {
            $currenttimestamp = intval(microtime(true));
            if ($record->status == 'Live') {
                // ttc = ndwn ==== llt of prev tsvr
                $this->debuglog($fn, "record:", $record);$this->get_timeserver_data($record->timeserver);

                $tsrecord = $this->get_timeserver_data($record->timeserver);
                if (!empty($tsrecord)) {
                    $livetimenow = ($tsrecord->lastlivetime - $record->timetoconsider) + $record->livetime;
                    $params = array(
                                'status' => "'Dead'",
                                'timetoconsider' => $tsrecord->lastlivetime,
                                'livetime' => $livetimenow,
                                'roomid' => $roomid
                                );
                    $updateresult = $this->update_livetable_data($params);
                }
            }
        }
    }

    protected function get_timeserver_data($timeserver) {
        global $DB;
        $sql = 'SELECT *
                    FROM {quizaccess_hbmon_timeserver}
                    WHERE timeserverid = ' . $timeserver;
        $record = $DB->get_record_sql($sql);
        if (!is_null($record)) {
            return $record;
        } else {
            return null;
        }
    }

    protected function get_deadtime($attempt) {
        global $DB;
        $fn = 'get_deadtime';

        if (isset($attempt->id)) {
            $roomid = $this->construct_roomid($attempt->id);
            $record = $this->get_livetable_data($roomid);

            $tssql = "SELECT * FROM {quizaccess_hbmon_timeserver} ORDER BY timeserverid DESC LIMIT 1";
            $tsrecord2 = $DB->get_record_sql($tssql);

            if (!is_null($record)) {
                $this->debuglog($fn, 'ts: ' . $tsrecord2->timeserverid . ' roomts: ' . $record->timeserver);

                if (!empty($tsrecord2) && ($tsrecord2->timeserverid == $record->timeserver) && $record->status == "Dead") {
                    $timenow = time();
                    $deadtime = $record->deadtime + ($timenow - $record->timetoconsider);
                    return $deadtime;
                } elseif (!empty($tsrecord2) && ($tsrecord2->timeserverid != $record->timeserver)) {
                    // Server down.
                    if (isset($record->timeserver))
                        $tsrecord = $this->get_timeserver_data($record->timeserver);

                    $serverdowntime;
                    $sdowntimestart = $tsrecord->lastlivetime;
                    $sdowntimeend = $tsrecord2->timestarted;
                    $serverdowntime = $sdowntimeend - $sdowntimestart;

                    $userdowntime;
                    $udowntimestart = $record->timetoconsider;
                    $udowntimeend = time();
                    $userdowntime = $udowntimeend - $udowntimestart;

                    // Depends on policy setup.
                    $userdowntime2;
                    $userdowntime2 = $udowntimeend - $sdowntimestart;

                    // Condition 2 - Server and user, both go down.
                    $maxdowntime;
                    $arr = array($serverdowntime, $userdowntime, $userdowntime2);
                    $maxdowntime = max($arr);

                    return $maxdowntime;
                } else {
                    return $record->deadtime;
                }
            }
        }
        return null;
    }

    protected function get_livetable_data($roomid) {
        global $DB;
        $fn = 'get_livetable_data';

        $sql = 'SELECT *
                    FROM {quizaccess_hbmon_livetable}
                    WHERE roomid = "' . $roomid . '"';
        $record = $DB->get_record_sql($sql);
        if (!empty($record)) {
            $this->debuglog($fn, 'record:', $record);

            return $record;
        } else {
            return null;
        }
    }

    protected function update_livetable_data($params) {
        global $DB;
        $fn = 'update_livetable_data';

        $count = count($params) - 1;
        $sql = 'UPDATE {quizaccess_hbmon_livetable} SET ';
        foreach ($params as $key => $value) {
            $count--;
            if ($key == 'roomid')
                continue;
            if ($count)
                $sql .= $key . ' = ' . $value . ', ';
            else
                $sql .= $key . ' = ' . $value;
        }
        $sql .= ' WHERE roomid = "' . $params['roomid'] . '"';

        $result = $DB->execute($sql);
        $this->debuglog($fn, 'sql result ' . $result);
    }

    protected function create_override_auto($attempt, $deadtime) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $userid     = $USER->id;
        $quiz       = $this->quiz;
        $cmid       = $this->quizobj->get_cmid();
        $context    = context_module::instance($cmid);
        $fn = "create_override_auto";

        $this->debuglog($fn, 'deadtime', $deadtime);

        // Setup the form data required for processing as in overrideedit.php file.
        $override = new stdClass();
        $override->cmid = $cmid;

        $timelimit = $quiz->timelimit + $deadtime;
        $override->timelimit = $timelimit;

        // Direct manipulation of quiz timelimit for immediately changing the quiz->timelimit.
        // Required mainly for display of 'Timelimit' on quiz/view.php page.
        // Or else, it takes a page refresh for changes to take effect.
        $quiz->timelimit = $timelimit;

        // If timelimit is already modified (in end_time()).
        // $override->timelimit = $quiz->timelimit;

        if (($attempt->timestart + $timelimit) > $quiz->timeclose) {
            $timeclose = $attempt->timestart + $timelimit;
            $override->timeclose = $timeclose;
        } else {
            $override->timeclose = null;
        }

        // Set other keys to null.
        $override->timeopen = null;
        $override->attempts = null;
        $override->password = null;

        // Process the data.
        $override->quiz = $quiz->id;
        $override->userid = $userid;

        // See if we are replacing an existing override.
        $conditions = array(
                        'quiz' => $quiz->id,
                        'userid' => empty($override->userid)? null : $override->userid
                        );
        if ($oldoverride = $DB->get_record('quiz_overrides', $conditions)) {
            // There is an old override, so we merge any new settings on top of
            // the older override.
            $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
            foreach ($keys as $key) {
                if (is_null($override->{$key})) {
                    $override->{$key} = $oldoverride->{$key};
                }
            }

            // Set the course module id before calling quiz_delete_override().
            $quiz->cmid = $cmid;
            $override->id = $oldoverride->id;
            // quiz_delete_override($quiz, $oldoverride->id);
            $DB->update_record('quiz_overrides', $override);
        } else {
            //unset($override->id);
            $override->id = $DB->insert_record('quiz_overrides', $override);
        }
        // Parameters for events we may be triggering.
        $params = array(
                'context' => $context,
                'other' => array(
                        'quizid' => $quiz->id
                ),
                'objectid' => $override->id,
                'relateduserid' => $override->userid
        );

        $event = \mod_quiz\event\user_override_created::create($params);

        // Trigger the override created event.
        $event->trigger();

        // Update timecheckstate (as in quiz_update_open_attempts()).
        $timecheckstate = $attempt->timestart + $timelimit;
        $DB->set_field('quiz_attempts', 'timecheckstate', $timecheckstate, array('id' => $attempt->id));

        // User override. We only need to update the calendar event for this user override.
        quiz_update_events($quiz, $override);
    }
/*
    protected function create_override($roomid, $attempt, $deadtime) {
        global $DB, $CFG;
        $cmid       = $this->quizobj->get_cmid();
        $quiz       = $this->quizobj->get_quiz();

        // Setup the form data required for processing as in overrideedit.php file.
        $override = new stdClass();
        $override->cmid = $cmid;
        $override->quiz = $this->quiz->id;
        $override->userid = '';

        // Create timelimit [, timeclose] override.
        $timelimit = $this->quiz->timelimit + $deadtime;
        $override->timelimit = $timelimit;

        // Direct manipulation of quiz timelimit for immediately changing the quiz->timelimit.
        // Required mainly for display of 'Timelimit' on quiz/view.php page.
        // Or else, it takes a page refresh for changes to take effect.
        $this->quiz->timelimit = $timelimit;

        if (($attempt->timestart + $timelimit) > $this->quiz->timeclose) {
            $timeclose = $attempt->timestart + $timelimit;
            $override->timeclose = $timeclose;
        } else {
            $override->timeclose = null;
        }

        // Set other keys to null.
        $override->password = null;
        $override->timeopen = null;
        $override->attempts = null;

        $clsobj = new override();
        $clsobj->create_user_override($attempt, $override);
    }

    public function setup_attempt_page($page) {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $HBCFG;

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($HBCFG->wwwroot . ':' . $HBCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );

        // Use this to delete user-override when the attempt finishes.
//         $this->current_attempt_finished();

        $sessionkey = sesskey();
        $userid     = $USER->id;
        $username   = $USER->username;

        $quiz       = $this->quizobj->get_quiz();
        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();

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
        if($attempt) {
            $attemptid  = $attempt->id;
            $quiza      = $DB->get_record('quiz_attempts', array('id' => $attemptid));
            $state      = $quiza->state;

            if($quiza->state == 'finished') {
                $roomid = $username . '_' . $quizid . '_' . $attemptid;
                $select_sql = 'SELECT *
                                    FROM {quizaccess_hbmon_livetable}
                                    WHERE roomid = "' . $roomid . '"';
                                    // AND status = "Dead"';
                                    // AND deadtime > 60000';
                $records = $DB->get_records_sql($select_sql);

                if (!empty($records)){
                    foreach ($records as $record) {
                        if($roomid == $record->roomid){
//                             $this->create_user_override($roomid, $cmid, $quiz, $state, null);
                        }
                    }
                }
            }
        }
    }
*/
    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('header', 'hbmonheader', 'Heartbeat monitor');

        $hbmonsettingsarray   = array();
        $mform->addElement('select', 'hbmonrequired',
                get_string('hbmonrequired', 'quizaccess_heartbeatmonitor'), array(
                        0 => get_string('notrequired', 'quizaccess_heartbeatmonitor'),
                        1 => get_string('hbmonrequiredoption', 'quizaccess_heartbeatmonitor')
                ));
        $mform->addHelpButton('hbmonrequired', 'hbmonrequired', 'quizaccess_heartbeatmonitor');

        $radioarray = array();
        $hbmonsettingsarray[]= $mform->createElement('radio', 'hbmonmode', '', get_string('automatic', 'quizaccess_heartbeatmonitor'), 1);
        $hbmonsettingsarray[]= $mform->createElement('radio', 'hbmonmode', '', get_string('manual', 'quizaccess_heartbeatmonitor'), 0);
        $mform->setDefault('hbmonmode', 1);
        $mform->addGroup($hbmonsettingsarray, 'enablehbmon', 'Mode', array(' '), false);
        $mform->disabledIf('hbmonmode', 'hbmonrequired', 'neq', 1);

        $mform->addElement('text', 'nodehost', "Node host", 'maxlength="25" size="15" ');
        $mform->setType('nodehost', PARAM_HOST);
        $mform->setDefault('nodehost', 'localhost');
//         $mform->setDefault('nodehost', '10.102.1.115');
        $mform->disabledIf('nodehost', 'hbmonrequired', 'neq', 1);

        $mform->addElement('text', 'nodeport', 'Node port', 'maxlength="4" size="4" ');
        $mform->setType('nodeport', PARAM_NUMBER);
        $mform->setDefault('nodeport', '3000');
        $mform->disabledIf('nodeport', 'hbmonrequired', 'neq', 1);
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
            $DB->delete_records('quizaccess_hbmon_node', array('quizid' => $quiz->id));
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

            if (!$DB->record_exists('quizaccess_hbmon_node', array('quizid' => $quiz->id))) {
                $record1 = new stdClass();
                $record1->nodehost = $quiz->nodehost;
                $record1->nodeport = $quiz->nodeport;
                $record1->quizid = $quiz->id;
                $DB->insert_record('quizaccess_hbmon_node', $record1);
            } else {
                $select = "quizid = $quiz->id";
                $id = $DB->get_field_select('quizaccess_hbmon_node', 'id', $select);
                $record1 = new stdClass();
                $record1->id = $id;
                $record1->nodehost = $quiz->nodehost;
                $record1->nodeport = $quiz->nodeport;
                $DB->update_record('quizaccess_hbmon_node', $record1);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_enable_hbmon', array('quizid' => $quiz->id));
        $DB->delete_records('quizaccess_hbmon_node', array('quizid' => $quiz->id));
    }

    public static function get_settings_sql($quizid) {
        return array(
                'hbmon.hbmonrequired as hbmonrequired, node.nodehost as nodehost, node.nodeport as nodeport',
                'LEFT JOIN {quizaccess_enable_hbmon} hbmon ON hbmon.quizid = quiz.id
                 LEFT JOIN {quizaccess_hbmon_node} node ON node.quizid = quiz.id',
                array()
        );
//         return array(
//                 'hbmon_hbmonrequired as hbmonrequired',
//                 'LEFT JOIN {quizaccess_enable_hbmon} hbmon ON hbmon.quizid = quiz.id',
//                 array()
//         );
    }

    public static function get_extra_settings($quizid) {
        return array();
    }
}

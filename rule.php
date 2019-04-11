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

//         $quizid = $this->quizobj->get_quizid();
//         $HBCFG = hbmonconfig($quizid);

//         echo '<br> hbcfg -----------------';
//         print_object($HBCFG);

        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($HBCFG->wwwroot . ':' . $HBCFG->port . '/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeatmonitor/client.js') );

        // User details.
        $sessionkey = sesskey();
        $userid     = $USER->id;
        $username   = $USER->username;

        // Quiz details.
        $quiz       = $this->quizobj->get_quiz();
        $quizid     = $this->quizobj->get_quizid();
        $cmid       = $this->quizobj->get_cmid();
//         echo '<br><br><br> in prevent acc----------';
//         print_object($this);

        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
            echo '<br> in unfinished';
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
                    $PAGE->requires->js_init_call('client', array($roomid, json_encode($HBCFG)));
                }
            }
        }
    }

    public function end_time($attempt) {
        global $CFG, $PAGE, $_SESSION, $DB, $USER, $HBCFG;

//         echo '<br><br><br>-- In end time func --';
//         print_object($attempt);

        $this->check_node_server_status($attempt);

        if(isset($attempt->id)) {
            /*$deadtime = $this->get_deadtime($attempt);
            // if deadtime  > 30
            if($deadtime > 60) {
                // if attempt in progress
                echo '<br> attemptid ' . $attempt->id;
                $roomid = $this->construct_roomid($attempt->id);
                echo '<br> roomid ' . $roomid;
                $record = $this->get_livetable_data($roomid);
                if(!is_null($record)) {
                    $deadtime = (time() - $record->timetoconsider) + $record->deadtime;
                    $this->create_override_auto($attempt, $deadtime);

                    // Update livetable record.
                    $params = array(
                                'deadtime' => 0,
                                'extratime' => $record->extratime + $deadtime,
                                'roomid' => $roomid
                            );
                    $this->update_livetable_data($params);
                }
            } else {*/
//                 $roomid = $this->construct_roomid($attempt->id);
//                 echo '<br> roomid ' . $roomid;
//                 $record = $this->get_livetable_data($roomid);
//                 if (!is_null($record) && $record->status == "Dead") {
//                     $timenow = time();
//                     $deadtime = $timenow - $record->timetoconsider;

                    $deadtime = $this->get_deadtime($attempt);

                    if (!is_null($deadtime)) {
                        $roomid = $this->construct_roomid($attempt->id);
                        $record = $this->get_livetable_data($roomid);

                        if ($record->status == 'Dead') {
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
                    }
//                 }
//             }
            // if attempt completed
            //create override($attempt, $deadtime, $newattempttrue) but builds on the prev attempt
        }
        return $attempt->timestart + $this->quiz->timelimit;
    }

    protected function construct_roomid($attemptid) {
        global $USER;
        $username   = $USER->username;
        $quizid     = $this->quizobj->get_quizid();
        $roomid = $username . '_' . $quizid . '_' . $attemptid;

        return $roomid;
    }

    protected function check_node_server_status($attempt = null) {
        global $HBCFG;
        try {
            // Try connecting to the node server.
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $phpws_result = @socket_connect($socket, $HBCFG->host, $HBCFG->port);
        } catch (Exception $e) {
            echo '<br> in catch node down';
            if (!is_null($attempt)) {
                $roomid = $this->construct_roomid($attempt->id);
                $this->process_node_server_down($roomid);
            }
            throw new moodle_exception('servererr', 'quizaccess_heartbeatmonitor', $this->quizobj->view_url());
        }
    }

    protected function process_node_server_down($roomid) {
        $record = $this->get_livetable_data($roomid);   // worry later about handling debug if multiple records found

        if (!empty($record)){
            $currenttimestamp = intval(microtime(true));

            if ($record->status == 'Live') {
                // ttc = ndwn ==== llt of prev tsvr
                $tsrecord = $this->get_timeserver_data($record->timeserver);

                if (!empty($tsrecord)){
                    $livetimenow = ($tsrecord->lastlivetime - $record->timetoconsider) + $record->livetime;
                    $params = array(
                            'status' => "Dead",
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
                    FROM mdl_quizaccess_hbmon_timeserver
                    WHERE timeserverid = ' . $timeserver;
        $record = $DB->get_record_sql($sql);
        if (!is_null($record)) {
            return $record;
        } else {
            return null;
        }
    }

    protected function get_deadtime($attempt) {
        if (isset($attempt->id)) {
            $roomid = $this->construct_roomid($attempt->id);
            $record = $this->get_livetable_data($roomid);

            if (!is_null($record)) {
                if ($record->status == "Dead") {
                    $timenow = time();
                    $deadtime = $record->deadtime + ($timenow - $record->timetoconsider);
                    return $deadtime;
                } else {
                    return $record->deadtime;
                }
            }
        }
        return null;
    }

    protected function get_livetable_data($roomid) {
        global $DB;
        $sql = 'SELECT *
                    FROM {quizaccess_hbmon_livetable}
                    WHERE roomid = "' . $roomid . '"';
        $record = $DB->get_record_sql($sql);
        if (!empty($record)) {
            return $record;
        } else {
            return null;
        }
    }

    protected function update_livetable_data($params) { // Generalise this query to update all the values as provided.
        global $DB;
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
    }

    protected function create_override_auto($attempt, $deadtime) {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $userid     = $USER->id;
        $quiz       = $this->quiz;
        $cmid       = $this->quizobj->get_cmid();
        $context    = context_module::instance($cmid);

        // Setup the form data required for processing as in overrideedit.php file.
        $override = new stdClass();
        $override->cmid = $cmid;

        $timelimit = $quiz->timelimit + $deadtime;
        $override->timelimit = $timelimit;
        echo '<br> cuo ovrde timelimit ' . $deadtime;

        // Direct manipulation of quiz timelimit for immediately changing the quiz->timelimit.
        // Required mainly for display of 'Timelimit' on quiz/view.php page.
        // Or else, it takes a page refresh for changes to take effect.
        $quiz->timelimit = $timelimit;
        echo '<br> cuo quiz timelimit ' . $quiz->timelimit;

        // If timelimit is already modified (in end_time()).
        //         $override->timelimit = $quiz->timelimit;

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
                'userid' => empty($override->userid)? null : $override->userid);
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

//             quiz_delete_override($quiz, $oldoverride->id);
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
        echo '<br> tcs ' . $timecheckstate;
        $DB->set_field('quiz_attempts', 'timecheckstate', $timecheckstate, array('id' => $attempt->id));

        // User override. We only need to update the calendar event for this user override.
        quiz_update_events($quiz, $override);


    }

    function extracodechecks() {
        // If unfinished attempt.
        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
            echo '<br> in unfinished';
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

                    // If node server is down.
                    if(!$phpws_result) {
                        return get_string('servererr', 'quizaccess_heartbeatmonitor');

                    } else {
                        echo '<br> in else js init call';
                        $PAGE->requires->js_init_call('client', array($quizid, $userid, $username, $attemptid, $sessionkey, json_encode($HBCFG)));

                        //                         $hbmonmodesql = "SELECT hbmonmode
                        //                                             FROM {quizaccess_enable_hbmon}
                        //                                             WHERE quizid = $quizid";
                        //                         $hbmonmode = $DB->get_field_sql($hbmonmodesql);
                        //                         if ($hbmonmode) {
                        //                             echo '<br>-- In rule crtovrrde -- <br>-- qa state - ' . $quiza->state;
                        // If deadtime is there, then create override.

                        //                             $deadtime = $this->get_deadtime($roomid);
                        $sql = 'SELECT *
                                    FROM {quizaccess_hbmon_livetable}
                                    WHERE roomid = "' . $roomid . '"' .
                                    /*  AND status = "Live" */
                                    'AND deadtime > 60';
                        $record = $DB->get_record_sql($sql);
                        if (!empty($record)) {
                            $deadtime = $record->deadtime;
                            return $deadtime;
                        } else {
                            return null;
                        }
                        //                             if(!is_null($deadtime)) {

                        //                             if (!empty($records)){
                        //                                 foreach ($records as $record) {
                        //                                     if($roomid == $record->roomid){
                            //                                         echo '<br>-- In rule crtovrrd 2 --';
                            //                                     $this->create_override($roomid, $attempt, $deadtime);
                        //                                     }
                        //                                     break;
                        //                                 }
                        //                             }
                        //                         }
                    }
            }
        }
        return $attempt->timestart + $this->quiz->timelimit;
        } else {
            if(!$phpws_result) {
                // If new attempt.
                return get_string('servererr', 'quizaccess_heartbeatmonitor');
            }
        }
    }

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

//         if($attempt->state === 'finished') {
//             $clsobj->reset_timelimit_override($cmid, $roomid, $override, $quiz);
//         } else {
            $clsobj->create_user_override($attempt, $override);
//         }
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

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {

       $mform->addElement('header', 'hbmonheader', 'Heartbeat monitor');

        $hbmonsettingsarray   = array();

//         $hbmonsettingsarray[] = $mform->createElement('select', 'hbmonrequired',
//                 get_string('hbmonrequired', 'quizaccess_heartbeatmonitor'), array(
//                         0 => get_string('notrequired', 'quizaccess_heartbeatmonitor'),
//                         1 => get_string('hbmonrequiredoption', 'quizaccess_heartbeatmonitor')
//                 ));
        $mform->addElement('select', 'hbmonrequired',
                get_string('hbmonrequired', 'quizaccess_heartbeatmonitor'), array(
                        0 => get_string('notrequired', 'quizaccess_heartbeatmonitor'),
                        1 => get_string('hbmonrequiredoption', 'quizaccess_heartbeatmonitor')
                ));
        $mform->addHelpButton('hbmonrequired', 'hbmonrequired', 'quizaccess_heartbeatmonitor');

        $radioarray = array();
        $hbmonsettingsarray[]= $mform->createElement('radio', 'hbmonmode', '', get_string('automatic', 'quizaccess_heartbeatmonitor'), 1);
        $hbmonsettingsarray[]= $mform->createElement('radio', 'hbmonmode', '', get_string('manual', 'quizaccess_heartbeatmonitor'), 0);
//         $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
        $mform->setDefault('hbmonmode', 1);
//         $hbmonsettingsarray[] = $radioarray;
        $mform->addGroup($hbmonsettingsarray, 'enablehbmon', 'Mode', array(' '), false);
        $mform->disabledIf('hbmonmode', 'hbmonrequired', 'neq', 1);

//         $hbmonsettingsarray[] = $mform->createElement('text', 'nodehost', "Host", 'maxlength="25" size="15" ');
        $mform->addElement('text', 'nodehost', "Node host", 'maxlength="25" size="15" ');
        $mform->setType('nodehost', PARAM_HOST);
        //         $mform->addRule('', get_string('missing'), 'required', null, 'server');
        $mform->setDefault('nodehost', 'localhost');
        $mform->disabledIf('nodehost', 'hbmonrequired', 'neq', 1);

//         $hbmonsettingsarray[] = $mform->createElement('text', 'nodeport', 'Port', 'maxlength="4" size="4" ');
        $mform->addElement('text', 'nodeport', 'Node port', 'maxlength="4" size="4" ');
        $mform->setType('nodeport', PARAM_NUMBER);
        $mform->setDefault('nodeport', '3000');
        $mform->disabledIf('nodeport', 'hbmonrequired', 'neq', 1);

//         $mform->addGroup($hbmonsettingsarray, 'enablehbmon', get_string('hbmonrequired', 'quizaccess_heartbeatmonitor'), array(' '), false);
//         $mform->setAdvanced('enablehbmon', true);


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

                $record1 = new stdClass();
                $record1->nodehost = $quiz->nodehost;
                $record1->nodeport = $quiz->nodeport;
                $DB->insert_record('quizaccess_hbmon_node', $record1);
            } else {
                $select = "quizid = $quiz->id";
                $id = $DB->get_field_select('quizaccess_enable_hbmon', 'id', $select);
                $record = new stdClass();
                $record->id = $id;
                $record->hbmonmode = $quiz->hbmonmode;
                $DB->update_record('quizaccess_enable_hbmon', $record);

                $record1 = new stdClass();
                $record1->nodehost = $quiz->nodehost;
                $record1->nodeport = $quiz->nodeport;
                if (!$DB->record_exists('quizaccess_hbmon_node')) {
                    $DB->insert_record('quizaccess_hbmon_node', $record1);
                } else {
                    $DB->update_record('quizaccess_hbmon_node', $record1);
                }
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
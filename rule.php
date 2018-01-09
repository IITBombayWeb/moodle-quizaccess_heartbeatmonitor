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
        $sessionkey = sesskey();
        $userid     = $_SESSION['USER']->id;
        $username   = $_SESSION['USER']->username;
        $quizid     = $this->quizobj->get_quizid();

//     	$url = 'http://127.0.0.1:3000/';
//     	$ch = curl_init($url);
//     	curl_setopt($ch, CURLOPT_NOBODY, true);
//     	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//     	curl_exec($ch);
//     	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     	curl_close($ch);
//     	if (200 == $retcode) {
        $qa             = $DB->get_record('quiz_attempts', array('id'=>$attemptid));

        // Error..since socket gets connected while reviewing the quiz.. but qa->state is finished..so conflict
                if($qa->state != 'finished') {
    	    $PAGE->requires->js_init_call('client', array($quizid, $userid, $username, $attemptid, $sessionkey));
                }
//     	}
    }
}


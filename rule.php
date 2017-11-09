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
 * @package    quizaccess_heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017, Indian Institute Of Technology, Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

class quizaccess_heartbeat extends quiz_access_rule_base {


    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        // This rule is always used, even if the quiz has no open or close date.
        return new self($quizobj, $timenow);
    }

//     public function time_left_display($attempt, $timenow) {
//     public function notify_preflight_check_passed($attemptid) {

//         $id = optional_param ( 'id', 0, PARAM_INT );
//         $cm = get_coursemodule_from_id ('quiz', $id);
//     }

    public function setup_attempt_page($page) {
        global $CFG, $PAGE, $SESSION;

//         echo "<script src='http://127.0.0.1:3000/socket.io/socket.io.js'></script>";
        $PAGE->requires->js( new moodle_url('http://127.0.0.1:3000/socket.io/socket.io.js'), true );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/quiz/accessrule/heartbeat/client.js') );

//----------------------echo javascript - works! --------------------------------------------------
//             echo "<script type='text/javascript'>
//                     alert('before');
//                     var socket = io('http://127.0.0.1:3000');
//                     alert('now');

//                     alert('after'+socket.id);
//                     </script>";

//-----------------------------------php websockets - works! ----------------------------------------
//             error_reporting(E_ALL);

//             $port = 3000; // Port the node app listens to
//             $address = '127.0.0.1'; // IP the node app is on

//             // Create socket
//             $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//             if ($socket === false) {
//                 echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
//             }

//             // Connect to node app
//             $result = socket_connect($socket, $address, $port);
//             if ($result === false) {
//                 echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
//             }

//             // Data we want to send
//             $data = array('itemid' => '1234567', 'steamid' => '769591951959', 'otherinfo' => 'hi there');

//             // Prepares to transmit it
//             $encdata = json_encode($data);

//             socket_write($socket, $encdata, strlen($encdata));

//             socket_close($socket);
//             echo 'Sent data\n';
//---------------------------------------------------------------------------------------
    }
}

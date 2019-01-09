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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/formslib.php');

/**
 * Time limit override form.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class startnode_form extends moodleform {

    /** @var object course module object. */
    protected $cm;

    /** @var object the quiz settings object. */
    protected $quiz;

    /** @var context the quiz context. */
    protected $course;

    /**
     * Constructor.
     * @param moodle_url $submiturl the form action URL.
     * @param object course module object.
     * @param object the quiz settings object.
     * @param context the quiz context.
     */
    public function __construct($submiturl, $quiz, $course, $cm) {

        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->course = $course;

        parent::__construct($submiturl, null, 'post');
    }

    /**
     * Form definition method.
     */
    function definition() {
        global $CFG, $DB;

        $cmid   = $this->cm->id;
        $quizid = $this->quiz->id;
        $mform  = $this->_form;

        // Post data for overrideedit.php.
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
        $courseid = $course->id;
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

        $url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));


        // Setup the form.
//         $indexurl           = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php');

        // Display table.
        $mform->addElement('header', 'starttimeserver', 'Heartbeat time server');
        $mform->setExpanded('starttimeserver', true);

        $flag = 0;
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $phpws_result = @socket_connect($socket, '127.0.0.1', 3000);

        //echo '<br><br><br>-- ws res in strtnd frm --';
        //print_object($phpws_result);

        if(!$phpws_result) {
//             exec("node /var/ww/html/moodle/mod/quiz/accessrule/heartbeatmonitor/server.js 2>&1", $output);
//             die();
            //echo 'start';
            $mform->addElement('static', 'description', '', 'Start the \'Time server\' by clicking \'Start\' button.');

            // Start button.
            $mform->addElement('submit', 'submitbutton', 'Start');
        } else {
            //echo 'stop';
            $mform->addElement('static', 'description', '', 'Heartbeat time server is running. <br>Stop the time server by clicking on the \'Stop\' button.');

            // Stop button.
            $mform->addElement('submit', 'submitbutton', 'Stop');
        }
    }
}

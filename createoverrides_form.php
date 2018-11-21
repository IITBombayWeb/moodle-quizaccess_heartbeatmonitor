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
class createoverrides_form extends moodleform {

    /** @var object course module object. */
    protected $cm;

    /** @var object the quiz settings object. */
    protected $quiz;

    /** @var context the quiz context. */
    protected $context;

    /**
     * Constructor.
     * @param moodle_url $submiturl the form action URL.
     * @param object course module object.
     * @param object the quiz settings object.
     * @param context the quiz context.
     */
    public function __construct($submiturl, $cm, $quiz, $context) {

        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->context = $context;

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

        // Display live users.
        // Fetch records from database.
        $sql = 'SELECT *
                    FROM {quizaccess_hbmon_livetable}
                    WHERE status = "Live"
                      AND deadtime <> 0';
        $result     = $DB->get_records_sql($sql);
        $deadtime   = null;
        $userid     = null;
        $arr_users  = array();

        if (!empty($result)) {
            // Output data of each row.
            foreach ($result as $record) {
                $roomid         = $record->roomid;
                $arr            = explode("_", $roomid);
                $attemptid      = array_splice($arr, -1)[0];
                $quizid         = array_splice($arr, -1)[0];
                $username       = implode("_", $arr);
                $user           = $DB->get_record('user', array('username'=>$username));
                $userid         = $user->id;

                if($quizid == $quiz->id) {
                    $status           = $record->status;
                    $timetoconsider   = $record->timetoconsider;
                    $livetime         = $record->livetime;
                    $deadtime         = $record->deadtime;
                    $arr_users[$roomid] = $user->firstname .  ' ' . $user->lastname;
                }
            }
        }

        // Setup the form.
        $timelimit          = $quiz->timelimit + intval($deadtime / 1000);
        $processoverrideurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
        $indexurl           = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php');

        // Display table.
        $mform->addElement('header', 'createoverrides', 'Create user override');
        $mform->setExpanded('createoverrides', false);

        if($arr_users) {
            $attributes = null;
            $select = $mform->addElement('select', 'users', get_string('selectusers', 'quizaccess_heartbeatmonitor'), $arr_users, $attributes);
            // $mform->setDefault('users', 'No User');
            reset($arr_users);
            // $mform->getElement('users')->setSelected(array(key($arr_users)));
            $select->setMultiple(true);
            $mform->addElement('static', 'description', '', get_string('note1', 'quizaccess_heartbeatmonitor'));

            // Submit button.
            $mform->addElement('submit', 'submitbutton', get_string('createoverride', 'quizaccess_heartbeatmonitor'));
        } else {
            $mform->addElement('static', 'description', '', get_string('note2', 'quizaccess_heartbeatmonitor'));
        }
    }
}
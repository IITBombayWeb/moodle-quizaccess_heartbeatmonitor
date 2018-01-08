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
class timelimit_override_form1 extends moodleform {

    /** @var object course module object. */
    protected $cm;

    /** @var object the quiz settings object. */
    protected $quiz;

    /** @var context the quiz context. */
    protected $context;

    /** @var int userid, if provided. */
    protected $userid;

    /** @var int users array, if provided. */
    protected $users = array();

    /** @var int timelimit, if provided. */
    protected $timelimit;

    /**
     * Constructor.
     * @param moodle_url $submiturl the form action URL.
     * @param object course module object.
     * @param object the quiz settings object.
     * @param context the quiz context.
     */
    public function __construct($submiturl, $cm, $quiz, $context, $users, $timelimit) {

        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->context = $context;
//         $this->userid = empty($userid) ? 0 : $userid;
        $this->users = empty($users) ? null : $users;

        $this->timelimit = $timelimit;
//         $this->a = $a;

        parent::__construct($submiturl, null, 'post');

    }

    /**
     * Form definition method.
     */
    function definition() {
        global $CFG;

        $cm = $this->cm;
        $mform = $this->_form;

        // Post data for overrideedit.php.
        // Action.
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);
        $mform->setDefault('action', 'adduser');

        // Course module ID.
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $cm->id);

        // Quiz.
        $mform->addElement('hidden', 'quiz');
        $mform->setType('quiz', PARAM_INT);
        $mform->setDefault('quiz', $this->quiz->id);

        $mform->addElement('hidden', '_qf__quiz_override_form');
        $mform->setType('_qf__quiz_override_form', PARAM_INT);
        $mform->setDefault('_qf__quiz_override_form', 1);

        $mform->addElement('hidden', 'mform_isexpanded_id_override');
        $mform->setType('mform_isexpanded_id_override', PARAM_INT);
        $mform->setDefault('mform_isexpanded_id_override', 1);

//         // User ID.
//         $mform->addElement('hidden', 'userid');
//         $mform->setType('userid', PARAM_INT);
//         $mform->setDefault('userid', $this->userid);

        // User IDs.
//         $mform->addElement('hidden', 'userid');
//         $mform->setType('userid', PARAM_TEXT);
//         $mform->setDefault('userid', $this->userid);

        // Users.
        $mform->addElement('hidden', 'users');
        $mform->setType('users', PARAM_TEXT);
        $mform->setDefault('users', $this->users);

        // Password.
        $mform->addElement('hidden', 'password');
        $mform->setType('password', PARAM_TEXT);
        $mform->setDefault('password', $this->quiz->password);

        // Open and close dates.
        $mform->addElement('hidden', 'timeopen');
        $mform->setType('timeopen', PARAM_INT);
        $mform->setDefault('timeopen', $this->quiz->timeopen);
//         $mform->setDefault('timeopen', 1505125800);

        $mform->addElement('hidden', 'timeclose');
        $mform->setType('timeclose', PARAM_INT);
        $mform->setDefault('timeclose', $this->quiz->timeclose);
//         $mform->setDefault('timeclose', 1512209160);

        // Time limit.
        $mform->addElement('hidden', 'timelimit');
        $mform->setType('timelimit', PARAM_INT);
        $mform->setDefault('timelimit', $this->timelimit);
//         $mform->setDefault('timelimit', 9000);

        // Number of attempts.
        $mform->addElement('hidden', 'attempts');
        $mform->setType('attempts', PARAM_INT);
        $mform->setDefault('attempts', $this->quiz->attempts);

        // Submit button.
//         $mform->addElement('submit', 'submitbutton', get_string('save', 'quiz'));
        $this->add_action_buttons($cancel = true, get_string('save', 'quiz'));

        //normally you use add_action_buttons instead of this code
//         $buttonarray=array();
//         $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('save', 'quiz'));
//         $buttonarray[] = $mform->createElement('reset', 'resetbutton', get_string('revert'));
//         $buttonarray[] = $mform->createElement('cancel');
//         $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }
}
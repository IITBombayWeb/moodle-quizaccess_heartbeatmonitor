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
class new_form extends moodleform {


    /** @var object course module object. */
    protected $cm;

    /** @var object the quiz settings object. */
    protected $quiz;

    /** @var context the quiz context. */
    protected $context;

//     /** @var int userid, if provided. */
//     protected $userid;

//     /** @var int timelimit, if provided. */
//     protected $timelimit;

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
//         $this->userid = empty($userid) ? 0 : $userid;
//         $this->timelimit = $timelimit;
//         $this->a = $a;

        parent::__construct($submiturl, null, 'post');

    }

    /**
     * Form definition method.
     */
    function definition() {
        global $CFG, $DB;

        $cmid = $this->cm->id;
        $quizid = $this->quiz->id;

        $mform = $this->_form;

        // Post data for overrideedit.php.

        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
        $courseid = $course->id;
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

        $url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));

        // Display live users.
        // Fetch records from database.
//         $servername = "localhost";
//         $dbusername = "root";
//         $dbpassword = "root123";
//         $dbname     = "trialdb";

//         $servername = $CFG->dbhost;
//         $dbusername = $CFG->dbuser;
//         $dbpassword = $CFG->dbpass;
//         $dbname     = $CFG->dbname;

//         // Create connection
//         $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

//         // Check connection
//         if ($conn->connect_error) {
//             die("Connection failed: " . $conn->connect_error);
//         }
        // echo "Connected successfully";

//         $sql = 'SELECT * FROM livetable1';  // Select data for a particular quiz and not entire table..insert quizid col in livetable1 for this.
//         $result = $conn->query($sql);
//         $arr = array();
//         $roomid = null;

//         $table = new html_table();
//         $table->id = 'liveusers';
//         $table->caption = 'User Details';
//         $table->head = array('', 'Socket room id', 'Current status', 'Status update on', 'Live time', 'Dead time');

// //         $mform->addElement('static', 'description', '', 'Select users');

//         if ($result->num_rows > 0) {
//             // Output data of each row.

//             while($data = $result->fetch_assoc()) {
//                 $roomid         = $data["roomid"];
//                 $arr            = explode("_", $roomid);
//                 $attemptid      = array_splice($arr, -1)[0];
//                 $quizid1        = array_splice($arr, -1)[0];
//                 $username       = implode("_", $arr);
//                 //         echo 'un ' . $username;
//                 $user           = $DB->get_record('user', array('username'=>$username));
//                 //         print_object($user);
//                 if($user) {
//                     $userid         = $user->id;
//                 }

//                 if($quizid1 == $quizid) {
//                     $status          = $data["status"];
//                     $timetoconsider  = $data["timetoconsider"];
//                     $livetime        = $data["livetime"];
//                     $deadtime        = $data["deadtime"];

//                     $currentTimestamp = intval(microtime(true)*1000);

//                     if ($status == 'Live') {
//                         $livetime = ($currentTimestamp - $timetoconsider) + $livetime;
//                     } else {
//                         $deadtime = ($currentTimestamp - $timetoconsider) + $deadtime;
//                     }

//                     $seconds = intval($livetime / 1000);
//                     $dtF = new DateTime('@0');
//                     $dtT = new DateTime("@$seconds");
//                     $lt =  $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');

//                     $seconds1 = intval($deadtime / 1000);
//                     $dtF1 = new DateTime('@0');
//                     $dtT1 = new DateTime("@$seconds1");
//                     $dt =  $dtF1->diff($dtT1)->format('%a d, %h h : %i m : %s s');

//                     $humanisedlivetime = $lt;
//                     $humaniseddeadtime = $dt;

//                     $table->rowclasses['roomid'] = $roomid;
//                     $row = new html_table_row();
//                     $row->id = $roomid;
//                     $row->attributes['class'] = $roomid;

//                     $value = $roomid . '_' . $deadtime;
//                     $cell0 = new html_table_cell();
// //                     $cell0 = new html_table_cell(
// //                             html_writer::empty_tag('input', array('type'  => 'checkbox',
// //                                     'name'  => 'setoverride',
// //                                     'value' => $value,
// //                                     'class' => 'setoverride')));
// //                     $cell0 = new html_table_cell(
// //                             $mform->addElement('advcheckbox', $user->id, '', $username, array('group' => 1), array(0, 1)));
// //                     $cell0 = $mform->addElement('advcheckbox', $user->id, '', $username, array('group' => 1), array(0, 1));
//                     $cell0 = new html_table_cell($user->firstname .  ' ' . $user->lastname);
//                     $cell0->id = 'name';

//                     $cell1 = new html_table_cell($roomid);
//                     $cell1->id = 'roomid';

//                     $cell2 = new html_table_cell($status);
//                     $cell2->id = 'status';

//                     $cell3 = new html_table_cell(userdate(intval($timetoconsider / 1000)));
//                     $cell3->id = 'timetoconsider';

//                     $cell4 = new html_table_cell($humanisedlivetime);
//                     $cell4->id = 'livetime';
//                     $cell4->attributes['value'] = $livetime;

//                     $cell5 = new html_table_cell($humaniseddeadtime);
//                     $cell5->id = 'deadtime';
//                     $cell5->attributes['value'] = $deadtime;

//                     $row->cells[] = $cell0;
//                     $row->cells[] = $cell1;
//                     $row->cells[] = $cell2;
//                     $row->cells[] = $cell3;
//                     $row->cells[] = $cell4;
//                     $row->cells[] = $cell5;

//                     $table->data[] = $row;
// //                     $mform->addElement('advcheckbox', $user->id, '', $username, array('group' => 1), array(0, 1));
//                 }
//             }
//         }

        $sql1 = 'SELECT * FROM {quizaccess_hbmon_livetable1} WHERE status = "Live" AND deadtime <> 0';
//         $result1 = $conn->query($sql1);
        $result1    = $DB->get_records_sql($sql1);
        $deadtime1  = null;
        $userid1    = null;
        $arr_users  = array();

        if (!empty($result1)) {
//             print_object($result1);
//         if ($result1->num_rows > 0) {
            // Output data of each row.
            foreach ($result1 as $record) {
//             while($data = $result1->fetch_assoc()) {
                $roomid1        = $record->roomid;

                $arr            = explode("_", $roomid1);
                $attemptid      = array_splice($arr, -1)[0];
                $quizid1        = array_splice($arr, -1)[0];
                $username       = implode("_", $arr);

                $user           = $DB->get_record('user', array('username'=>$username));
                $userid1        = $user->id;

                if($quizid1 == $quiz->id) {
                    $status1          = $record->status;
                    $timetoconsider1  = $record->timetoconsider;
                    $livetime1        = $record->livetime;
                    $deadtime1        = $record->deadtime;
                    //             echo $roomid1 . ' ' . $livetime1;
                    $arr_users[$roomid1] = $user->firstname .  ' ' . $user->lastname;
                }
            }
        }

        // Setup the form.
        $timelimit = $quiz->timelimit + intval($deadtime1 / 1000);
        //     $overrideediturl = new moodle_url('/mod/quiz/overrideedit.php');
        $processoverrideurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
        $indexurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php');
        $intermediaryurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/intermediary.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));

        // Display table.
//         $mform->addElement('static', 'description', '', 'Select users');
//         echo html_writer::table($table);
//         echo '<br>';

//         $mform->addElement('static', 'description', '', '');

        $mform->addElement('header', 'createoverrides', 'Create user override');
        $mform->setExpanded('createoverrides', false);

        if($arr_users) {
            $attributes = null;
            $select = $mform->addElement('select', 'users', '<b>Select users for creating user overrides </b><br>', $arr_users, $attributes);
//             $mform->setDefault('users', 'No User');
            reset($arr_users);
//             $mform->getElement('users')->setSelected(array(key($arr_users)));
            $select->setMultiple(true);
            $mform->addElement('static', 'description', '', '(Note: List contains users who are online and have a non-zero "Quiz time lost" value only.)');

            // Submit button.
//             $mform->addElement('hidden', 'submitbutton', get_string('save', 'quiz'));

//             $mform->addElement('hidden', 'submitbutton');
//             $mform->setType('submitbutton', PARAM_RAW);
//             $mform->setDefault('submitbutton', get_string('save', 'quiz'));

            $mform->addElement('submit', 'submitbutton', 'Create override');
        } else {
            $mform->addElement('static', 'description', '', '(Note: No user meets minimum conditions required for creating a user override.)');
        }

    }

    function secondsToTime($seconds) {
        $dtF = new DateTime('@0');
        $dtT = new DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a d, %h h : %i m : %s s');
    }
}
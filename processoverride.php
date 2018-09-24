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
 * This page handles editing and creation of quiz overrides.
 *
 * @package   mod_quiz
 * @copyright 2010 Matt Petro
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/override_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimitoverride_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/heartbeatmonitor/timelimitoverride.php');

$cmid       = optional_param('cmid', 0, PARAM_INT);
$overrideid = optional_param('id', 0, PARAM_INT);
$action     = optional_param('action', null, PARAM_ALPHA);
$reset      = optional_param('reset', false, PARAM_BOOL);
$override   = null;

if ($cmid) {
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
    $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('invalidcoursemodule');
}
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

$url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/processoverride.php');
if ($action) {
    $url->param('action', $action);
}

$url->param('cmid', $cmid);

$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

// Add or edit an override.
require_capability('mod/quiz:manageoverrides', $context);

// Creating a new override.
$data = new stdClass();

// Merge quiz defaults with data.
$keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
foreach ($keys as $key) {
    if (!isset($data->{$key}) || $reset) {
        $data->{$key} = $quiz->{$key};
    }
}

$overridelisturl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/showoverrides.php', array('cmid'=>$cm->id));
$overridelisturl->param('mode', 'user');

// Setup the form.
$users = array();
$mform = new timelimitoverride_form($url, $cm, $quiz, $context, $users, $override);
$mform->set_data($data);

$indexurl = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php', array('quizid'=>$quiz->id, 'courseid'=>$course->id, 'cmid'=>$cmid));

if($mform->is_cancelled()) {
    redirect($indexurl);
}
if ($fromform = $mform->get_data()) {
    $roomids = array();
    $roomids = explode(" ", $fromform->users);
    foreach ($roomids as $roomid) {
        $arr = array();
        $arr = explode("_", $roomid);
        $attemptid = array_splice($arr, -1)[0];
        $room_quizid = array_splice($arr, -1)[0];
        $username = implode("_", $arr);
        $uid = $DB->get_field('user', 'id', array('username' => $username));

        $quizobj = quiz::create($cm->instance, $uid);
        $quiz1 = $quizobj->get_quiz();

        $dataobj = new timelimitoverride();
        $dataobj->create_timelimit_override($cmid, $roomid, $fromform, $quiz1);
    }
    if (!empty($fromform->submitbutton)) {
        redirect($overridelisturl);
    }
}

// Print the form.
$pagetitle = get_string('editoverride', 'quiz');
$PAGE->navbar->add($pagetitle);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));

$mform->display();

echo $OUTPUT->footer();
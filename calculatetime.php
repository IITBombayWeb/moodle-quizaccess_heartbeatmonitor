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
 * This page handles listing of quiz overrides
 *
 * @package    mod_quiz
 * @copyright  2010 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
// require_once($CFG->dirroot.'/mod/quiz/lib.php');
// require_once($CFG->dirroot.'/mod/quiz/locallib.php');
// require_once($CFG->dirroot.'/mod/quiz/override_form.php');


$quizid = required_param('quizid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
// $mode = optional_param('mode', '', PARAM_ALPHA); // One of 'user' or 'group', default is 'group'.

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
$quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

// Default mode is "user".
$mode = "user";

$url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/calculatetime.php', array('quizid'=>$quizid, 'courseid'=>$courseid, 'cmid'=>$cmid));

$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

// Check the user has the required capabilities to list overrides.
require_capability('mod/quiz:manage', $context);

// Display a list of overrides.
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('calculatetime', 'quizaccess_heartbeatmonitor'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));
// echo '<br><br><br>hi';

// Fetch records from database.
// mysql_connect('localhost','root','root123');
// mysql_select_db("trialdb");

$servername = "localhost";
$username = "root";
$password = "root123";
$dbname = "trialdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";

$sql = 'SELECT * FROM livetable1';
// $params = array('quizid' => $quiz->id);
$result = $conn->query($sql);

$table = new html_table();
$table->head = array('Roomid','Status', 'Time to consider' , 'Live time', 'Dead time');
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
//         echo "id: " . $row["roomid"]. " status: " . $row["status"]. " timetoconsider: " . $row["timetoconsider"]. "<br>";
        $table->data[] = array($row["roomid"], $row["status"], $row["timetoconsider"], $row["livetime"], $row["deadtime"]);
    }
} else {
    echo "0 results";
}
echo html_writer::table($table);

$conn->close();
// $result = mysql_query($sql);
// print_object($result);

// Finish the page.
echo $OUTPUT->footer();
?>

<script>
function humanise(difference) {
    var days    = Math.floor(difference / (1000 * 60 * 60 * 24));
    var hours   = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((difference % (1000 * 60)) / 1000);
    var time    = days+' days, '+hours+' hrs, '+minutes+' mins, '+seconds+' secs' ;
    return time;
}

setInterval(function() {
    $.get("/calculatetime", function(data) {
        alert('hhi ' + data);

                    console.log(livestatusinfo);

        for(i in livestatusinfo){
            var roomid          = livestatusinfo[i].roomid;
            var status          = livestatusinfo[i].status;
            var timetoconsider  = livestatusinfo[i].timetoconsider;
            var livetime        = livestatusinfo[i].livetime;
            var deadtime        = livestatusinfo[i].deadtime;

            var currentTimestamp    = new Date().getTime();
            //                 var a = parseInt(currentTimestamp - timetoconsider) + parseInt(livetime);
            //                 alert(humanise(currentTimestamp - timetoconsider) + '  ' + humanise(livetime) + '  ' + humanise(a));

            if (status == 'Live') {
                livetime = parseInt(currentTimestamp - timetoconsider) + parseInt(livetime);
            } else {
                deadtime = parseInt(currentTimestamp - timetoconsider) + parseInt(deadtime);
            }

            var humanisedlivetime = humanise(livetime);
            var humaniseddeadtime = humanise(deadtime);

            if($('#'+roomid).length > 0) {
                //remove prev. row and add updated row
                $('#'+roomid).remove();

                var  row = '<tr id = ' + roomid + '><td>'
                        + roomid + '</td><td>'
                        + status + '</td><td>'
                        + timetoconsider + '</td><td>'
                        + humanisedlivetime + '</td><td>'
                        + humaniseddeadtime + '</td>'+
                        '<td class="livesockets"></td></tr>';

                        $("#livestatus").append(row);
                        for(j in livestatusinfo[i].roomwisesockets){
                            $('td.livesockets').append(livestatusinfo[i].roomwisesockets[j] + '<br>');
                        }

            } else {
                //new row addition
                var  row = '<tr id = ' + roomid + '><td>'
                        + roomid + '</td><td>'
                        + status + '</td><td>'
                        + timetoconsider + '</td><td>'
                        + humanisedlivetime + '</td><td>'
                        + humaniseddeadtime + '</td>'+
                        '<td class="livesockets"></td></tr>';

                        $("#livestatus").append(row);
                        for(j in livestatusinfo[i].roomwisesockets){
                            $('td.livesockets').append(livestatusinfo[i].roomwisesockets[j] + '<br>');
                        }
            }

            $("#allsoccount").text(livestatusinfo[i].allsocketscount);
            if($('#allsockets').length > 0) {
                //remove prev. row and add updated row
                $('#allsockets').remove();

                $("#connectedsockets").append('<tr id="allsockets"><td id="here"></td></tr>');
                for(j in livestatusinfo[i].allsockets){
                    $("#here").append(livestatusinfo[i].allsockets[j] + '<br>');
                }

            } else {
                $("#connectedsockets").append('<tr id="allsockets"><td id="here"></td></tr>');
                for(j in livestatusinfo[i].allsockets){
                    $("#here").append(livestatusinfo[i].allsockets[j] + '<br>');
                }
            }

        }
    });
}, 1000);
</script>



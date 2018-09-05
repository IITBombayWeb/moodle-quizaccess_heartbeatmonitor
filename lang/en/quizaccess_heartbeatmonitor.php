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
 * Strings for the quizaccess_heartbeatmonitor plugin.

 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'Hearbeat monitor quiz access rule';
$string['calculatetime'] = 'Calculate time';
$string['liveusers'] = 'Users status';
$string['nodatafound'] = 'No data found.';
$string['heading'] = '{$a} | Heartbeat monitor ';
$string['honestycheckheader'] = 'Please read the following message';
$string['honestychecklabel'] = 'I have read and agree to the above statement.';
$string['honestycheckrequired'] = 'Hearbeat monitoring';
$string['honestycheckrequired_help'] = 'If you enable this option, quiz connections and disconnections will be monitored through the plugin.';
$string['honestycheckrequiredoption'] = 'required';
$string['honestycheckstatement'] = 'I understand that it is important that the attempt I am about to make is all my own work. I understand what constitutes plagiarism or cheating, and I will not undertake such activities.';
$string['notrequired'] = 'not required';
// $string['pluginname'] = 'Acknowledge plagiarism statement access rule';
$string['youmustagree'] = 'You must agree to this statement before you start the quiz.';

$string['notrequired'] = 'No';
$string['hbmonrequired'] = 'Enable heartbeat monitoring';
$string['hbmonrequired_help'] = 'Heartbeat monitoring system is used to track the online status
                                    of the users attempting the quiz, keep record of time lost due to
                                    network disconnections and accordingly, auto-increment the quiz timelimit.
                                    If you enable this option, users will be automatically granted extra time
                                    depending upon the amount of time they have lost.';
$string['hbmonrequiredoption'] = 'Yes';
$string['automatic'] = 'Automatic';
$string['manual'] = 'Manual';
$string['servererr'] = '<font color="red">Heartbeat time server is not on. Please contact your instructor.</font>';
$string['usersattemptingquiz'] = 'Users attempting quiz';
$string['user'] = 'User';
$string['socketroomid'] = 'IP address';
$string['currentstatus'] = 'Current status';
$string['statusupdate'] = 'Last live time';
$string['timeutilized'] = 'Quiz time used up';
$string['timelost'] = 'Quiz time lost';
$string['online'] = '<font color="green"><i>Online</i></font>';
$string['offline'] = '<font color="red"><i>Offline</i></font>';
$string['youhaveselected'] = '<br><h4>Selected users</h4><br><br>';
$string['h4open'] = '<h4>';
$string['h4close'] = '</h4>';
$string['br'] = '<br>';



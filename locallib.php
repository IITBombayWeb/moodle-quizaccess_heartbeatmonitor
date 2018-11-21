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
 * Library of functions used by the quizaccess_useripmapping plugin.
 *
 * @package    quizaccess_useripmapping
 * @author     Amrata Ramchandani <ramchandani.amrata@gmail.com>
 * @copyright  2017 Indian Institute Of Technology,Bombay,India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * Validation callback function - verifies the column line of csv file.
 * Converts standard column names to lowercase.
 *
 */

/**
 * This function extends the settings navigation block for the site.
 * It is being called from quiz_extend_settings_navigation function.
 */
function heartbeatmonitor_accessrule_extend_navigation($accessrulenode, $cm) {
    $url = new moodle_url('/mod/quiz/accessrule/heartbeatmonitor/index.php'
            , array(
            'quizid' => $cm->instance,
            'courseid' => $cm->course,
            'cmid' => $cm->id
    ));
    $node = navigation_node::create(get_string('pluginname', 'quizaccess_heartbeatmonitor'), $url,
            navigation_node::TYPE_SETTING, null, 'quiz_accessrule_heartbeatmonitor', new pix_icon('i/item', ''));
    $accessrulenode->add_node($node);
}
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
 * Upgrade script for the quiz module.
 *
 * @package    mod_quiz
 * @copyright  2006 Eloy Lafuente (stronk7)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz module upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_quizaccess_heartbeatmonitor_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017110209) {

        // Define table quizaccess_enable_hbmonto be created.
        $table = new xmldb_table('quizaccess_enable_hbmon');

        // Adding fields to table quizaccess_enable_hbmon.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hbmonrequired', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hbmonmode', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table quizaccess_enable_hbmon.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('quizid', XMLDB_KEY_FOREIGN, array('quizid'), 'quiz', array('id'));

        // Adding indexes to table quizaccess_enable_hbmon.
//         $table->add_index('quizid-firstslot', XMLDB_INDEX_UNIQUE, array('quizid', 'firstslot'));

        // Conditionally launch create table for quizaccess_enable_hbmon.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        //--------------------------------------------------------------------------------------------

        // Define table quizaccess_hbmon_timeserverto be created.
        $table1 = new xmldb_table('quizaccess_hbmon_timeserver');

        // Adding fields to table quizaccess_hbmon_timeserver.
        $table1->add_field('timeserverid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table1->add_field('lastlivetime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table quizaccess_hbmon_timeserver.
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('timeserverid'));

        // Adding indexes to table quizaccess_hbmon_timeserver.
        //         $table->add_index('quizid-firstslot', XMLDB_INDEX_UNIQUE, array('quizid', 'firstslot'));

        // Conditionally launch create table for quizaccess_hbmon_timeserver.
        if (!$dbman->table_exists($table1)) {
            $dbman->create_table($table1);
        }

        //---------------------------------------------------------------------------------------------

        // Define table quizaccess_hbmon_socketinfo1 to be created.
        $table2 = new xmldb_table('quizaccess_hbmon_socketinfo1');

        // Adding fields to table quizaccess_hbmon_timeserver.
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('roomid', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null);
        $table2->add_field('socketid', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null);
        $table2->add_field('socketstatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null);
        $table2->add_field('ip', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null);
        $table2->add_field('timestamp', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table quizaccess_hbmon_timeserver.
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table quizaccess_hbmon_timeserver.
        //         $table->add_index('quizid-firstslot', XMLDB_INDEX_UNIQUE, array('quizid', 'firstslot'));

        // Conditionally launch create table for quizaccess_hbmon_timeserver.
        if (!$dbman->table_exists($table2)) {
            $dbman->create_table($table2);
        }

        //---------------------------------------------------------------------------------------------
        // Heartbeatmonitor savepoint reached.
        upgrade_plugin_savepoint(true, 2017110209, 'quizaccess', 'heartbeatmonitor');
    }

}
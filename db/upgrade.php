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

    if ($oldversion < 2017110220) {

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

        // Define table quizaccess_enable_hbmonto be created.
        $table3 = new xmldb_table('quizaccess_enable_hbmon');
        $field1 = new xmldb_field('nodehost', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, 'localhost');
        $field2 = new xmldb_field('nodeport', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '3000');

        // Conditionally launch create table for quizaccess_enable_hbmon.
        if (!$dbman->table_exists($table3)) {
            $dbman->create_table($table3);
        }

        // Adding fields to table quizaccess_enable_hbmon.
        if ($dbman->field_exists($table3, $field1)) {
            $dbman->drop_field($table3, $field1);
        }
        if ($dbman->field_exists($table3, $field2)) {
            $dbman->drop_field($table3, $field2);
        }

        //---------------------------------------------------------------------------------------------

        // Define table quizaccess_hbmon_node to be created.
        $table4 = new xmldb_table('quizaccess_hbmon_node');

        // Adding fields to table quizaccess_hbmon_node.
        $table4->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table4->add_field('nodehost', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, 'localhost');
        $table4->add_field('nodeport', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '3000');

        // Adding keys to table quizaccess_hbmon_node.
        $table4->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for quizaccess_hbmon_node.
        if (!$dbman->table_exists($table4)) {
            $dbman->create_table($table4);
        }

        //---------------------------------------------------------------------------------------------

        // Define table quizaccess_hbmon_node to be created.
        echo '<br><br><br> in find key name before';
        $table5 = new xmldb_table('quizaccess_hbmon_node');

        // Adding fields to table quizaccess_hbmon_node.
        $field1 = new xmldb_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $key2 = new xmldb_key('quizid');
        $key2->set_attributes(XMLDB_KEY_FOREIGN, array('quizid'), 'quiz', array('id'));

//         $table5->addKey($key2);
//         $table5->add_key('quizid', XMLDB_KEY_FOREIGN, array('quizid'), 'quiz', array('id'));

        // Conditionally launch create table for quizaccess_hbmon_node.
        if (!$dbman->table_exists($table5)) {
            $dbman->create_table($table5);
        }
        if (!$dbman->field_exists($table5, $field1)) {
            $dbman->add_field($table5, $field1);
        }
//         if (!$dbman->find_key_name('quizaccess_hbmon_node', 'quizid')) {
//         if (!$dbman->find_key_name($table5, $key2)) {
//             echo '<br><br><br> in find key name ';
//             $table5->addKey($key2);
            $dbman->add_key($table5, $key2);
//         }

        //---------------------------------------------------------------------------------------------
        // Heartbeatmonitor savepoint reached.
        upgrade_plugin_savepoint(true, 2017110220, 'quizaccess', 'heartbeatmonitor');
    }

}
<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    block_enrolsurvey
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 */

function xmldb_block_enrolsurvey_install() {
    global $CFG, $DB;
    $result = true;
    $dbman = $DB->get_manager();

    // Migrate block instances.
    $oldrecord = $DB->get_record('block', array('name' => 'enrol_survey'), 'id');
    if (!empty($oldrecord)) {
        // Convert any existing old enrol_survey block instances to enrolsurvey blocks.
        $sql = "UPDATE {block_instances} SET blockname = 'enrolsurvey' WHERE blockname = 'enrol_survey'";
        $DB->execute($sql);

        // Delete old enrol_survey record in block table.
        $DB->delete_records('block', array('id' => $oldrecord->id));
    }

    // Migrate old config settings.
    $settings = (array)get_config('block_enrol_survey');
    if (!empty($settings)) {
        foreach ($settings as $name => $val) {
            set_config($name, $val, 'block_enrolsurvey');
        }
    }
    unset_all_config_for_plugin('block_enrol_survey');

    // Migrate old block_enrol_survey table if it exists.
    $table = new xmldb_table('block_enrol_survey_taken'); // Old pre 2.6 table.
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('block_enrolsurvey_taken');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'block_enrolsurvey_taken');
    }

    // Migrate capabilities.
    $oldcapprefix = 'block/enrol_survey';
    $newcapprefix = 'block/enrolsurvey';
    $sql = 'SELECT * FROM {role_capabilities} WHERE capability LIKE ?';
    $params = array($oldcapprefix.'%');
    $rolecaps = $DB->get_recordset_sql($sql, $params);
    foreach ($rolecaps as $rolecaprec) {
        $updaterec = new stdClass;
        $updaterec->id = $rolecaprec->id;
        $updaterec->capability = str_replace($oldcapprefix, $newcapprefix, $rolecaprec->capability);
        $DB->update_record('role_capabilities', $updaterec);
    }

    return $result;
}

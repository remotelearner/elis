<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

class manual_options_userset_classifications extends manual_options_base_class {
    function get_options($dataobject) {
        global $DB;

        require_once elispm::file('plugins/userset_classification/usersetclassification.class.php');
        $recs = $DB->get_records(usersetclassification::TABLE, null, 'name ASC', 'shortname, name');
        if (!$recs) {
            return array();
        }
        $result = array();
        foreach ($recs as $rec) {
            $result[$rec->shortname] = $rec->name;
        }
        return $result;
    }

    function is_applicable($contextlevel) {
        //TODO: port to ELIS2 when required
        return $contextlevel === 'cluster' && is_readable($CFG->dirroot . '/elis/plugins/userset_classification/usersetclassification.class.php');
    }
}

?>

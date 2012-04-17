<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1/lib.php');

/**
 * The main Integration Point block class
 */
class block_rlip extends block_base {

    /**
     * Block initialization method
     */
    function init() {
        $this->title = get_string('pluginname', 'block_rlip');
    }

    /**
     * Method to fetch block contents
     *
     * @return object Object containing the text content of the block
     */
    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            //use cached version
            return $this->content;
        }

        $this->content = new stdClass;

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('moodle/site:config', $context)) {
            //add link to the IP plugins page
            $displaystring = get_string('plugins', 'block_rlip');
            $url = $CFG->wwwroot.'/blocks/rlip/plugins.php';
            $this->content->text = html_writer::tag('a', $displaystring, array('href' => $url));
            $this->content->text .= html_writer::empty_tag('br');

            //add link to the log viewing page
            $displaystring = get_string('logs', 'block_rlip');
            $url = $CFG->wwwroot.'/blocks/rlip/viewlogs.php';
            $this->content->text .= html_writer::tag('a', $displaystring, array('href' => $url));
        } else {
            $this->content->text = '';
        }

        $this->content->footer = '';

        return $this->content;
    }

    // Removes RLIP tables and data on uninstall
    public function before_delete() {
        global $DB;

        $dbman = $DB->get_manager();

        $rlipexporttbl = new xmldb_table(RLIPEXPORT_VERSION1_FIELD_TABLE);
        if ($dbman->table_exists($rlipexporttbl)) {
            $dbman->drop_table($rlipexporttbl);
        }

        $rlipimporttbl = new xmldb_table(RLIPIMPORT_VERSION1_MAPPING_TABLE);
        if ($dbman->table_exists($rlipimporttbl)) {
            $dbman->drop_table($rlipimporttbl);
        }

        $rlipplugins = array('rlipexport_version1', 'block_rlip', 'rlipimport_version1');
        $DB->delete_records_select('config_plugins', "plugin = ? OR plugin = ? OR plugin = ?", $rlipplugins);
    }

}

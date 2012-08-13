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
 * @package    rlip
 * @subpackage block_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */


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
        global $DB, $OUTPUT;

        $dbman = $DB->get_manager();

        // delete any elis_scheduled_tasks for block
        $DB->delete_records('elis_scheduled_tasks',
                            array('plugin' => 'block_rlip'));

        //delegate to sub-plugins
        $subplugintypes = array('rlipimport', 'rlipexport', 'rlipfile');
        foreach ($subplugintypes as $subplugintype) {
            $subplugins = get_plugin_list($subplugintype);

            //go through the subplugins for this type
            foreach ($subplugins as $subpluginname => $subpluginpath) {
                $uninstalllib = $subpluginpath.'/db/uninstall.php';

                if (file_exists($uninstalllib)) {
                    //we have an unstall db file
                    require_once($uninstalllib);
                    $uninstallfunction = 'xmldb_'.$subplugintype.'_'.$subpluginname.'_uninstall';

                    if (function_exists($uninstallfunction)) {
                        //we have an uninstall function, so run it
                        if (!$uninstallfunction()) {
                            echo $OUTPUT->notification('Encountered a problem running uninstall function for '.
                                                       $subplugintype.'_'.$subpluginname);
                        }
                    }
                }
            }
        } 
    }

}


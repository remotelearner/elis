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
        } else {
            $this->content->text = '';
        }

        $this->content->footer = '';

        return $this->content;
    }
}
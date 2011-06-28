<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elis::lib('page.class.php');

abstract class pm_page extends elis_page {
    /**
     * The page's short name
     */
    var $pagename;

    protected function _get_page_url() {
        global $CFG;
        return "{$CFG->wwwroot}/elis/program/index.php";
    }

    protected function _get_page_type() {
        return 'elispm';
    }

    protected function _get_page_params() {
        return array('s' => $this->pagename) + parent::_get_page_params();
    }

    function build_navbar_default() {
        global $CFG;
        parent::build_navbar_default();

        $this->navbar->add(get_string('learningplan', 'elis_program'), "{$CFG->wwwroot}/elis/program/");
    }

    /**
     * Specifies a unique shortname for the entity represented by
     * a page of this type, transforming the supplied value if necessary
     *
     * @param   string       $parent_path  Path of all parent elements, or the empty string
     *                                     if none
     * @param   string       $name         Initial name provided for the element
     *
     * @return  string|NULL                A valid name to identify the item with, or NULL if
     *                                     not applicable
     */
    static function get_entity_name($parent_path, $name) {
        //implement in child class if necessary
        return NULL;
    }
}

<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elisprogram_usetdisppriority
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once elispm::file('plugins/usetdisppriority/lib.php');

/**
 * Install function for this plugin
 *
 * @return  boolean  true  Returns true to satisfy install procedure
 */
function xmldb_elisprogram_usetdisppriority_install() {
    global $CFG;

    require_once elispm::lib('setup.php');

    $field = new field();
    $field->shortname = USERSET_DISPLAY_PRIORITY_FIELD;

    $field->name = get_string('display_priority_field_name', 'elisprogram_usetdisppriority');
    $field->datatype = 'int';

    $category = new field_category();
    $category->name = get_string('display_settings_category_name', 'elisprogram_usetdisppriority');

    $field = field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USERSET, $category);

    // make sure 'manual' is an owner
    if (!isset($field->owners['manual'])) {
        $owner = new field_owner();
        $owner->fieldid = $field->id;
        $owner->plugin = 'manual';
        $owner->param_view_capability = '';
        $owner->param_edit_capability = '';
        $owner->param_control = 'text';
        $owner->param_options_source = 'usetdisppriority';
        $owner->param_help_file = 'elisprogram_usetdisppriority/display_priority';
        $owner->save();
    }

    return true;
}

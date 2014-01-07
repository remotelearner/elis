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
 * @package    elisprogram_usetclassify
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once elispm::file('plugins/usetclassify/lib.php');

/**
 * Install function for this plugin
 *
 * @return  boolean  true  Returns true to satisfy install procedure
 */
function xmldb_elisprogram_usetclassify_install() {
    global $CFG;

    require_once elispm::lib('setup.php');
    require_once elis::lib('data/customfield.class.php');
    require_once elispm::file('plugins/usetclassify/usersetclassification.class.php');

    $field = new field();
    $field->shortname = USERSET_CLASSIFICATION_FIELD;
    $field->name = get_string('classification_field_name', 'elisprogram_usetclassify');
    $field->datatype = 'char';

    $category = new field_category();
    $category->name = get_string('classification_category_name', 'elisprogram_usetclassify');

    $field = field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USERSET, $category);

    // make sure we're set as owner
    if (!isset($field->owners['usetclassify'])) {
        $owner = new field_owner();
        $owner->fieldid = $field->id;
        $owner->plugin = 'usetclassify';
        $owner->save();
    }

    // make sure 'manual' is an owner
    if (!isset($field->owners['manual'])) {
        $owner = new field_owner();
        $owner->fieldid = $field->id;
        $owner->plugin = 'manual';
        $owner->param_view_capability = '';
        $owner->param_edit_capability = 'moodle/user:update';
        $owner->param_control = 'menu';
        $owner->param_options_source = 'usetclassify';
        $owner->param_help_file = 'elisprogram_usetclassify/cluster_classification';
        $owner->save();
    }

    // make sure we have a default value set
    if (!field_data::get_for_context_and_field(NULL, $field)) {
        field_data::set_for_context_and_field(NULL, $field, 'regular');
    }

    $default = new usersetclassification();
    $default->shortname = 'regular';
    $default->name = get_string('cluster', 'local_elisprogram');
    $default->param_autoenrol_curricula = 1;
    $default->param_autoenrol_tracks = 1;
    $default->save();

    return true;
}

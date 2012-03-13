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
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) .'/../../config.php');
require_once($CFG->dirroot .'/elis/core/lib/setup.php');
//require_once($CFG->dirroot .'/blocks/rlip/form/rlip_schedule_form.class.php');

require_once elis::lib('page.class.php');

class ip_schedule_page extends elis_page {
    var $form_class = 'rlip_schedule_form';

    function get_page_title_default() {
        // TBD: could check param 'plugin' & show different import/export title
        return get_string('schedulepagetitle', 'block_rlip');
    }

    function build_navbar_default() {
        $this->navbar->add(get_string('pluginname', 'block_rlip'), ''); // TBD
    }

    function can_do_default() {
        return has_capability('moodle/site:config',
                              get_context_instance(CONTEXT_SYSTEM)); // TBD
    }

    function display_default() {
        error_log('/blocks/rlip/schedulepage.php::display_default() > ENTER');
        $plugin = $this->required_param('plugin', PARAM_CLEAN);
        list($type, $name) = explode('_', $plugin);
        switch ($type) {
            case 'rlipimport':
                $this->display_imports();
                break;
            case 'rlipexport':
                $this->display_exports();
                break;
            default:
                error_log("/blocks/rlip/schedulepage.php::display_default() - Illegal plugin type: '{$type}'");
                print_error('iptypenotsupported', 'block_rlip', '', $type);
        }
    }

    function display_imports() {
        echo 'display_imports';
    }

    function display_exports() {
        echo 'display_exports';
    }
}

$PAGE = new ip_schedule_page();
$PAGE->requires->css('/blocks/rlip/styles.css');
$PAGE->run();


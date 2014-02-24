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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once elispm::lib('deprecatedlib.php'); // cm_get_crlmuserid()
require_once elispm::lib('page.class.php');
require_once elispm::file('healthpage.class.php');

/**
 * This page is just a dummy that allows generic linking to the dashboard
 * from the Curriculum Admin menu
 *
 */
class dashboardpage extends pm_page {
    // Arrays for which components last cron runtimes to include
    private $blocks = array(); // empty array for none; 'elisadmin' ?
    private $plugins = array(); // TBD: 'local_elisprogram', 'local_eliscore' ?

    /**
     * Determines whether or not the current user can navigate to the
     * Curriculum Admin dashboard
     *
     * @return  boolean  Whether or not access is allowed
     *
     */
    function can_do_default() {
        //allow any logged-in user since the dashboard varies based on the user
        return isloggedin();
    }

    /**
     * Create a url to the current page (just points to the main PM index)
     *
     * @return moodle_url
     */
    function get_moodle_url($extra = array()) {
        $page = $this->get_new_page($extra);
        $url = $page->url;

        return $url;
    }

    function last_cron_runtimes() {
        global $DB;
        $description = '';
        foreach ($this->blocks as $block) {
            $a = new stdClass;
            $a->name = $block;
            $lastcron = $DB->get_field('block', 'lastcron', array('name' => $block));
            $a->lastcron = $lastcron ? userdate($lastcron) : get_string('cron_notrun', 'local_elisprogram');
            $description .= get_string('health_cron_block', 'local_elisprogram', $a);
        }
        foreach ($this->plugins as $plugin) {
            $a = new stdClass;
            $a->name = $plugin;
            $lastcron = $DB->get_field('config_plugins', 'value', array('plugin' => $plugin, 'name' => 'lastcron'));
            $a->lastcron = $lastcron ? userdate($lastcron) : get_string('cron_notrun', 'local_elisprogram');
            $description .= get_string('health_cron_plugin', 'local_elisprogram', $a);
        }
        $lasteliscron = $DB->get_field('local_eliscore_sched_tasks', 'MAX(lastruntime)', array());
        $lastcron = $lasteliscron ? userdate($lasteliscron) : get_string('cron_notrun', 'local_elisprogram');
        $description .= get_string('health_cron_elis', 'local_elisprogram', $lastcron);
        return $description;
    }

    /**
     * Entry point to the page
     */
    function do_default() {
        global $USER, $CFG;
        require_once(elispm::lib('lib.php'));

        //update the current user's info in the PM system
        //todo: check return status?
        pm_update_user_information($USER->id);

        //display as normal
        $this->display('default');
    }

    /**
     * get_tcpdf_info method to get TCPDF library version info
     * @return array componentname, release, version
     */
    public function get_tcpdf_info() {
        global $CFG;
        $ret = array(null, null, null);
        $tcpdfinfofile = $CFG->dirroot.'/local/elisreports/lib/tcpdf/README.TXT';
        if (file_exists($tcpdfinfofile)) {
            $tcpdfreadme = file_get_contents($tcpdfinfofile);
            $matches = array();
            $name = '';
            $release = '';
            $version = '';
            if (preg_match('/Name: (.*)/', $tcpdfreadme, $matches)) {
                $name = $matches[1];
            }
            if (preg_match('/Version: (.*)/', $tcpdfreadme, $matches)) {
                $release = $matches[1];
            }
            if (preg_match('/Release date: (.*)/', $tcpdfreadme, $matches)) {
                $version = $matches[1];
            }
            $ret = array($name, $release, $version);
        }
        return $ret;
    }

    /**
     * get_pchart_info method to get pChart library version info
     * @return array componentname, release, version
     */
    public function get_pchart_info() {
        global $CFG;
        $ret = array(null, null, null);
        $pchartinfofile = $CFG->dirroot.'/local/elisreports/lib/pChart.1.27d/pChart/pChart.class';
        if (file_exists($pchartinfofile)) {
            $ret = array('pChart', '1.27d', '06/17/2008'); // TBD - get from file?
        }
        return $ret;
    }

    /**
     * get_jquery_file_info method to get jQuery library version info from specified file
     * @param array $files list of files to get info from
     * @param array $infostrings associative array of default values, i.e. array('name' => 'Name', 'version' => 'Version', 'release' => 'Release date')
     * @return array componentname, release, version
     */
    public function get_jquery_file_info($files, $infostrings) {
        $ret = array(null, null, null);
        foreach ($files as $filename) {
            $matches = array();
            $name = null;
            if (preg_match("/.*{$infostrings['name']}-([0-9.]*)/", $filename, $matches)) {
                $name = $infostrings['name'];
                $release = trim($matches[1], '.');
                $version = '';
                $ret = array($name, $release, $version);
                break;
            }
        }
        return $ret;
    }

    /**
     * get_re_jquery_info method to get jQuery library version info
     * @return array componentname, release, version
     */
    public function get_re_jquery_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/js/results_engine/jquery-*');
        return $this->get_jquery_file_info($files, array('name' => 'jquery'));
    }

    /**
     * get_re_jquery_ui_info method to get jQuery library version info
     * @return array componentname, release, version
     */
    public function get_re_jquery_ui_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/js/results_engine/jquery-ui-*');
        return $this->get_jquery_file_info($files, array('name' => 'jquery-ui'));
    }

    /**
     * get_ds_jquery_info method to get jQuery library version info
     * @return array componentname, release, version
     */
    public function get_ds_jquery_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/lib/deepsight/js/jquery-*');
        return $this->get_jquery_file_info($files, array('name' => 'jquery'));
    }

    /**
     * get_ds_jquery_ui_info method to get jQuery library version info
     * @return array componentname, release, version
     */
    public function get_ds_jquery_ui_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/lib/deepsight/js/jquery-ui-*');
        return $this->get_jquery_file_info($files, array('name' => 'jquery-ui'));
    }

    /**
     * elis_versions method to get all ELIS PM and component version info
     * @return string
     */
    protected function elis_versions() {
        global $CFG;
        $ret = html_writer::script(
                "function toggle_elis_component_versions() {
                    var compdiv;
                    if (compdiv = document.getElementById('eliscomponentversions')) {
                        if (compdiv.className.indexOf('accesshide') != -1) {
                            compdiv.className = '';
                        } else {
                            compdiv.className = 'accesshide';
                        }
                     }
                 }");
        $ret .= html_writer::tag('p', get_string('elispmversion', 'local_elisprogram', elispm::$release).'&nbsp;'.
                html_writer::empty_tag('input', array(
                    'type' => 'button',
                    'value' => get_string('alleliscomponents', 'local_elisprogram'),
                    'onclick' => 'toggle_elis_component_versions();'
                )));
        $eliscomponents = array(
            'block_elisadmin' => null,
            'block_courserequest' => null,
            'block_enrolsurvey' => null,
            'block_repository' => null,
            'enrol_elis' => null,
            'local_eliscore' => null,
            'local_elisprogram' => null,
            'local_elisreports' => null,
            'local_datahub' => null,
            'auth_elisfilessso' => null,
            'repository_elisfiles' => null,
            'lib_tcpdf' => array($this, 'get_tcpdf_info'),
            'lib_pChart' => array($this, 'get_pchart_info'),
            'lib_jquery1' => array($this, 'get_re_jquery_info'),
            'lib_jquery_ui1' => array($this, 'get_re_jquery_ui_info'),
            'lib_jquery2' => array($this, 'get_ds_jquery_info'),
            'lib_jquery_ui2' => array($this, 'get_ds_jquery_ui_info')
        );
        $componenttable = new html_table();
        $componenttable->attributes = array('width' => '70%', 'border' => '0');
        $componenttable->head = array(get_string('eliscomponent', 'local_elisprogram'),
                get_string('eliscomponentrelease', 'local_elisprogram'),
                get_string('eliscomponentversion', 'local_elisprogram'));
        $componenttable->data = array();
        foreach ($eliscomponents as $eliscomponent => $getinfocallback) {
            list($plugintype, $pluginname) = explode('_', $eliscomponent);
            if (!empty($getinfocallback)) {
                list($componentname, $release, $version) = call_user_func($getinfocallback);
                // error_log("elis_versions(): {$componentname}, {$release}, {$version}");
                if (!empty($componentname)) {
                    $thirdpartylib = get_string('thirdpartylib', 'local_elisprogram');
                    $componenttable->data[] = array("$componentname $thirdpartylib",
                            $release, $version);
                }
            } else if (($compdir = core_component::get_plugin_directory($plugintype, $pluginname)) && file_exists($compdir.'/version.php')) {
                $plugin = new stdClass;
                require($compdir.'/version.php');
                if (!empty($plugin->version)) {
                    $version = $plugin->version;
                    $release = !empty($plugin->release) ? $plugin->release : '';
                    $componenttable->data[] = array($eliscomponent, $release, $version);
                }
            }
        }
        $ret .= html_writer::tag('div', html_writer::table($componenttable), array(
            'id' => 'eliscomponentversions',
            'class' => 'accesshide'));
        return $ret;
    }

    function display_default() {
        global $CFG, $USER, $OUTPUT;

        $context = context_system::instance();
        if (has_capability('local/elisprogram:manage', $context) || has_capability('local/elisprogram:config', $context)) {
            echo $OUTPUT->heading(get_string('admin_dashboard', 'local_elisprogram'));
            echo $OUTPUT->box(html_writer::tag('p', get_string('elis_doc_class_link', 'local_elisprogram')));
            echo $OUTPUT->box(html_writer::tag('p', $this->last_cron_runtimes()));
            $healthpg = new healthpage();
            if ($healthpg->can_do_default()) {
                echo $OUTPUT->box(html_writer::tag('p', get_string('health_check_link', 'local_elisprogram', $CFG)));
            }

            // Output ELIS version info
            echo  $OUTPUT->box($this->elis_versions());
        }

        if ($cmuid = cm_get_crlmuserid($USER->id)) {
            $user = new user($cmuid);
            echo $user->get_dashboard();
        }
    }
}

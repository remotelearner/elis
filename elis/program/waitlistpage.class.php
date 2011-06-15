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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/student.class.php');
require_once elispm::lib('data/waitlist.class.php');
require_once elispm::lib('selectionpage.class.php');
require_once elispm::file('pmclasspage.class.php');
require_once elispm::file('form/waitlistform.class.php');

class waitlistpage extends selectionpage {
    const LANG_FILE = 'elis_program';

    var $data_class = 'waitlist';
    var $pagename   = 'wtg';
    var $tab_page   = 'pmclasspage'; // TBD: 'cmclasspage'
    var $section    = 'curr';        // TBD: 'curr'

    function can_do_default() {
        $id = $this->required_param('id', PARAM_INT);
        $pmclasspage = new pmclasspage(array('id' => $id));
        return $pmclasspage->can_do('edit');
    }

    protected function get_selection_form() {
        return new waitlisteditform();
    }

    function get_selection_filter() {
        $alpha      = $this->optional_param('alpha', '', PARAM_ALPHA);
        $namesearch = trim($this->optional_param('search', '', PARAM_CLEAN));
        // FIXME:
        return array('alpha' => $alpha, 'namesearch' => $namesearch);
    }

    /* *** TBD *** */
    function print_selection_filter($filter) {
        $id = $this->required_param('id', PARAM_INT);
        pmalphabox(new moodle_url($this->_get_page_url(),
                           array('s' => $this->pagename, 'id' => $id)));
        pmsearchbox($this->get_basepage());
    }

    function get_records($filter) {
        $sort    = $this->optional_param('sort', 'timecreated', PARAM_CLEAN);
        $dir     = $this->optional_param('dir', 'ASC', PARAM_CLEAN);
        $page    = $this->optional_param('page', 0, PARAM_INT);
        $perpage = $this->optional_param('perpage', 30, PARAM_INT); // how many per page
        $id      = $this->required_param('id', PARAM_INT);

        $items = waitlist::get_students($id, $sort, $dir, $page, $perpage, $filter['namesearch'], $filter['alpha']);
        $numitems = waitlist::count_records($id, $filter['namesearch'], $filter['alpha']);

        return array($items, $numitems);
    }

    function get_records_from_selection($selection) {
        global $DB;
        $id = $this->required_param('id', PARAM_INT); // TBD
        $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $sql = "SELECT watlst.id, usr.id as uid, $FULLNAME as name, usr.idnumber, usr.country, usr.language, watlst.timecreated
                  FROM {". waitlist::TABLE .'} watlst
                  JOIN {'. user::TABLE .'} usr ON watlst.userid = usr.id
                 WHERE watlst.classid = ? 
                   AND watlst.id IN ('. implode(',',$selection) .')';
        return $DB->get_records_sql($sql, array($id));
    }

    function create_selection_table($records, $baseurl) {
        $id = $this->required_param('id', PARAM_INT); // TBD
        return new waitlist_table($records,
                   new moodle_url($baseurl, array('s' => $this->pagename,
                                                  'id' => $id)
                   ));
    }

    protected function get_base_params() {
        $params = parent::get_base_params();
        $params['id'] = $this->required_param('id', PARAM_INT);
        return $params;
    }

    function get_tab_page($params=array()) {
        return new $this->tab_page($params);
    }

    function print_header() {
        parent::print_header();

        if (!$this->is_bare()) {
            $id = $this->required_param('id', PARAM_INT);
            $this->get_tab_page()->print_tabs(get_class($this), array('id' => $id));
        }
    }

    function process_selection($data) {
        $id = $this->required_param('id', PARAM_INT);

        if (empty($data->_selection)) {
            echo print_error('no_items_selected', self::LANG_FILE);
        } else {
            $sparam = new stdClass;
            $sparam->num = count($data->_selection);
            // ***TBD***
            notice_yesno(get_string('confirm_waitlist_'.$data->do, self::LANG_FILE, $sparam),
                         'index.php', 'index.php',
                         array('s' => $this->pagename,
                               'id' => $id,
                               'action' => $data->do,
                               'selected' => implode(',',$data->_selection)
                             ),
                         array('s' => $this->pagename, 'id' => $id),
                         'POST', 'GET');
        }
    }

    function action_remove() {
        $id = $this->required_param('id', PARAM_INT);
        $recs = explode(',', $this->required_param('selected',PARAM_TEXT));

        // make sure everything is an int
        foreach ($recs as $key => $val) {
            $recs[$key] = (int)$val;
            if (empty($recs[$key])) {
                unset($recs[$key]);
            }
        }

        $result = true;
        foreach ($recs as $recid) {
            $waitlistobj = new waitlist($recid);
            if (!($result = $waitlistobj->delete())) {
                break;
            }
        }

        $tmppage = new waitlistpage(array('id' => $id));
        if ($result) {
            redirect($tmppage->url, get_string('success_waitlist_remove', self::LANG_FILE));
        } else {
            $sparam = new stdClass;
            $sparam->url = $tmppage->url;
            print_error('error_waitlist_remove', self::LANG_FILE, $sparam);
        }
    }

    function action_overenrol() {
        $id = $this->required_param('id', PARAM_INT);
        $recs = explode(',', $this->required_param('selected',PARAM_TEXT));

        // make sure everything is an int
        foreach ($recs as $key => $val) {
            $recs[$key] = (int)$val;
            if (empty($recs[$key])) {
                unset($recs[$key]);
            }
        }

        $result = true;
        foreach ($recs as $recid) {
            $waitlistobj = new waitlist($recid);
            $waitlistobj->enrol();
        }

        $tmppage = new waitlistpage(array('id' => $id));
        if ($result) {
            redirect($tmppage->url, get_string('success_waitlist_overenrol', self::LANG_FILE));
        } else {
            $sparam = new stdClass;
            $sparam->url = $tmppage->url;
            print_error('error_waitlist_overenrol', self::LANG_FILE, $sparam);
        }
    }
}

class waitlist_table extends selection_table {
    const LANG_FILE = 'elis_program';

    function __construct(&$items, $url) {
        $columns = array(
            '_selection'  => array('header' => '', 'sortable' => false,
                                   'display_function' => array(&$this, 'get_item_display__selection')), // TBD
            'idnumber'    => array('header' => get_string('idnumber',        self::LANG_FILE)),
            'name'        => array('header' => get_string('name',            self::LANG_FILE)),
            'country'     => array('header' => get_string('country',         self::LANG_FILE)),
            'language'    => array('header' => get_string('user_language',   self::LANG_FILE)),
            'timecreated' => array('header' => get_string('registered_date', self::LANG_FILE),
                                   'display_function' => array(&$this, 'get_item_display_timecreated')), // TBD , ?
        );

        // foreach($items as $item) $item->_selection = '';

        //$formatters = array();
        // TBD: class recordlinkformatter ???
        //$formatters['name'] = $formatters['idnumber'] = new recordlinkformatter(new usermanagementpage(), 'uid');
        parent::__construct($items, $columns, $url);
    }

    function get_item_display_timecreated($column, $item) {
        return $this->get_date_item_display($column, $item);
    }

}


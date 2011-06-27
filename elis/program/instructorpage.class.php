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

require_once elispm::lib('lib.php');
require_once elispm::lib('deprecatedlib.php'); // cm_get_param(), cm_error() ...
require_once elispm::lib('associationpage.class.php');
require_once elispm::lib('data/instructor.class.php');
require_once elispm::file('pmclasspage.class.php');

class instructorpage extends associationpage {
    const LANG_FILE = 'elis_program';

    var $data_class = 'instructor';
    var $pagename = 'ins';
    var $tab_page = 'pmclasspage'; // cmclasspage

    //var $form_class = 'instructorform';

    var $section = 'curr';

    var $parent_data_class = 'pmclass'; // cmclass

    function __construct(array $params = null) {
        $this->tabs = array(
        array('tab_id' => 'currcourse_edit', 'page' => get_class($this), 'params' => array('action' => 'edit'), 'name' => 'Edit', 'showtab' => true, 'showbutton' => true, 'image' => 'edit.gif'),
        array('tab_id' => 'delete', 'page' => get_class($this), 'params' => array('action' => 'delete'), 'name' => 'Delete', 'showbutton' => true, 'image' => 'delete.gif'),
        );
        parent::__construct($params);
    }

    function can_do_default() {
        $id = $this->required_param('id', PARAM_INT);
        $pmclasspage = new pmclasspage(array('id' => $id)); // cmclasspage
        return $pmclasspage->can_do('edit');
    }

    function do_delete() { // action_confirm
        $insid = required_param('association_id', PARAM_INT);
        $confirm = required_param('confirm', PARAM_TEXT);

        $ins = new instructor($insid);
        $event_object = $this->_db->get_record(instructor::TABLE, array('id' => $insid));

        if (md5($insid) != $confirm) {
            echo cm_error(get_string('invalidconfirm', self::LANG_FILE));
        } else {
            $user = new user($ins->userid); // TBD: $event_object->userid
            $user->name = fullname($user);
            $status = $ins->delete();
          /* **** no return code from delete()
            if (!$status) {
                echo cm_error(get_string('instructor_notdeleted', self::LANG_FILE, $user));
            } else
          **** */
            { //instructor_successfully_deleted
                echo cm_error(get_string('instructor_deleted', self::LANG_FILE, $user));
            }
        }
        $this->display('default'); // $this->action_default();
    }

    function do_add() {
        $this->do_savenew();
    }

    function display_add() {
        $action       = cm_get_param('action', 'add'); // TBD was: ''
        $delete       = cm_get_param('delete', 0);
        $confirm      = cm_get_param('confirm', '');   //md5 confirmation hash
        $confirmuser  = cm_get_param('confirmuser', 0);
        $insid        = cm_get_param('association_id', 0);
        $clsid        = cm_get_param('id', 0);
        $userid       = cm_get_param('userid', 0);
        $sort         = cm_get_param('sort', 'name'); // TBD was: 'assigntime'
        $dir          = cm_get_param('dir', 'ASC');
        $page         = cm_get_param('page', 0);
        $perpage      = cm_get_param('perpage', 30);        // how many per page
        $namesearch   = trim(cm_get_param('search', ''));
        $alpha        = cm_get_param('alpha', '');

        echo $this->get_add_form($clsid, $sort, $dir, $page, $perpage, $namesearch, $alpha);
    }

    function display_delete() { // action_delete
        $insid = required_param('association_id', PARAM_INT);
        echo $this->get_delete_form($insid);
    }

    function display_edit() { // action_edit
        $insid = required_param('association_id', PARAM_INT);
        echo $this->get_edit_form($insid);
    }

    function do_edit() {
        $this->do_savenew();
    }

    function do_savenew() { // action_savenew
        $users = cm_get_param('users', array());
        $clsid = required_param('id', PARAM_INT);

        if (!empty($users)) {
            foreach ($users as $uid => $user) {
                if (!empty($user['assign'])) {
                    $insrecord            = array();
                    $insrecord['classid'] = $clsid;
                    $insrecord['userid']  = $uid;

                    $startyear  = $user['startyear'];
                    $startmonth = $user['startmonth'];
                    $startday   = $user['startday'];
                    $insrecord['assigntime'] = mktime(0, 0, 0, $startmonth, $startday, $startyear);

                    $endyear  = $user['endyear'];
                    $endmonth = $user['endmonth'];
                    $endday   = $user['endday'];
                    $insrecord['completetime'] = mktime(0, 0, 0, $endmonth, $endday, $endyear);

                    $newins = new instructor($insrecord);
                    $status = $newins->save();
                  /* **** no return code from ->save()
                    if ($status !== true) {
                        if (!empty($status->message)) {
                            echo cm_error(get_string('record_not_created_reason', self::LANG_FILE, $status));
                        } else {
                            echo cm_error(get_string('record_not_created', self::LANG_FILE));
                        }
                    }
                  **** */
                }
            }
        }

        $this->display('add'); // $this->action_default();
    }

    function do_update() { // action_update
        $userid = required_param('userid', PARAM_INT);
        $insid = required_param('association_id', PARAM_INT);
        $clsid = required_param('id', PARAM_INT);

        $users = cm_get_param('users', array());
        $uid   = $userid;
        $user  = current($users);

        $insrecord            = array();
        $insrecord['id']      = $insid;
        $insrecord['classid'] = $clsid;
        $insrecord['userid']  = $uid;

        $startyear  = $user['startyear'];
        $startmonth = $user['startmonth'];
        $startday   = $user['startday'];
        $insrecord['assigntime'] = mktime(0, 0, 0, $startmonth, $startday, $startyear);

        $endyear  = $user['endyear'];
        $endmonth = $user['endmonth'];
        $endday   = $user['endday'];
        $insrecord['completetime'] = mktime(0, 0, 0, $endmonth, $endday, $endyear);

        $ins = new instructor($insrecord);
        $status = $ins->save(); // WAS: $ins->data_update_record()
      /* **** no return code from ->save()
        if ($status !== true) {
            echo cm_error(get_string('record_not_created_reason', self::LANG_FILE, $status));
        }
      **** */

        $this->display('default'); // $this->action_default();
    }

    function display_default() { // action_default()
        global $OUTPUT;

        $action       = cm_get_param('action', 'default'); // TBD: was ''
        $delete       = cm_get_param('delete', 0);
        $confirm      = cm_get_param('confirm', ''); //md5 confirmation hash
        $confirmuser  = cm_get_param('confirmuser', 0);
        $insid        = cm_get_param('association_id', 0);
        $clsid        = cm_get_param('id', 0);
        $userid       = cm_get_param('userid', 0);
        $sort         = cm_get_param('sort', 'name'); // TBD 'assigntime'
        $dir          = cm_get_param('dir', 'ASC');
        $page         = cm_get_param('page', 0);
        $perpage      = cm_get_param('perpage', 30); // how many per page
        $namesearch   = trim(cm_get_param('search', ''));
        $alpha        = cm_get_param('alpha', '');

        $cls = new pmclass($clsid); // cmclass($clsid)

        $columns = array(
            'idnumber'     => array('header' => get_string('instructor_idnumber', self::LANG_FILE),
                                    'display_function' => 'htmltab_display_function'),
            'name'         => array('header' => get_string('instructor_name', self::LANG_FILE),
                                    'display_function' => 'htmltab_display_function'),
            'assigntime'   => array('header' => get_string('instructor_assignment', self::LANG_FILE),
                                    'display_function' => 'htmltab_display_function'),
            'completetime' => array('header' => get_string('instructor_completion', self::LANG_FILE),
                                    'display_function' => 'htmltab_display_function'),
            'buttons'      => array('header' => '', 'sortable' => false,
                                    'display_function' => 'htmltab_display_function')
        );

      /* **** TBD
        foreach ($columns as $column => $cdesc) {
            if ($sort != $column) {
                $columnicon = "";
                $columndir = "ASC";
            } else {
                $columndir  = $dir == "ASC" ? "DESC":"ASC";
                $columnicon = $dir == "ASC" ? "down":"up";
                $columnicon = " <img src=\"$CFG->pixpath/t/$columnicon.gif\" alt=\"\" />";

            }
            $$column = "<a href=\"index.php?s=ins&amp;section=curr&amp;id=$clsid&amp;sort=$column&amp;dir=$columndir&amp;namesearch=".urlencode(stripslashes($namesearch))."&amp;alpha=$alpha\">".$cdesc."</a>$columnicon";
            $table->head[]  = $$column;
            $table->align[] = "left";
        }
        $table->head[]  = '';
        $table->align[] = 'center';
      **** */

        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }
        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        } else {
            $sort = 'name';
            $columns[$sort]['sortable'] = $dir;
        }

        $inss    = instructor_get_listing($clsid, $sort, $dir, $page*$perpage, $perpage, $namesearch, $alpha);
        $numinss = instructor_count_records($clsid);

        $page_params = array('s' => 'ins', 'section' => 'curr', 'id' => $clsid,
                        'action' => $action, 'sort' => $sort, 'dir' => $dir,
                        'perpage' => $perpage, 'search' => $namesearch);

        pmalphabox(new moodle_url($this->_get_page_url(), $page_params),
            'alpha', get_string('instructor_name', self::LANG_FILE) .':');

      /* **** replaced by pmalphabox()
        $alphabet = explode(',', get_string('alphabet', 'langconfig'));
        $strall = get_string('all');

        echo "<p style=\"text-align:center\">";
        echo get_string('instructor_name', self::LANG_FILE)." : ";
        if ($alpha) {
            echo " <a href=\"index.php?s=ins&amp;section=curr&amp;id=$clsid&amp;sort=name&amp;dir=ASC&amp;".
                 "perpage=$perpage\">$strall</a> ";
        } else {
            echo " <b>$strall</b> ";
        }
        foreach ($alphabet as $letter) {
            if ($letter == $alpha) {
                echo " <b>$letter</b> ";
            } else {
                echo " <a href=\"index.php?s=ins&amp;section=curr&amp;id=$clsid&amp;sort=name&amp;dir=ASC&amp;".
                     "perpage=$perpage&amp;alpha=$letter\">$letter</a> ";
            }
        }
        echo "</p>";
      **** */

        // TBD: added action, '/elis/program/index.php' ???
        $full_url = "index.php?s=ins&amp;section=curr&amp;id=$clsid&amp;action=$action&amp;sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;alpha=$alpha&amp;search="
                    . urlencode(stripslashes($namesearch)) .'&amp;';
        $pagingbar = new paging_bar($numinss, $page, $perpage, $full_url);
        echo $OUTPUT->render($pagingbar);
        flush();

        $table = NULL;
        if (!$inss) {
            pmshowmatches($alpha, $namesearch, null, 'no_instructor_matching');
        } else {
            // TBD
            //$table->align = array ("left", "left", "center", "center");
            //$table->width = "95%";

            $newarr = array();
            foreach ($inss as $ins) {
                $deletebutton = '<a href="index.php?s=ins&amp;section=curr&amp;id=' . $clsid .
                                '&amp;action=delete&amp;association_id=' . $ins->id . '">' .
                                '<img src="'. $OUTPUT->pix_url('delete') . '" alt="Delete" title="Delete" /></a>';
                $editbutton = '<a href="index.php?s=ins&amp;section=curr&amp;id=' . $clsid .
                              '&amp;action=edit&amp;association_id=' . $ins->id . '">' .
                              '<img src="'. $OUTPUT->pix_url('edit') .'" alt="Edit" title="Edit" /></a>';

                foreach ($columns as $column => $cdesc) {
                    if (($column == 'assigntime') || ($column == 'completetime')) {
                        $newarr[] = !empty($ins->$column)
                                    ? date(get_string('pm_date_format',
                                                      self::LANG_FILE),
                                           $ins->$column)
                                    : '-';
                    } else if ($column == 'buttons') {
                        $newarr[] = $editbutton . ' ' . $deletebutton;
                    } else {
                        $newarr[] = $ins->$column;
                    }
                }
                //$table->data[] = $newarr;
            }
            if (!empty($newarr)) {
                $page_params['alpha'] = $alpha;
                unset($page_params['sort']);
                unset($page_params['dir']);
                $table = new display_table($newarr, $columns,
                                           get_pm_url(null, $page_params));
            }
        }

        pmsearchbox($this, 'search', 'get', get_string('show_all_users', self::LANG_FILE));

        $add_instructor_link = '<a href="index.php?s=ins&amp;section=curr&amp;action=add&amp;id='. $clsid .'">'. get_string('instructor_add', self::LANG_FILE) .'</a>';
        if (!empty($table)) {
            echo $OUTPUT->heading($add_instructor_link);
            echo $table->get_html();
            $pagingbar = new paging_bar($numinss, $page, $perpage, $full_url);
            echo $OUTPUT->render($pagingbar);
        }
        echo $OUTPUT->heading($add_instructor_link);

    }

    function get_add_form($clsid, $sort, $dir, $page, $perpage, $namesearch, $alpha) {
        $output = '';

        $newins = new instructor(); // TBD: was new instructor($clsid)
        //$newins->classid = $clsid;
        //$cls = new pmclass($clsid); // cmclass($clsid)

        $output .= $newins->edit_form_html($clsid, $sort, $dir, $page, $perpage, $namesearch, $alpha);

        return $output;
    }


    /**
     * Returns the edit ins form.
     *
     * @return string HTML for the form.
     */
    function get_edit_form($insid, $sort = '', $dir = '', $startrec = 0,
                           $perpage = 0, $namesearch = '', $alpha = '') {
        $output = '';

        $ins = new instructor($insid);

        $output .= $ins->edit_form_html($insid);

        return $output;
    }


    /**
     * Returns the delete instructor form.
     *
     * @param string $action Delete or confirm.
     * @param int    $id     The id of the instructor.
     * @return string HTML for the form.
     *
     */
    function get_delete_form($insid) {
        $ins = new instructor($insid);

        $url     = 'index.php';
        $message = get_string('confirm_delete_instructor', 'block_curr_admin', cm_fullname($ins->user));
        $optionsyes = array('s' => 'ins', 'section' => 'curr', 'id' => $ins->classid,
                            'action' => 'confirm', 'association_id' => $insid, 'confirm' => md5($insid));
        $optionsno = array('s' => 'ins', 'section' => 'curr', 'id' => $ins->classid,
                           'search' => $ins->pmclass->idnumber); // TBD: cmclass

        echo cm_delete_form($url, $message, $optionsyes, $optionsno);

    }
}


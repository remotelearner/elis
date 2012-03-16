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
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();


define('CONTEXT_ELIS_PROGRAM', 1001);
define('CONTEXT_ELIS_TRACK',   1002);
define('CONTEXT_ELIS_COURSE',  1003);
define('CONTEXT_ELIS_CLASS',   1004);
define('CONTEXT_ELIS_USER',    1005);
define('CONTEXT_ELIS_USERSET', 1006);




class context_elis_helper extends context {

    private static $alllevels = array(
            CONTEXT_ELIS_PROGRAM => 'context_elis_program',
            CONTEXT_ELIS_TRACK   => 'context_elis_track',
            CONTEXT_ELIS_COURSE  => 'context_elis_course',
            CONTEXT_ELIS_CLASS   => 'context_elis_class',
            CONTEXT_ELIS_USER    => 'context_elis_user',
            CONTEXT_ELIS_USERSET => 'context_elis_userset',
    );

    /**
     * Instance does not make sense here, only static use
     */
    protected function __construct() {
    }

    /**
     * Returns a class name of the context level class
     *
     * @static
     * @param int $contextlevel (CONTEXT_SYSTEM, etc.)
     * @return string class name of the context class
     */
    public static function get_class_for_level($contextlevel) {
        if (isset(self::$alllevels[$contextlevel])) {
            return self::$alllevels[$contextlevel];
        } else {
            throw new coding_exception('Invalid context level specified');
        }
    }

    /**
     * not used
     */
    public function get_url() {
    }

    /**
     * not used
     */
    public function get_capabilities() {
    }
}

/**
 * ELIS program context
 */
class context_elis_program extends context {
    /**
     * Please use context_elis_program::instance($programid) if you need the instance of this context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_ELIS_PROGRAM) {
            throw new coding_exception('Invalid $record->contextlevel in context_elis_program constructor.');
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('curriculum', 'elis_program');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($program = $DB->get_record(curriculum::TABLE, array('id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('curriculum', 'elis_program').': ';
            }
            $name .= format_string($program->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        $params = array(
            's'      => 'cur',
            'action' => 'view',
            'id'     => $this->_instanceid
        );
        return new moodle_url('/elis/program/index.php', $params);
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        // TODO: Are these context levels correct in the following query?
        $params = array();
        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel IN (".CONTEXT_ELIS_PROGRAM.",".CONTEXT_ELIS_TRACK.",".CONTEXT_ELIS_COURSE.",".CONTEXT_ELIS_CLASS.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns ELIS program context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_coursecat context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_ELIS_PROGRAM, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_ELIS_PROGRAM, 'instanceid'=>$instanceid))) {
            if ($program = $DB->get_record(curriculum::TABLE, array('id'=>$instanceid), 'id,idnumber', $strictness)) {
                $record = context::insert_context_record(CONTEXT_ELIS_PROGRAM, $program->id, '/'.SYSCONTEXTID, 0);
            }
        }

        if ($record) {
            $context = new context_elis_program($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts of program and all tracks
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_ELIS_PROGRAM);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at ELIS program context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_ELIS_PROGRAM.", ep.id
                  FROM {".curriculum::TABLE."} ep
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE ep.id = cx.instanceid AND cx.contextlevel=".CONTEXT_ELIS_PROGRAM.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {".curriculum::TABLE."} ep ON c.instanceid = cc.id
                   WHERE ep.id IS NULL AND c.contextlevel = ".CONTEXT_ELIS_PROGRAM."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at ELIS program context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_ELIS_PROGRAM." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = $emptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
                $emptyclause    = "AND ({context}.path IS NULL OR {context}.depth = 0)";
            }

            $base = '/'.SYSCONTEXTID;

            // Normal top level categories
            $sql = "UPDATE {context}
                       SET depth=2,
                           path=".$DB->sql_concat("'$base/'", 'id')."
                     WHERE contextlevel=".CONTEXT_ELIS_PROGRAM."
                           AND EXISTS (SELECT 'x'
                                         FROM {course_categories} ep
                                        WHERE ep.id = {context}.instanceid)
                           $emptyclause";
            $DB->execute($sql);
        }
    }
}


/**
 * ELIS user context
 */
class context_elis_user extends context {
    /**
     * Please use context_user::instance($userid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_ELIS_USER) {
            throw new coding_exception('Invalid $record->contextlevel in context_elis_user constructor.');
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('user', 'elis_program');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with User
     * @param boolean $short does not apply to user context
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($user = $DB->get_record(user::TABLE, array('id'=>$this->_instanceid, 'deleted'=>0))) {
            if ($withprefix){
                $name = get_string('user', 'elis_program').': ';
            }
            $name .= fullname($user);
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        $params = array(
            's'      => 'usr',
            'action' => 'view',
            'id'     => $this->_instanceid
        );
        return new moodle_url('/elis/program/index.php', $params);
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel = ".CONTEXT_ELIS_USER;

        return $records = $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns ELIS User context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_elis_user context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_ELIS_USER, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_ELIS_USER, 'instanceid'=>$instanceid))) {
            if ($user = $DB->get_record(user::TABLE, array('id'=>$instanceid), 'id', $strictness)) {
                $record = context::insert_context_record(CONTEXT_ELIS_USER, $user->id, '/'.SYSCONTEXTID, 0);
            }
        }

        if ($record) {
            $context = new context_elis_user($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Create missing context instances at ELIS user context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_ELIS_USER.", u.id
                  FROM {".user::TABLE."} u
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE u.id = cx.instanceid AND cx.contextlevel=".CONTEXT_ELIS_USER.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {".user::TABLE."} u ON c.instanceid = u.id
                   WHERE u.id IS NULL AND c.contextlevel = ".CONTEXT_ELIS_USER."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at user context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        // first update normal users
        $sql = "UPDATE {context}
                   SET depth = 2,
                       path = ".$DB->sql_concat("'/".SYSCONTEXTID."/'", 'id')."
                 WHERE contextlevel=".CONTEXT_ELIS_USER;
        $DB->execute($sql);
    }
}

/**
 * ELIS User Set context
 */
class context_elis_userset extends context {
    /**
     * Please use context_coursecat::instance($usersetid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_ELIS_USERSET) {
            throw new coding_exception('Invalid $record->contextlevel in context_userset constructor.');
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('cluster', 'elis_program');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with User Set
     * @param boolean $short does not apply to userset's
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($userset = $DB->get_record(userset::TABLE, array('id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('cluster', 'elis_program').': ';
            }
            $name .= format_string($userset->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        $params = array(
            's'      => 'clst',
            'action' => 'view',
            'id'     => $this->_instanceid
        );
        return new moodle_url('/elis/program/index.php', $params);
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel = ".CONTEXT_ELIS_USERSET;

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns ELIS User Set context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_elis_userset context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_ELIS_USERSET, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_ELIS_USERSET, 'instanceid'=>$instanceid))) {
            if ($userset = $DB->get_record(userset::TABLE, array('id'=>$instanceid), 'id,parent', $strictness)) {
                if ($userset->parent) {
                    $parentcontext = context_elis_userset::instance($userset->parent);
                    $record = context::insert_context_record(CONTEXT_ELIS_USERSET, $userset->id, $parentcontext->path);
                } else {
                    $record = context::insert_context_record(CONTEXT_ELIS_USERSET, $userset->id, '/'.SYSCONTEXTID, 0);
                }
            }
        }

        if ($record) {
            $context = new context_elis_userset($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts of userset and sub-usersets
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_ELIS_USERSET);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at course ELIS User Set context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_ELIS_USERSET.", cc.id
                  FROM {".userset::TABLE."} eu
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE eu.id = cx.instanceid AND cx.contextlevel=".CONTEXT_ELIS_USERSET.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {".userset::TABLE."} eu ON c.instanceid = eu.id
                   WHERE eu.id IS NULL AND c.contextlevel = ".CONTEXT_ELIS_USERSET."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at ELIS User Set context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_ELIS_USERSET." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = $emptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
                $emptyclause    = "AND ({context}.path IS NULL OR {context}.depth = 0)";
            }

            $base = '/'.SYSCONTEXTID;

            // Normal top level user sets
            $sql = "UPDATE {context}
                       SET depth=2,
                           path=".$DB->sql_concat("'$base/'", 'id')."
                     WHERE contextlevel=".CONTEXT_ELIS_USERSET."
                           AND EXISTS (SELECT 'x'
                                         FROM {".userset::TABLE."} eu
                                        WHERE eu.id = {context}.instanceid AND cc.depth=1)
                           $emptyclause";
            $DB->execute($sql);

            // Deeper categories - one query per depthlevel
            $maxdepth = $DB->get_field_sql("SELECT MAX(depth) FROM {".userset::TABLE."}");
            for ($n=2; $n<=$maxdepth; $n++) {
                $sql = "INSERT INTO {context_temp} (id, path, depth)
                        SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                          FROM {context} ctx
                          JOIN {".userset::TABLE."} eu ON (eu.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_ELIS_USERSET." AND eu.depth = $n)
                          JOIN {context} pctx ON (pctx.instanceid = eu.parent AND pctx.contextlevel = ".CONTEXT_ELIS_USERSET.")
                         WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                               $ctxemptyclause";
                $trans = $DB->start_delegated_transaction();
                $DB->delete_records('context_temp');
                $DB->execute($sql);
                context::merge_context_temp_table();
                $DB->delete_records('context_temp');
                $trans->allow_commit();

            }
        }
    }
}

/*
class context_level_elis_curriculum extends context_level_base {
    protected $table = 'crlm_curriculum';

    public function get_context_info($instanceid, $strictness) {
        $basepath  = '/' . SYSCONTEXTID;
        $basedepth = 1;
        $result = true;

        return array($result, $basepath, $basedepth, null);
    }

    public function get_contextlevel_name() {
        return get_string('program', 'elis_program');
    }

    public function get_context_url($context) {
        return new moodle_url('/elis/program/curriculum.php', array('id'=>$context->instanceid));
    }

    public function print_context_name($context, $withprefix = true, $short = false) {
        global $DB;
        $name = '';
        $curriculum = $DB->get_record('crlm_curriculum', array('id' => $context->instanceid));

        if (!empty($curriculum)) {
            if ($withprefix) {
                $name = 'curriculum: ';
            }
            $name .= $curriculum->name;
        }

        return $name;
    }

    public function fetch_context_capabilities_sql($context) {
        $SQL = 'SELECT *
                  FROM {capabilities}';

        return array($SQL, null);
    }

    public function get_child_contexts($context) {
        global $CFG, $DB, $ACCESSLIB_PRIVATE;

        $cache = $ACCESSLIB_PRIVATE->contexcache;

        // Find
        // - tracks
        $trackcontextlevel = context_level_base::get_custom_context_level('track', 'elis_program');
        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ?
                       AND ctx.contextlevel = $trackcontextlevel";
        $params = array("{$context->path}/%");
        $records = $DB->get_recordset_sql($sql, $params);
        $array = array();
        foreach ($records as $rec) {
            $array[$rec->id] = $rec;
            $cache->add($rec);
        }
        return $array;
    }

    public function build_context_path($base, $emptyclause) {
        global $CFG, $DB;

        $a = '{context}';
        eval('$emptyclause = "'.$emptyclause.'";');

        // Curriculum
        $contextlevel = context_level_base::get_custom_context_level('curriculum', 'elis_program');
        $sql = 'UPDATE {context}
                   SET depth=2, path='.$DB->sql_concat("'$base/'", 'id')."
                 WHERE contextlevel = $contextlevel
                       AND EXISTS (SELECT 'x'
                                     FROM {crlm_curriculum} u
                                    WHERE u.id = {context}.instanceid)
                       $emptyclause";
        $DB->execute($sql);
    }
}

class context_level_elis_track extends context_level_base {
    protected $table = 'crlm_track';

    public function get_context_info($instanceid, $strictness) {
        global $CFG, $DB;
        $basepath  = null;
        $basedepth = null;
        $result = true;
        $error_message = null;
        $curcontextlevel = context_level_base::get_custom_context_level('curriculum', 'elis_program');
        $sql = "SELECT ctx.path, ctx.depth
                  FROM {context}       ctx
                  JOIN {crlm_track}    trk
                    ON (trk.curid=ctx.instanceid AND ctx.contextlevel={$curcontextlevel})
                 WHERE trk.id={$instanceid}";
        if ($p = $DB->get_record_sql($sql)) {
            $basepath  = $p->path;
            $basedepth = $p->depth;
        } else if ($trk = $DB->get_record('crlm_track', array('id' => $instanceid), '*', $strictness)) {
            if ($parent = get_context_instance($curcontextlevel, $trk->curid)) {
                $basepath  = $parent->path;
                $basedepth = $parent->depth;
            } else {
                // curriculum does not exist - tracks can not exist without a curriculum
                $error_message = "track ($instanceid) is attached to an invalid curriculum";
                $result = false;
            }
        } else {
            // track does not exist
            $error_message = "incorrect track id ($instanceid)";
            $result = false;
        }

        return array($result, $basepath, $basedepth, $error_message);
    }

    public function get_contextlevel_name() {
        return get_string('track', 'elis_program');
    }

    public function get_context_url($context) {
        return new moodle_url('/elis/program/track.php', array('id'=>$context->instanceid));
    }

    public function print_context_name($context, $withprefix = true, $short = false) {
        global $DB;
        $name = '';
        $track = $DB->get_record('crlm_track', array('id' => $context->instanceid));

        if (!empty($track)) {
            if ($withprefix) {
                $name = 'track: ';
            }
            $name .= $track->name;
        }

        return $name;
    }

    public function fetch_context_capabilities_sql($context) {
        $SQL = 'SELECT *
                  FROM {capabilities}';

        return array($SQL,null);
    }

    public function get_child_contexts($context) {
        //no children by default
        return array();
    }

    public function build_context_path($base, $emptyclause) {
        global $CFG, $DB;

        $a = 'ctx';
        eval('$ctxemptyclause = "'.$emptyclause.'";');

        // Tracks
        $trackcontextlevel = context_level_base::get_custom_context_level('track', 'elis_program');
        $curcontextlevel = context_level_base::get_custom_context_level('curriculum', 'elis_program');
        $sql = 'INSERT INTO {context_temp} (id, path, depth)
                SELECT ctx.id, '.$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                  FROM {context} ctx
                  JOIN {crlm_track} t ON ctx.instanceid=t.id
                  JOIN {context} pctx ON t.curid=pctx.instanceid
                 WHERE ctx.contextlevel=$trackcontextlevel
                       AND pctx.contextlevel=$curcontextlevel
                           AND NOT EXISTS (SELECT 'x'
                                           FROM {context_temp} temp
                                           WHERE temp.id = ctx.id)
                       $ctxemptyclause";
        $DB->execute($sql);

        context_level_base::flush_context_temp();
    }
}


class context_level_elis_course extends context_level_base {
    protected $table = 'crlm_course';

    public function get_context_info($instanceid, $strictness) {
        $basepath  = '/' . SYSCONTEXTID;
        $basedepth = 1;
        $result = true;

        return array($result, $basepath, $basedepth, null);
    }

    public function get_contextlevel_name() {
        return get_string('course', 'elis_program');
    }

    public function get_context_url($context) {
        return new moodle_url('/elis/program/course.php', array('id'=>$context->instanceid));
    }

    public function print_context_name($context, $withprefix = true, $short = false) {
        global $DB;
        $name = '';
        $course = $DB->get_record('crlm_course', array('id' => $context->instanceid));

        if (!empty($course)) {
            if ($withprefix) {
                $name = 'course: ';
            }
            $name .= $course->name;
        }

        return $name;
    }

    public function fetch_context_capabilities_sql($context) {
        $SQL = "SELECT *
                  FROM {capabilities}";

        return array($SQL, null);
    }

    public function get_child_contexts($context) {
        global $CFG, $DB, $ACCESSLIB_PRIVATE;

        $cache = $ACCESSLIB_PRIVATE->contexcache;

        // Find
        // - classes
        $classcontextlevel = context_level_base::get_custom_context_level('class', 'elis_program');
        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ?
                       AND ctx.contextlevel = $classcontextlevel";
        $params = array("{$context->path}/%");
        $records = $DB->get_recordset_sql($sql, $params);
        $array = array();
        foreach ($records as $rec) {
            $array[$rec->id] = $rec;
            $cache->add($rec);
        }
        return $array;
    }

    public function build_context_path($base, $emptyclause) {
        global $CFG, $DB;

        $a = '{context}';
        eval('$emptyclause = "'.$emptyclause.'";');

        // Course
        $contextlevel = context_level_base::get_custom_context_level('course', 'elis_program');
        $sql = 'UPDATE {context}
                   SET depth=2, path='.$DB->sql_concat("'$base/'", 'id')."
                 WHERE contextlevel=$contextlevel
                       AND EXISTS (SELECT 'x'
                                     FROM {crlm_course} u
                                    WHERE u.id = {context}.instanceid)
                       $emptyclause ";
        $DB->execute($sql);
    }
}

class context_level_elis_class extends context_level_base {
    protected $table = 'crlm_class';

    public function get_context_info($instanceid, $strictness) {
        global $CFG, $DB;
        $basepath  = null;
        $basedepth = null;
        $result = true;
        $errormessage = null;
        $crscontextlevel = context_level_base::get_custom_context_level('course', 'elis_program');
        $sql = "SELECT ctx.path, ctx.depth
                  FROM {context}       ctx
                  JOIN {crlm_class}    cls
                    ON (cls.courseid=ctx.instanceid AND ctx.contextlevel={$crscontextlevel})
                 WHERE cls.id={$instanceid}";
        if ($p = $DB->get_record_sql($sql)) {
            $basepath  = $p->path;
            $basedepth = $p->depth;
            $result = true;
        } else if ($cls = $DB->get_record('crlm_class', array('id' => $instanceid), '*', $strictness)) {
            if ($parent = get_context_instance($crscontextlevel, $cls->courseid)) {
                $basepath  = $parent->path;
                $basedepth = $parent->depth;
            } else {
                // course does not exist - classes can not exist without a
                // course
                $errormessage = "class ($instanceid) is attached to an invalid course";
                $result = false;
            }
        } else {
            // class does not exist
            $errormessage = "incorrect class id ($instanceid)";
            $result = false;
            $basepath = '';
            $basedepth = '';
        }

        return array($result, $basepath, $basedepth, $errormessage);
    }

    public function get_context_url($context) {
        return new moodle_url('/elis/program/class.php', array('id'=>$context->instanceid));
    }

    public function get_contextlevel_name() {
        return get_string('class', 'elis_program');
    }

    public function print_context_name($context, $withprefix = true, $short = false) {
        global $DB;
        $name = '';
        $cmclass = $DB->get_record('crlm_class', array('id' => $context->instanceid));

        if (!empty($cmclass)) {
            if ($withprefix) {
                $name = 'class: ';
            }
            $name .= $cmclass->idnumber;
        }

        return $name;
    }

    public function fetch_context_capabilities_sql($context) {
        $SQL = 'SELECT *
                  FROM {capabilities}';

        return array($SQL,null);
    }

    public function get_child_contexts($context) {
        //no children by default
        return array();
    }

    public function build_context_path($base, $emptyclause) {
        global $CFG, $DB;

        $a = 'ctx';
        eval('$ctxemptyclause = "'.$emptyclause.'";');

        // Class
        $classcontextlevel = context_level_base::get_custom_context_level('class', 'elis_program');
        $coursecontextlevel = context_level_base::get_custom_context_level('course', 'elis_program');
        $sql = 'INSERT INTO {context_temp} (id, path, depth)
                SELECT ctx.id, '.$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                  FROM {context} ctx
                  JOIN {crlm_class} c ON ctx.instanceid=c.id
                  JOIN {context} pctx ON c.courseid=pctx.instanceid
                 WHERE ctx.contextlevel=$classcontextlevel
                       AND pctx.contextlevel=$coursecontextlevel
                           AND NOT EXISTS (SELECT 'x'
                                           FROM {context_temp} temp
                                           WHERE temp.id = ctx.id)
                       $ctxemptyclause";
        $DB->execute($sql);

        context_level_base::flush_context_temp();
    }
}

class context_level_elis_user extends context_level_base {
    protected $table = 'crlm_user';

    public function get_context_info($instanceid, $strictness) {
        $basepath  = '/' . SYSCONTEXTID;
        $basedepth = 1;
        $result = true;

        return array($result, $basepath, $basedepth, null);
    }

    public function get_contextlevel_name() {
        return get_string('user', 'elis_program');
    }

    public function get_context_url($context) {
        return new moodle_url('/elis/program/user.php', array('id'=>$context->instanceid));
    }

    public function print_context_name($context, $withprefix = true, $short = false) {
        global $DB;
        $name = '';
        $user = $DB->get_record('crlm_user', array('id' => $context->instanceid));

        if (!empty($user)) {
            if ($withprefix) {
                $name = 'user: ';
            }
            $name .= fullname($user);
        }

        return $name;
    }

    public function fetch_context_capabilities_sql($context) {
        $SQL = 'SELECT *
                  FROM {capabilities}';

        return array($SQL,null);
    }

    public function get_child_contexts($context) {
        //no children by default
        return array();
    }

    public function build_context_path($base, $emptyclause) {
        global $CFG, $DB;

        $a = '{context}';
        eval('$emptyclause = "'.$emptyclause.'";');

        // User
        $contextlevel = context_level_base::get_custom_context_level('user', 'elis_program');
        $sql = 'UPDATE {context}
                   SET depth=2, path='.$DB->sql_concat("'$base/'", 'id')."
                 WHERE contextlevel=$contextlevel
                       AND EXISTS (SELECT 'x'
                                     FROM {crlm_user} u
                                    WHERE u.id = {context}.instanceid)
                       $emptyclause";
        $DB->execute($sql);
    }
}


class context_level_elis_cluster extends context_level_base {
    protected $table = 'crlm_cluster';

    public function get_context_info($instanceid, $strictness) {
        global $CFG, $DB;
        $basepath  = null;
        $basedepth = null;
        $result = true;
        $errormessage = null;
        $contextlevel = context_level_base::get_custom_context_level('cluster', 'elis_program');
        $sql = "SELECT ctx.path, ctx.depth
                  FROM {context}           ctx
                  JOIN {crlm_cluster}      cc
                    ON (cc.parent=ctx.instanceid AND ctx.contextlevel=$contextlevel)
                 WHERE cc.id={$instanceid}";
        if ($p = $DB->get_record_sql($sql)) {
            $basepath  = $p->path;
            $basedepth = $p->depth;
        } else if ($cluster = $DB->get_record('crlm_cluster', array('id' => $instanceid), '*', $strictness)) {
            if (empty($cluster->parent)) {
                // ok - this is a top cluster
                $basepath  = '/' . SYSCONTEXTID;
                $basedepth = 1;
            } else if ($parent = get_context_instance($contextlevel, $cluster->parent)) {
                $basepath  = $parent->path;
                $basedepth = $parent->depth;
            } else {
                // wrong parent cluster - no big deal, this can be fixed later
                $basepath  = null;
                $basedepth = 0;
            }
        } else {
            // incorrect cluster id
            $errormessage = "incorrect cluster id ($instanceid)";
            $result = false;
        }

        return array($result, $basepath, $basedepth, $errormessage);
    }

    public function get_contextlevel_name() {
        return get_string('cluster', 'elis_program');
    }

    public function get_context_url($context) {
        return new moodle_url('/elis/program/cluster.php', array('id'=>$context->instanceid));
    }

    public function print_context_name($context, $withprefix = true, $short = false) {
        global $DB;
        $name = '';
        $cluster = $DB->get_record('crlm_cluster', array('id' => $context->instanceid));

        if (!empty($cluster)) {
            if ($withprefix) {
                $name = 'cluster: ';
            }
            $name .= $cluster->name;
        }

        return $name;
    }

    public function fetch_context_capabilities_sql($context) {
        $SQL = 'SELECT *
                  FROM {capabilities}';

        return $SQL;
    }

    public function get_child_contexts($context) {
        global $CFG, $DB, $ACCESSLIB_PRIVATE;

        $cache = $ACCESSLIB_PRIVATE->contexcache;

        // Find
        // - sub-clusters
        $sql = 'SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ?
                       AND ctx.contextlevel = '.context_level_base::get_custom_context_level('cluster', 'elis_program');
        $params = array("{$context->path}/%");
        $records = $DB->get_recordset_sql($sql);
        $array = array();
        foreach ($records as $rec) {
            $array[$rec->id] = $rec;
            $cache->add($rec);
        }
        return $array;
    }

    public function build_context_path($base, $emptyclause) {
        global $CFG, $DB;

        $a = 'ctx';
        eval('$ctxemptyclause = "'.$emptyclause.'";');
        $a = '{context}';
        eval('$emptyclause = "'.$emptyclause.'";');

        // Cluster
        $contextlevel = context_level_base::get_custom_context_level('cluster', 'elis_program');
        $sql = "UPDATE {context}
                   SET depth=2, path=".$DB->sql_concat("'$base/'", 'id')."
                 WHERE contextlevel=$contextlevel
                       AND EXISTS (SELECT 'x'
                                     FROM {crlm_cluster} u
                                    WHERE u.id = {context}.instanceid
                                      AND u.depth=1)
                       $emptyclause";
        $DB->execute($sql);

        // Deeper clusters - one query per depthlevel
        $maxdepth = $DB->get_field_sql('SELECT MAX(depth) FROM {crlm_cluster}');
        for ($n=2;$n<=$maxdepth;$n++) {
            $sql = 'INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, '.$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {crlm_cluster} c ON ctx.instanceid=c.id
                      JOIN {context} pctx ON c.parent=pctx.instanceid
                     WHERE ctx.contextlevel=$contextlevel
                           AND pctx.contextlevel=$contextlevel
                           AND c.depth=$n
                           AND NOT EXISTS (SELECT 'x'
                                           FROM {context_temp} temp
                                           WHERE temp.id = ctx.id)
                           $ctxemptyclause";
            $DB->execute($sql);

            // this is needed after every loop
            // MDL-11532
            context_level_base::flush_context_temp();
        }
    }
}
*/

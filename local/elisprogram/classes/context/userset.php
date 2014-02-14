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

namespace local_elisprogram\context;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

/**
 * ELIS Userset Context.
 */
class userset extends \local_eliscore\context\base {
    /**
     * Please use \local_elisprogram\context\userset::instance($usersetid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @throws \coding_exception if an invalid context level is passed
     * @param \stdClass $record A DB record from the mdl_context table
     */
    protected function __construct(\stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != \local_eliscore\context\helper::get_level_from_class_name(get_class($this))) {
            throw new \coding_exception('Invalid $record->contextlevel in \local_elisprogram\context\userset constructor.');
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('cluster', 'local_elisprogram');
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
        if ($userset = $DB->get_record(\userset::TABLE, array('id' => $this->_instanceid))) {
            if ($withprefix) {
                $name = get_string('cluster', 'local_elisprogram').': ';
            }
            $name .= format_string($userset->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return \moodle_url An instance of moodle_url
     */
    public function get_url() {
        $params = array(
            's'      => 'clst',
            'action' => 'view',
            'id'     => $this->_instanceid
        );
        return new \moodle_url('/local/elisprogram/index.php', $params);
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array An array of records.
     */
    public function get_capabilities() {
        global $DB;

        // To group them sensibly for display.
        $sort = 'ORDER BY contextlevel,component,name';

        $ctxlevels = array(
                \local_eliscore\context\helper::get_level_from_class_name(get_class($this))
        );
        list($ctxinorequal, $params) = $DB->get_in_or_equal($ctxlevels);

        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel ".$ctxinorequal;

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns ELIS User Set context instance.
     *
     * @static
     * @param int $instanceid The ELIS userset id
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means we will throw an exception if no record or multiple records found.
     * @return \local_elisprogram\context\userset|bool Context instance or false if instance was not found.
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());

        if ($context = \local_eliscore\context\base::cache_get($contextlevel, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel' => $contextlevel, 'instanceid' => $instanceid))) {
            if ($userset = $DB->get_record(\userset::TABLE, array('id' => $instanceid), 'id,parent', $strictness)) {
                if ($userset->parent) {
                    $parentcontext = \local_elisprogram\context\userset::instance($userset->parent);
                    $record = \local_eliscore\context\base::insert_context_record($contextlevel, $userset->id, $parentcontext->path);
                } else {
                    $record = \local_eliscore\context\base::insert_context_record($contextlevel, $userset->id, '/'.SYSCONTEXTID, 0);
                }
            }
        }

        if ($record) {
            $context = new \local_elisprogram\context\userset($record);
            \local_eliscore\context\base::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts of userset and sub-usersets
     *
     * @return \local_elisprogram\context\userset|array A context instance or an empty array
     */
    public function get_child_contexts() {
        global $DB;

        $result = array();
        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_class($this));

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth + 1, $contextlevel);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            $result[$record->id] = \local_eliscore\context\base::create_instance_from_record($record);
        }
        unset($records);

        return $result;
    }

    /**
     * Create missing context instances at course ELIS User Set context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".$contextlevel.", euset.id
                  FROM {".\userset::TABLE."} euset
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE euset.id = cx.instanceid AND cx.contextlevel = ".$contextlevel.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {".\userset::TABLE."} euset ON c.instanceid = euset.id
                   WHERE euset.id IS NULL AND c.contextlevel = ".$contextlevel."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at ELIS User Set context level.
     *
     * @static
     * @param bool $force Set to false to include records whose path is null or depth is zero
     */
    protected static function build_paths($force) {
        global $DB;

        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());

        if ($force or $DB->record_exists_select('context', "contextlevel = ".$contextlevel." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = $emptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
                $emptyclause    = "AND ({context}.path IS NULL OR {context}.depth = 0)";
            }

            $base = '/'.SYSCONTEXTID;

            // Normal top level user sets
            $sql = "UPDATE {context}
                       SET depth = 2,
                           path = ".$DB->sql_concat("'$base/'", 'id')."
                     WHERE contextlevel = ".$contextlevel."
                           AND EXISTS (SELECT 'x'
                                         FROM {".\userset::TABLE."} eu
                                        WHERE eu.id = {context}.instanceid AND eu.depth = 1)
                           $emptyclause";
            $DB->execute($sql);

            // Deeper categories - one query per depthlevel
            $maxdepth = $DB->get_field_sql("SELECT MAX(depth) FROM {".\userset::TABLE."}");
            for ($n = 2; $n <= $maxdepth; $n++) {
                $sql = "INSERT INTO {context_temp} (id, path, depth)
                        SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                          FROM {context} ctx
                          JOIN {".\userset::TABLE."} eu ON (eu.id = ctx.instanceid AND ctx.contextlevel = ".$contextlevel." AND eu.depth = $n)
                          JOIN {context} pctx ON (pctx.instanceid = eu.parent AND pctx.contextlevel = ".$contextlevel.")
                         WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                               $ctxemptyclause";
                $trans = $DB->start_delegated_transaction();
                $DB->delete_records('context_temp');
                $DB->execute($sql);
                \local_eliscore\context\base::merge_context_temp_table();
                $DB->delete_records('context_temp');
                $trans->allow_commit();

            }
        }
    }
}

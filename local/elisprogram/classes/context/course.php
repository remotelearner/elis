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
require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

/**
 * ELIS Course Context.
 */
class course extends \local_eliscore\context\base {
    /**
     * Please use \local_elisprogram\context\course::instance($courseid) if you need the instance of this context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @throws \coding_exception if an invalid context level is passed
     * @param \stdClass $record A DB record from the mdl_context table
     */
    protected function __construct(\stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != \local_eliscore\context\helper::get_level_from_class_name(get_class($this))) {
            throw new \coding_exception('Invalid $record->contextlevel in \local_elisprogram\context\course constructor.');
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('course', 'local_elisprogram');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context
     * @param boolean $short whether to use the short name of the thing.
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        $course = $DB->get_record(\course::TABLE, array('id' => $this->_instanceid));
        if (!empty($course)) {
            if ($withprefix) {
                $name = get_string('course', 'local_elisprogram').': ';
            }
            if ($short) {
                $name .= format_string($course->idnumber, true, array('context' => $this));
            } else {
                $name .= format_string($course->name, true, array('context' => $this));
            }
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
            's'      => 'crs',
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
                \local_eliscore\context\helper::get_level_from_class_name(get_class($this)),
                \local_elisprogram\context\pmclass::get_context_level()
        );
        list($ctxinorequal, $params) = $DB->get_in_or_equal($ctxlevels);

        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel ".$ctxinorequal;

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns ELIS course context instance.
     *
     * @static
     * @param int $instanceid The ELIS course id
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means we will throw an exception if no record or multiple records found.
     * @return \local_elisprogram\context\course|bool Context instance or false if instance was not found.
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());

        if ($context = \local_eliscore\context\base::cache_get($contextlevel, $instanceid)) {
            return $context;
        }

        $record = $DB->get_record('context', array('contextlevel' => $contextlevel, 'instanceid' => $instanceid));
        if (empty($record)) {
            $course = $DB->get_record(\course::TABLE, array('id' => $instanceid), 'id,idnumber', $strictness);
            if (!empty($course)) {
                $parentpath = '/'.SYSCONTEXTID;
                $record = \local_eliscore\context\base::insert_context_record($contextlevel, $course->id, $parentpath);
            }
        }

        if (!empty($record)) {
            $context = new \local_elisprogram\context\course($record);
            \local_eliscore\context\base::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts of course (most likely classes),
     * decendents beyond immediate children are not returned.
     *
     * @return \local_elisprogram\context\course|array A context instance or an empty array
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
     * Create missing context instances at ELIS course context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".$contextlevel.", ecrs.id
                  FROM {".\course::TABLE."} ecrs
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE ecrs.id = cx.instanceid AND cx.contextlevel = ".$contextlevel.")";
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
         LEFT OUTER JOIN {".\course::TABLE."} ecrs ON c.instanceid = ecrs.id
                   WHERE ecrs.id IS NULL AND c.contextlevel = ".$contextlevel."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at ELIS course context level.
     *
     * @static
     * @param bool $force Set to false to include records whose path is null or depth is zero
     */
    protected static function build_paths($force) {
        global $DB;

        $contextlevel = \local_eliscore\context\helper::get_level_from_class_name(get_called_class());

        if ($force or $DB->record_exists_select('context', "contextlevel = ".$contextlevel." AND (depth = 0 OR path IS NULL)")) {

            $emptyclause = ($force) ? '' : "AND ({context}.path IS NULL OR {context}.depth = 0)";
            $base = '/'.SYSCONTEXTID;

            // Normal top level categories
            $sql = "UPDATE {context}
                       SET depth = 2,
                           path = ".$DB->sql_concat("'$base/'", 'id')."
                     WHERE contextlevel = ".$contextlevel."
                           AND EXISTS (SELECT 'x'
                                         FROM {local_elisprogram_crs} ep
                                        WHERE ep.id = {context}.instanceid)
                           $emptyclause";
            $DB->execute($sql);
        }
    }
}

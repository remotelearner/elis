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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');

/**
 * DB logging class used to test the bare minimum functionality of the DB
 * logger parent class
 */
class rlip_dblogger_test extends rlip_dblogger {
    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    function customize_record($record, $filename) {
        //no transformation
        return $record;
    }
}

/**
 * Class for validating generic database logging functionality
 */
class version1ExportDatabaseLoggingTest extends PHPUnit_Framework_TestCase {
    /**
     * Validate that the DB logger uses "0" as the default target start time
     */
    public function testDBLoggeringTargetStartTimeDefaultsToZero() {
        //obtain dblogger
        $dblogger = new rlip_dblogger_test();

        //data validation
        $targetstarttime = $dblogger->get_targetstarttime();
        $this->assertEquals($targetstarttime, 0);
    }

    /**
     * Validate that the DB logger uses the specified value as the target start
     * time
     */
    public function testDBLoggingSupportsTargetStartTimes() {
        //obtain dblogger
        $dblogger = new rlip_dblogger_test();

        //set target start time
        $dblogger->set_targetstarttime(1000000000);

        //data validation
        $targetstarttime = $dblogger->get_targetstarttime();
        $this->assertEquals($targetstarttime, 1000000000);
    }
}
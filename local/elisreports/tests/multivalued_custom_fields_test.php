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
 * @package    local_elisreports
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elis::lib('data/customfield.class.php'));
require_once(elis::lib('data/customfield.class.php'));
require_once($CFG->dirroot.'/local/elisreports/php_report_base.php');
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');

/**
 * Class to test PHP report multivalued custom fields.
 * @group local_elisreports
 */
class multivalued_custom_field_testcase extends elis_database_test {
    /**
     * Data provider method for test_format_default_data
     * @return array The test data - entry format: array(input, expectedoutput)
     */
    public function format_default_data_dataprovider() {
        return array(
                array(0, 0),
                array(1, 1),
                array('scalar', 'scalar'),
                array(array(1, 2, 3), '1, 2, 3'),
                array(array('a', 'b', 'c'), 'a, b, c'),
                array(array('foo', 'bar', 'gizmo'), 'foo, bar, gizmo'),
        );
    }

    /**
     * Method to test table report method: format_default_data()
     * @param mixed $inputdata The input field data, scalar unless multi-valued array
     * @param string $expected The expected output string
     * @dataProvider format_default_data_dataprovider
     * @uses $CFG
     */
    public function test_format_default_data($inputdata, $expected) {
        global $CFG;
        static $report = null;
        if (!$report) {
            require_once($CFG->dirroot.'/local/elisreports/instances/course_progress_summary/course_progress_summary_report.class.php');
            $report = new course_progress_summary_report('test_course_progress_summary');
        }
        $this->assertEquals($expected, $report->format_default_data($inputdata));
    }

    /**
     * Data provider method for test_multiline_get_select_columns()
     * @return array The test data - entry format: array(reportdir)
     */
    public function multiline_reports_dataprovider() {
        return array(
                array('course_progress_summary'),
                array('individual_course_progress'),
                array('user_class_completion'),
                array('user_class_completion_details')
        );
    }

    /**
     * Method to test changes from ELIS-4070 to get_select_columns()
     * @param string $reportdir The report directory under /instances
     * @dataProvider multiline_reports_dataprovider
     * @uses $CFG
     */
    public function test_multiline_get_select_columns($reportdir) {
        global $CFG;
        $rclass = $reportdir.'_report';
        require_once($CFG->dirroot."/local/elisreports/instances/{$reportdir}/{$rclass}.class.php");
        $report = new $rclass('test_'.$reportdir);
        $mlgrps = $report->get_report_sql_multiline_groups();
        if (!empty($mlgrps)) {
            $columns = $report->get_select_columns();
            $this->assertEquals(strlen($columns) - strlen($mlgrps), strpos($columns, $mlgrps));
        }
    }

    /**
     * Data provider method for test_multiline_groupby()
     *     individual_course_progress => enrolid
     *     user_class_completion => uid (, prgid - requires _show_curricula)
     *     user_class_completion_details => clsid
     * @return array the test data - entry format: array(reportdir, lastrec, currectrec, expected)
     */
    public function multiline_groupby_dataprovider() {
        return array(
                // ICPR
                array(
                        'individual_course_progress',
                        array( // lastrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3
                        ),
                        array( // currentrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 1
                        ),
                        false
                ),
                array(
                        'individual_course_progress',
                        array( // lastrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 2
                        ),
                        array( // currentrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 1
                        ),
                        false
                ),
                array(
                        'individual_course_progress',
                        array( // lastrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 4
                        ),
                        array( // currentrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 4
                        ),
                        true
                ),
                array(
                        'individual_course_progress',
                        array( // lastrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 4
                        ),
                        array( // currentrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 20,
                            'userid' => 3,
                            'enrolid' => 5
                        ),
                        false
                ),
                array(
                        'individual_course_progress',
                        array( // lastrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 3,
                            'enrolid' => 4
                        ),
                        array( // currentrec
                            'id' => 1,
                            'name' => 'course_name',
                            'idnumber' => 'class_idnumber',
                            'classid' => 2,
                            'userid' => 30,
                            'enrolid' => 4
                        ),
                        true
                ),
                // UCCR
                array(
                        'user_class_completion',
                        array( // lastrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 1,
                            'userid' => 3
                        ),
                        array( // currentrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 1,
                            'userid' => 3,
                            'uid' => 3,
                        ),
                        false
                ),
                array(
                        'user_class_completion',
                        array( // lastrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 1,
                            'userid' => 3,
                            'uid' => 3,
                        ),
                        array( // currentrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber_2',
                            'curid' => 1,
                            'userid' => 3,
                            'uid' => 3,
                        ),
                        true
                ),
                array(
                        'user_class_completion',
                        array( // lastrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 1,
                            'userid' => 3,
                            'uid' => 3,
                        ),
                        array( // currentrec
                            'id' => 4,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 1,
                            'userid' => 4,
                            'uid' => 4,
                        ),
                        false
                ),
                array(
                        'user_class_completion',
                        array( // lastrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 1,
                            'userid' => 3,
                            'uid' => 3,
                        ),
                        array( // currentrec
                            'id' => 3,
                            'useridnumber' => 'user_idnumber',
                            'curid' => 2,
                            'userid' => 3,
                            'uid' => 3,
                        ),
                        true
                ),
                // UCCDR
                array(
                        'user_class_completion_details',
                        array( // lastrec
                            'id' => 3,
                            'coursename' => 'course_name',
                            'classid' => 'class_idnumber',
                        ),
                        array( // currentrec
                            'id' => 3,
                            'coursename' => 'course_name',
                            'classid' => 'class_idnumber',
                            'clsid' => 2,
                        ),
                        false
                ),
                array(
                        'user_class_completion_details',
                        array( // lastrec
                            'id' => 3,
                            'coursename' => 'course_name',
                            'classid' => 'class_idnumber',
                            'clsid' => 4,
                        ),
                        array( // currentrec
                            'id' => 3,
                            'coursename' => 'course_name',
                            'classid' => 'class_idnumber',
                            'clsid' => 4,
                        ),
                        true
                ),
                array(
                        'user_class_completion_details',
                        array( // lastrec
                            'id' => 3,
                            'coursename' => 'course_name',
                            'classid' => 'class_idnumber',
                            'clsid' => 4,
                        ),
                        array( // currentrec
                            'id' => 3,
                            'coursename' => 'course_name2',
                            'classid' => 'class_idnumber2',
                            'clsid' => 4,
                        ),
                        true
                ),
        );
    }

    /**
     * Method to test table report method: multiline_groupby()
     * @param string $reportdir The report directory under /instances
     * @param array $lastrec The previous report record
     * @param array $currentrec The current report record
     * @param bool $expected The expected return from multiline_groupby()
     * @dataProvider multiline_groupby_dataprovider
     * @uses $CFG
     */
    public function test_multiline_groupby($reportdir, $lastrec, $currentrec, $expected) {
        global $CFG;
        $rclass = $reportdir.'_report';
        require_once($CFG->dirroot."/local/elisreports/instances/{$reportdir}/{$rclass}.class.php");
        $report = new $rclass('test_'.$reportdir);
        $report->init_groupings();
        $this->assertEquals($expected, $report->multiline_groupby((object)$lastrec, (object)$currentrec));
    }

    /**
     * Data provider method for test_append_data()
     * @return array The test data - entry format: array(reportdir, exportformat, lastrec, currectrec, rowdata, expectedrowdata)
     */
    public function append_data_dataprovider() {
        return array(
                // ICPR
                array(
                        'individual_course_progress',
                        php_report::$EXPORT_FORMAT_HTML,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 1
                        ),
                        array(),
                        array(),
                ),
                array(
                        'individual_course_progress',
                        php_report::$EXPORT_FORMAT_HTML,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 2
                        ),
                        array( // rowdata
                            'customfield' => '1'
                        ),
                        array( // expectedrowdata
                            'customfield' => "1<br/>\n2"
                        )
                ),
                array(
                        'individual_course_progress',
                        php_report::$EXPORT_FORMAT_PDF,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 2
                        ),
                        array( // rowdata
                            'customfield' => '1'
                        ),
                        array( // expectedrowdata
                            'customfield' => "1\n2"
                        )
                ),
                array(
                        'individual_course_progress',
                        php_report::$EXPORT_FORMAT_CSV,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 2
                        ),
                        array( // rowdata
                            'customfield' => '1'
                        ),
                        array( // expectedrowdata
                            'customfield' => "1, 2"
                        )
                ),
                array(
                        'user_class_completion',
                        php_report::$EXPORT_FORMAT_HTML,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 1
                        ),
                        array( // rowdata
                            'customfield' => '1'
                        ),
                        array( // expectedrowdata
                            'customfield' => '1'
                        )
                ),
                array(
                        'user_class_completion',
                        php_report::$EXPORT_FORMAT_HTML,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 2
                        ),
                        array( // rowdata
                            'customfield' => "1<br/>\n2"
                        ),
                        array( // expectedrowdata
                            'customfield' => "1<br/>\n2"
                        )
                ),
                array(
                        'user_class_completion',
                        php_report::$EXPORT_FORMAT_PDF,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 2
                        ),
                        array( // rowdata
                            'customfield' => "1\n2"
                        ),
                        array( // expectedrowdata
                            'customfield' => "1\n2"
                        )
                ),
                array(
                        'user_class_completion_details',
                        php_report::$EXPORT_FORMAT_CSV,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 2
                        ),
                        array( // rowdata
                            'customfield' => "1, 2"
                        ),
                        array( // expectedrowdata
                            'customfield' => "1, 2"
                        )
                ),
                array(
                        'user_class_completion_details',
                        php_report::$EXPORT_FORMAT_HTML,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 3
                        ),
                        array( // rowdata
                            'customfield' => "1<br/>\n2"
                        ),
                        array( // expectedrowdata
                            'customfield' => "1<br/>\n2<br/>\n3"
                        )
                ),
                array(
                        'user_class_completion_details',
                        php_report::$EXPORT_FORMAT_HTML,
                        array( // lastrec
                            'customfield' => 1
                        ),
                        array( // currentrec
                            'customfield' => 3
                        ),
                        array( // rowdata
                            'customfield' => "1<br/>\n2<br/>\n3"
                        ),
                        array( // expectedrowdata
                            'customfield' => "1<br/>\n2<br/>\n3"
                        )
                ),
        );
    }

    /**
     * Method to test table report method: append_data()
     * @param string $reportdir The report directory under /instances
     * @param string $exportformat The export format
     * @param array $lastrec The previous report record
     * @param array $currentrec The current report record
     * @param array $rowdata The current row data
     * @param array $expectedrowdata The row data after processing recs
     * @dataProvider append_data_dataprovider
     * @uses $CFG
     */
    public function test_append_data($reportdir, $exportformat, $lastrec, $currentrec, $rowdata, $expectedrowdata) {
        global $CFG;
        $rclass = $reportdir.'_report';
        require_once($CFG->dirroot."/local/elisreports/instances/{$reportdir}/{$rclass}.class.php");
        $report = new $rclass('test_'.$reportdir);
        $rowdata = (object)$rowdata;
        $report->append_data($rowdata, (object)$lastrec, (object)$currentrec, $exportformat);
        $this->assertEquals((object)$expectedrowdata, $rowdata);
    }
}

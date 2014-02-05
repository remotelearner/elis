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
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

/**
 * NOTE: This file only tests all ELIS custom context calls made by ELIS reports. It should not be used to verify
 * other report operations or data accuracy.
 */

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elis::lib('data/customfield.class.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');

/**
 * Class to test PHP report contexts.
 * @group local_elisreports
 */
class reports_contexts_testcase extends elis_database_test {
    /**
     * Load test data from CSV file.
     */
    protected function load_userset_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            userset::TABLE => elis::component_file('elisprogram', 'tests/fixtures/userset.csv')
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Test course usage summary report.
     * @uses $CFG
     */
    public function test_course_usage_summary() {
        global $CFG;
        require_once(dirname(__FILE__).'/../instances/course_usage_summary/course_usage_summary_report.class.php');
        $report = new course_usage_summary_report('test_course_summary');
        $filters = $report->get_filters();
        if (!empty($report->checkboxes_filter->options['choices'])) {
            foreach ($report->checkboxes_filter->options['choices'] as $choice => $desc) {
                $result = $report->get_average_test_score($choice);
                $resultisnumeric = is_numeric(substr($result, 0, -1));
                $this->assertTrue($resultisnumeric);
            }
        }
    }

    /**
     * Test course completion by cluster report.
     * @uses $CFG
     */
    public function test_course_completion_by_cluster() {
        global $CFG;
        $this->load_userset_csv_data();
        require_once(dirname(__FILE__).'/../instances/course_completion_by_cluster/course_completion_by_cluster_report.class.php');
        $report = new course_completion_by_cluster_report('test_course_completion_by_cluster');

        // Test context in get_report_sql.
        $columns = $report->get_select_columns();
        $sql = $report->get_report_sql($columns);
        $this->assertArrayHasKey(0, $sql);
        $this->assertNotEmpty($sql[0]);
        $this->assertArrayHasKey(1, $sql);
        $this->assertNotEmpty($sql[1]);

        // Test context in transform_grouping_header_label.
        $grouping = new stdClass;
        $grouping->field = 'cluster.id';
        $grouping->label = 'Test';
        $groupingcurrent['cluster.id'] = 1;
        $datum = new stdClass;
        $datum->cluster = 1;
        $result = $report->transform_grouping_header_label($groupingcurrent, $grouping, $datum, php_report::$EXPORT_FORMAT_LABEL);
        $this->assertNotEmpty($result);
    }

    /**
     * Test course progress summary report.
     * @uses $CFG
     */
    public function test_course_progress_summary() {
        global $CFG;
        require_once(dirname(__FILE__).'/../instances/course_progress_summary/course_progress_summary_report.class.php');
        $report = new course_progress_summary_report('test_course_progress_summary');

        // Test context in get_columns.
        $columns = $report->get_columns();
        $this->assertNotEmpty($columns);
    }

    /**
     * Test individual course progress report.
     * @uses $CFG
     */
    public function test_individual_course_progress() {
        global $CFG;
        require_once(dirname(__FILE__).'/../instances/individual_course_progress/individual_course_progress_report.class.php');
        $report = new individual_course_progress_report('test_individual_course_progress');

        // Test context in get_columns.
        $columns = $report->get_columns();
        $this->assertNotEmpty($columns);

        // Test context in get_max_test_score_sql.
        $fields = array('_elis_course_pretest', '_elis_course_posttest');
        foreach ($fields as $field) {
            $report->get_max_test_score_sql($field);
        }
    }
}

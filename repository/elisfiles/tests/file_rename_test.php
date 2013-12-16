<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');

/**
 * Class for the renaming of file names
 * @group repository_elisfiles
 */
class repository_elisfiles_file_rename_testcase extends PHPUnit_Framework_TestCase {

    /**
     * Data provider for validating that files are properly renamed
     * The first set is the filename to be transformed and the second set is the result
     * The third set contains the filenames that already exist
     * @return array data needed for testing
     */
    public function file_rename_provider() {
        return array(
                array('file_1.txt', 'file_2.txt', array()),
                array('file_1_1.txt', 'file_1_2.txt', array()),
                array('file1_1.txt', 'file1_2.txt', array()),
                array('file_1_1_1.txt', 'file_1_1_2.txt', array()),
                array('file_2.txt', 'file_3.txt', array()),
                array('file_1', 'file_2', array()),
                array('file_10', 'file_11', array()),
                array('file_1_1', 'file_1_2', array()),
                array('file', 'file_1', array()),
                array('file2', 'file2_1', array()),
                array('file2_a_1_b', 'file2_a_1_b_1', array()),
                array('file_a_b', 'file_a_b_1', array()),
                array('file_a_b.txt', 'file_a_b_1.txt', array()),
                array('file_a_b_.txt', 'file_a_b__1.txt', array()),
                array('file_a_b_', 'file_a_b__1', array()),
                array('_file_a', '_file_a_1', array()),
                array('_file_a.txt', '_file_a_1.txt', array()),
                array('__file_a_1.txt', '__file_a_2.txt', array()),
                array('file_1', 'file_3', (object) array('files' => array((object) array('title' => 'file_2', 'uuid' => true)))),
                array('file_1.txt', 'file_3.txt', (object) array('files' => array((object) array('title' => 'file_2.txt', 'uuid' => true)))),
                array(
                        'file',
                        'file_2',
                        (object) array(
                            'files' => (object) array(
                                    (object) array('title' => 'file_1', 'uuid' => true),
                                    (object) array('title' => 'file_3', 'uuid' => true))
                        )
                    ),
                array('file_1_1.txt', 'file_1_3.txt', (object) array('files' => array((object) array('title' => 'file_1_2.txt', 'uuid' => true)))),
                array('file_1_1.txt',
                    'file_1_4.txt',
                    (object) array(
                        'files' => array(
                                (object) array(
                                    'title' => 'file_1_2.txt',
                                    'uuid' => true),
                                    (object) array(
                                        'title' => 'file_1_3.txt',
                                        'uuid' => true
                                    )
                                )
                    )
                ),
                array(
                    'file_1',
                    'file_5',
                    (object) array(
                        'files' => array(
                                (object) array(
                                    'title' => 'file_3',
                                    'uuid' => true),
                                    (object) array(
                                        'title' => 'file_2',
                                        'uuid' => true),
                                        (object) array(
                                            'title' => 'file_4',
                                            'uuid' => true
                                        )
                                )
                    )
                ),
                array('_file', '_file_2', (object) array('files' => array((object) array('title' => '_file_1', 'uuid' => true))))
            );
    }

    /**
     * Validate that files are renamed properly
     * @dataProvider file_rename_provider
     * @uses $CFG
     * @param string $actual the actual file name
     * @param string $expected the expected file name
     * @param array|object a datastructure describing currently existing files
     */
    public function test_file_rename($actual, $expected, $listing) {
        global $CFG;
        require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');
        $result = elis_files_generate_unique_filename($actual, $listing);
        $this->assertEquals($expected, $result);
    }
}
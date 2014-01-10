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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/curriculum.class.php'));
    require_once(elispm::lib('data/curriculumcourse.class.php'));
    require_once(elispm::lib('data/course.class.php'));
    require_once(elispm::lib('data/coursetemplate.class.php'));
    require_once(elispm::lib('data/pmclass.class.php'));
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/userset.class.php'));
    require_once(elispm::lib('data/track.class.php'));
}

// Handy constants for readability.
define('ELIS_ENTITY_EXISTS', true);
define('ELIS_ENTITY_DOESNOT_EXIST', false);

// Readable defs for test_setup_data array index.
define('TEST_SETUP_COURSE', 0);
define('TEST_SETUP_CURRICULUM', 1);
define('TEST_SETUP_TRACK', 2);
define('TEST_SETUP_CLASS', 3);
define('TEST_SETUP_CLUSTER', 4);
global $notestsetup;
$notestsetup = array();

/**
 * Test importing entities
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_entity_import_testcase extends rlip_elis_test {

    public $contexttotable = array(
        'course'     => 'local_elisprogram_crs',
        'curr'       => 'local_elisprogram_pgm',
        'curriculum' => 'local_elisprogram_pgm',
        'track'      => 'local_elisprogram_trk',
        'class'      => 'local_elisprogram_cls',
        'cluster'    => 'local_elisprogram_uset'
    );

    public $testsetupdata = array(
            array(
                'action'     => 'create',
                'context'    => 'course',
                'idnumber'   => 'courseidnumber',
                'name'       => 'coursename'
            ),
            array(
                'action'     => 'create',
                'context'    => 'curriculum',
                'idnumber'   => 'programidnumber',
                'name'       => 'programname'
            ),
            array(
                'action'     => 'create',
                'context'    => 'track',
                'idnumber'   => 'trackidnumber',
                'name'       => 'trackname',
                'assignment' => 'programidnumber' // Program/Curriculum idnumber.
            ),
            array(
                'action'     => 'create',
                'context'    => 'class',
                'idnumber'   => 'classidnumber',
                'assignment' => 'courseidnumber', // Course Description idnumber.
                'maxstudents'=> 0
            ),
            array(
                'action'     => 'create',
                'context'    => 'cluster',
                'name'       => 'usersetname',
                'display'    => 'usersetdescription'
            )
    );

    /**
     * Test data provider
     *
     * @return array the test data
     */
    public function dataproviderfortests() {
        global $notestsetup;

        $testdata = array();

        // Course create - no idnumber.
        $testdata[] = array(
            'create',
            'course',
            array(
                'name' => 'coursename'
            ),
            $notestsetup,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Course create - no name.
        $testdata[] = array(
            'create',
            'course',
            array(
                'idnumber' => 'courseidnumber',
            ),
            $notestsetup,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Course create - ok!.
        $testdata[] = array(
            'create',
            'course',
            array(
                'idnumber' => 'courseidnumber',
                'name' => 'coursename',
            ),
            $notestsetup,
            ELIS_ENTITY_EXISTS
        );

        // Course create - all fields - ok!.
        $testdata[] = array(
            'create',
            'course',
            array(
                'idnumber' => 'courseidnumber',
                'name' => 'coursename',
                'code' => 'coursecode',
                'syllabus' => 'course syllabus',
                'lengthdescription'=> 'Length Description',
                'length' => '100',
                'credits' => '7.5',
                'completion_grade'=> '65',
                'cost' => '$355.80',
                'version' => '1.01',
                'assignment' => 'programidnumber',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Course update - no id.
        $testdata[] = array(
            'update',
            'course',
            array(
                'name'        => 'coursenamechanged1'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Course update - ok!.
        $testdata[] = array(
            'update',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
                'name'        => 'coursenamechanged2',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Course update - all fields being updated.
        $testdata[] = array(
            'update',
            'course',
            array(
                'idnumber'         => 'courseidnumber',
                'name'             => 'coursename_update',
                'code'             => 'coursecode_update',
                'syllabus'         => 'course syllabus - update',
                'lengthdescription'=> 'Years',
                'length'           => '2',
                'credits'          => '10.75',
                'completion_grade' => '75',
                'cost'             => '$499.99',
                'version'          => '2.5',
                'assignment'       => 'programidnumber',
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Course delete - no id.
        $testdata[] = array(
            'delete',
            'course',
            array(
                'name'        => 'coursename'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Course delete - ok.
        $testdata[] = array(
            'delete',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Curriculum create - no id.
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'name'        => 'programname'
            ),
            $notestsetup,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Curriculum create - no name.
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
            ),
            $notestsetup,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Curriculum create - ok!.
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname',
            ),
            $notestsetup,
            ELIS_ENTITY_EXISTS
        );

        // Curriculum create - all fields - ok!.
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname',
                'description' => 'Program Description',
                'reqcredits'  => '7.5',
                'timetocomplete'=> '1h,2d,3w,4m,5y',
                'frequency'   => '6h,7d,8w,9m,1y',
                'priority'    => '2'
            ),
            $notestsetup,
            ELIS_ENTITY_EXISTS
        );

        // Curriculum update - no id.
        $testdata[] = array(
            'update',
            'curriculum',
            array(
                'name'        => 'programnamechanged1'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Curriculum update - ok!.
        $testdata[] = array(
            'update',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programnamechanged2',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Curriculum Update - all fields being updated.
        $testdata[] = array(
            'update',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname_update',
                'description' => 'Program Description - Update',
                'reqcredits'  => '5.0',
                'timetocomplete'=> '3h,4d,1w,5m,2y',
                'frequency'   => '6m,1y',
                'priority'    => '1'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Curriculum delete - no id.
        $testdata[] = array(
            'delete',
            'curriculum',
            array(
                'name'        => 'programname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Curriculum delete - ok.
        $testdata[] = array(
            'delete',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Curriculum as curr create - ok!.
        $testdata[] = array(
            'create',
            'curr',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname',
            ),
            $notestsetup,
            ELIS_ENTITY_EXISTS
        );

        // Track create - no assignment.
        $testdata[] = array(
            'create',
            'track',
            array(
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Track create - no name.
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Track create - no idnumber.
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Track create - ok!.
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Track create - all-fields ok!.
        $testdata[] = array(
            'create',
           'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'description' => 'Track Description',
                'startdate'   => 'Jan/13/2012',
                'enddate'     => 'Jun/13/2012',
                'autocreate'  => 'yes',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Track create - date format YYYY.MM.DD.
        $testdata[] = array(
            'create',
           'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'startdate'   => '2000.12.25',
                'enddate'     => '2001.01.02',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Track create - date format DD-MM-YYYY.
        $testdata[] = array(
            'create',
           'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'startdate'   => '25-12-2000',
                'enddate'     => '24-11-2001',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Track create - date format MM/DD/YYYY.
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'startdate'   => '12/25/2000',
                'enddate'     => '11/24/2001',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Track update - no id.
        $testdata[] = array(
            'update',
            'track',
            array(
                'name'        => 'tracknamechanged1'
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Track update - ok!.
        $testdata[] = array(
            'update',
            'track',
            array(
                'idnumber'    => 'trackidnumber',
                'name'        => 'tracknamechanged2',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_EXISTS
        );

        // Track delete - no id.
        $testdata[] = array(
            'delete',
            'track',
            array(
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_EXISTS
        );

        // Track delete - ok.
        $testdata[] = array(
            'delete',
            'track',
            array(
                'idnumber'    => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Class create - no assignment.
        $testdata[] = array(
            'create',
            'class',
            array(
                'idnumber'    => 'classidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Class create - no idnumber.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Class create - ok!.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class create - all fields - ok!.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => 'Jan/13/2012',
                'enddate'     => 'Jun/13/2012',
                'starttimehour'=> '13',
                'starttimeminute'=> '15',
                'endtimehour'=> '14',
                'endtimeminute'=> '25',
                'maxstudents' => '35',
                'enrol_from_waitlist'=> 'yes',
                'track'       => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_COURSE, TEST_SETUP_TRACK),
            ELIS_ENTITY_EXISTS
        );

        // Class create - date format YYYY.MM.DD.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => '2000.12.25',
                'enddate'     => '2001.11.24'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class create - date format DD-MM-YYYY.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => '25-12-2000',
                'enddate'     => '20-11-2001'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class create - date format MM/DD/YYYY.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => '12/25/2000',
                'enddate'     => '11/20/2001'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = 0.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 0
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = no.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'no'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = 1.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 1
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = 'yes'.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'yes'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - starttime and endtime out of range (all set to 0).
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'      => 'courseidnumber',
                'idnumber'        => 'classidnumber',
                'starttimehour'   => 0,
                'starttimeminute' => 0,
                'endtimehour'     => 0,
                'endtimeminute'   => 0
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - starttime and endtime out of range.
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'      => 'courseidnumber',
                'idnumber'        => 'classidnumber',
                'starttimehour'   => 61,
                'starttimeminute' => 61,
                'endtimehour'     => 61,
                'endtimeminute'   => 61
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Class update - no idnumber.
        $testdata[] = array(
            'update',
            'class',
            array(
                'maxstudents' => 101,
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Class update - ok!.
        $testdata[] = array(
            'update',
            'class',
            array(
                'idnumber'    => 'classidnumber',
                'maxstudents' => 100,
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - all fields being updated.
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'startdate'           => 'Jun/7/2012',
                'enddate'             => 'Aug/31/2012',
                'starttimehour'       => '8',
                'starttimeminute'     => '45',
                'endtimehour'         => '10',
                'endtimeminute'       => '15',
                'maxstudents'         => '45',
                'enrol_from_waitlist' => 'no',
                'track'               => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_COURSE, TEST_SETUP_TRACK, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = 0.
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 0
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = no.
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'no'
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = 1.
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 1
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = 'yes'.
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'yes'
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - starttime out of range (i.e. unset).
        $testdata[] = array(
            'update',
            'class',
            array(
                'idnumber'        => 'classidnumber',
                'starttimehour'   => 61,
                'starttimeminute' => 61,
                'endtimehour'     => 61,
                'endtimeminute'   => 61
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class delete - no id.
        $testdata[] = array(
            'delete',
            'class',
            array(
                'maxstudents' => 0
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class delete - ok.
        $testdata[] = array(
            'delete',
            'class',
            array(
                'idnumber'    => 'classidnumber',
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Cluster create - no name.
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'display'     => 'usersetdescription',
            ),
            $notestsetup,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Cluster create - ok!.
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetname',
            ),
            $notestsetup,
            ELIS_ENTITY_EXISTS
        );

        // Cluster Create - minimal fields.
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname'
            ),
            $notestsetup,
            ELIS_ENTITY_EXISTS
        );

        // Cluster create - all fields - ok!.
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname',
                'display'     => 'Userset C Description',
                'parent'      => 'usersetname',
                'recursive'   => 0
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster Create - parent field = "top".
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname',
                'display'     => 'Userset C Description',
                'parent'      => 'top',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster Create - parent field = "usersetname".
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname',
                'display'     => 'Userset C Description',
                'parent'      => 'usersetname',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster update - no name.
        $testdata[] = array(
            'update',
            'cluster',
            array(
                'display'     => 'usersetdescription2',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Cluster update - ok!.
        $testdata[] = array(
            'update',
            'cluster',
            array(
                'name'        => 'usersetname',
                'display'     => 'usersetdescription3',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );
        // Cluster delete - no name.
        $testdata[] = array(
            'delete',
            'cluster',
            array(
                'display'     => 'usersetdescription',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster Delete - minimal fields.
        $testdata[] = array(
            'delete',
            'cluster',
            array(
                'name'     => 'usersetname',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // Cluster delete - ok.
        $testdata[] = array(
            'delete',
            'cluster',
            array(
                'name'        => 'usersetname',
                'recursive'   => 'yes'
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        return $testdata;
    }

    /**
     * Field mapping function to convert IP boolean column to user DB field
     *
     * @param array  $input    The input IP data fields
     * @param string $fieldkey The array key to check for boolean strings
     */
    public function map_bool_field(&$input, $fieldkey) {
        if (isset($input[$fieldkey])) {
            if ($input[$fieldkey] == 'no') {
                $input[$fieldkey] = '0';
            } else if ($input[$fieldkey] == 'yes') {
                $input[$fieldkey] = '1';
            }
        }
    }

    /**
     * Field mapping function to convert IP date columns to timestamp DB field
     *
     * @param array  $input    The input IP data fields
     * @param string $fieldkey The array key to check for date strings
     */
    public function map_date_field(&$input, $fieldkey) {
        if (isset($input[$fieldkey])) {
            $date = $input[$fieldkey];

            // Determine which case we are in.
            if (strpos($date, '/') !== false) {
                $delimiter = '/';
            } else if (strpos($date, '-') !== false) {
                $delimiter = '-';
            } else if (strpos($date, '.') !== false) {
                $delimiter = '.';
            } else {
                return false;
            }

            $parts = explode($delimiter, $date);

            if ($delimiter == '/') {
                // MMM/DD/YYYY or MM/DD/YYYY format.
                list($month, $day, $year) = $parts;

                $months = array('jan', 'feb', 'mar', 'apr',
                                'may', 'jun', 'jul', 'aug',
                                'sep', 'oct', 'nov', 'dec');
                $pos = array_search(strtolower($month), $months);
                if ($pos !== false) {
                    $month = $pos + 1;
                }
            } else if ($delimiter == '-') {
                // DD-MM-YYYY format.
                list($day, $month, $year) = $parts;
            } else {
                // YYYY.MM.DD format.
                list($year, $month, $day) = $parts;
            }

            $timestamp = rlip_timestamp(0, 0, 0, $month, $day, $year);
            $input[$fieldkey] = $timestamp;
        }
    }

    /**
     * Class mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_class($input, $shouldexist) {
        global $DB;
        if (array_key_exists('assignment', $input)) {
            $input['courseid'] = $DB->get_field(course::TABLE, 'id', array('idnumber' => $input['assignment']));
            unset($input['assignment']);
        }
        $this->map_date_field($input, 'startdate');
        $this->map_date_field($input, 'enddate');

        if (array_key_exists('starttimehour', $input)) {
            if ($input['starttimehour'] >= 25) {
                $input['starttimehour'] = 0;
            }
        }

        if (array_key_exists('starttimeminute', $input)) {
            if ($input['starttimeminute'] >= 61) {
                $input['starttimeminute'] = 0;
            }
        }

        if (array_key_exists('endtimehour', $input)) {
            if ($input['endtimehour'] >= 25) {
                $input['endtimehour'] = 0;
            }
        }

        if (array_key_exists('endtimeminute', $input)) {
            if ($input['endtimeminute'] >= 61) {
                $input['endtimeminute'] = 0;
            }
        }

        if (array_key_exists('track', $input)) {
            if ($shouldexist) {
                $this->assertFalse(!$DB->get_record(track::TABLE, array('idnumber' => $input['track'])));
            }
            unset($input['track']);
        }
        $this->map_bool_field($input, 'autoenrol');
        if (array_key_exists('autoenrol', $input)) {
            // TBD: verify autoenrol ok???.
            unset($input['autoenrol']);
        }
        $this->map_bool_field($input, 'enrol_from_waitlist');
        if (array_key_exists('track', $input)) {
            // TBD: test valid.
            unset($input['track']);
        }
        return $input;
    }

    /**
     * Cluster mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_cluster($input, $shouldexist) {
        global $DB;
        if (array_key_exists('parent', $input)) {
            if ($input['parent'] == 'top') {
                unset($input['parent']);
                $input['parent'] = '0';
            } else {
                $input['parent'] = $DB->get_field(userset::TABLE, 'id', array('name' => $input['parent']));
            }
        }
        $this->map_bool_field($input, 'recursive');
        if (array_key_exists('recursive', $input)) {
            // TBD.
            unset($input['recursive']);
        }
        return $input;
    }

    /**
     * Course mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_course($input, $shouldexist) {
        global $DB;
        if (array_key_exists('assignment', $input)) {
            $this->assertEquals($shouldexist, $DB->record_exists(curriculumcourse::TABLE, array()));
            unset($input['assignment']);
        }
        if (array_key_exists('syllabus', $input)) {
            $where  = $DB->sql_compare_text('syllabus').' = ?';
            $params = array(substr($input['syllabus'], 0, 32));
            $this->assertEquals($shouldexist, $DB->record_exists_select(course::TABLE, $where, $params));
            unset($input['syllabus']);
        }
        if (array_key_exists('link', $input)) {
            $mdlcourseid = $DB->get_field('course', 'id', array('shortname' => $input['link']));
            $this->assertEquals($shouldexist && $mdlcourseid !== false,
                    $DB->record_exists(coursetemplate::TABLE, array('location' => $mdlcourseid)));
            unset($input['link']);
        }
        return $input;
    }

    /**
     * Curriculum mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_curriculum($input, $shouldexist) {
        global $DB;
        if (array_key_exists('description', $input)) {
            $where  = $DB->sql_compare_text('description').' = ?';
            $params = array(substr($input['description'], 0, 32));
            $this->assertEquals($shouldexist, $DB->record_exists_select(curriculum::TABLE, $where, $params));
            unset($input['description']);
        }
        return $input;
    }

    /**
     * Track mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_track($input, $shouldexist) {
        global $DB;
        if (array_key_exists('assignment', $input)) {
            $input['curid'] = $DB->get_field(curriculum::TABLE, 'id', array('idnumber' => $input['assignment']));
            unset($input['assignment']);
        }
        $this->map_bool_field($input, 'autocreate');
        if (array_key_exists('autocreate', $input)) {
            // TBD: verify autocreate ok???.
            unset($input['autocreate']);
        }
        $this->map_date_field($input, 'startdate');
        $this->map_date_field($input, 'enddate');
        if (array_key_exists('description', $input)) {
            $where  = $DB->sql_compare_text('description').' = ?';
            $params = array(substr($input['description'], 0, 32));
            $this->assertEquals($shouldexist, $DB->record_exists_select(track::TABLE, $where, $params));
            unset($input['description']);
        }
        return $input;
    }

    /**
     * User import test cases
     *
     * @uses $DB
     * @dataProvider dataproviderfortests
     */
    public function test_elis_entity_import($action, $context, $entitydata, $setuparray, $entityexists) {
        global $CFG, $DB;

        if (empty($context)) {
            $this->markTestSkipped("\nPHPunit test coding error, 'context' NOT set - skipping!\n");
            return;
        }

        $file = get_plugin_directory('dhimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        set_config('enable_curriculum_expiration', true, 'local_elisprogram');

        $importdata = array(
            'action'  => $action,
            'context' => $context
        );
        foreach ($entitydata as $key => $value) {
            $importdata[$key] = $value;
        }

        try {
            foreach ($setuparray as $index) {
                $provider = new rlipimport_version1elis_importprovider_mockcourse($this->testsetupdata[$index]);
                $importplugin = new rlip_importplugin_version1elis($provider);
                @$importplugin->run();
            }

            $provider = new rlipimport_version1elis_importprovider_mockcourse($importdata);
            $importplugin = new rlip_importplugin_version1elis($provider);
            $importplugin->run();
        } catch (Exception $e) {
            mtrace("\nException in test_elis_entity_import(): ".$e->getMessage()."\n");
        }

        // Call any mapping functions to transform IP column to DB field.
        $mapfcn = 'map_'.$context;
        if (method_exists($this, $mapfcn)) {
            $entitydata = $this->$mapfcn($entitydata, $entityexists);
        }

        ob_start();
        var_dump($entitydata);
        $tmp = ob_get_contents();
        ob_end_clean();

        $crlmtable = $this->contexttotable[$context];
        ob_start();
        var_dump($DB->get_records($crlmtable));
        $crlmtabledata = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($entityexists, $DB->record_exists($crlmtable, $entitydata),
                "ELIS entity assertion: [mapped]entity_data ; {$crlmtable} = {$tmp} ; {$crlmtabledata}");
    }
}

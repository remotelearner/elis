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
 * @subpackage programmanager
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

$string['add'] = 'Add';
$string['add_class'] = 'Add Class';
$string['add_course'] = 'Add Course';
$string['add_pmclass'] = 'Add Class';
$string['add_user'] = 'Add user';
$string['added_to_waitlist_message'] = 'you have been added to the waitlist for class {$a->idnumber}';
$string['adding_completion_element'] = 'Adding Completion Element';
$string['address'] = 'Address';
$string['address2'] = 'Address 2';
$string['admin_dashboard'] = 'Administrator Dashboard';
$string['autocreate'] = 'Auto Create Moodle course from template';

$string['browse'] = 'Browse';

$string['class'] = 'Class';
$string['classreportlinks'] = 'Reports';
$string['class_assigntrackhead'] = 'Assigned Tracks';
$string['class_attached_course'] = 'This class is already attached to the Moodle course';
$string['class_course'] = 'Course';
$string['class_enddate'] = 'End Date';
$string['class_endtime'] = 'End Time';
$string['class_idnumber'] = 'ID Number';
$string['class_maxstudents'] = 'Max # of Students';
$string['class_moodle_course'] = 'Moodle Course';
$string['class_startdate'] = 'Start Date';
$string['class_starttime'] = 'Start Time';
$string['class_unassigntrackhead'] = 'Unassigned Tracks';
$string['clear'] = 'Clear';
$string['completionform:completion_grade'] = 'Completion grade';
$string['completionform:completion_grade_help'] = '<p>Minimum grade the learner must received to identify the element as &ldquo;completed&rdquo;.</p>';
$string['completionform:course_idnumber'] = 'ID Number';
$string['completionform:course_idnumber_help'] = '<p>When an element is an activity within Moodle, this number should correspond to the id number of that activity in Moodle.</p>';
$string['completionform:course_name'] = 'Name';
$string['completionform:course_name_help'] = '<p>Element name.  Should correspond with the name of the element in Moodle, when Moodle is being used.</p>';
$string['completionform:course_syllabus'] = 'Description';
$string['completionform:course_syllabus_help'] = '<p>Description information about the element.</p>';
$string['completionform:required'] = 'Required';
$string['completionform:required_help'] = '<p>Is the element a required to complete the course? Some elements may be optional and therefore not required for course completion.</p>';
$string['completion_description'] = 'Description';
$string['completion_elements'] = 'Completion Elements';
$string['completion_idnumber'] = 'ID Number';
$string['completion_grade'] = 'Completion grade';
$string['completion_name'] = 'Name';
$string['completion_status'] = 'Completion Status';
$string['confirm_delete_class'] = 'Are you sure you want to delete the class {$a->idnumber}?';
$string['confirm_delete_completion'] = 'Are you sure you want to delete the completion element \"name: {$a}\"?';
$string['confirm_delete_course'] = 'Are you sure you want to delete the course named {$a->name} (ID number: {$a->idnumber})?';
$string['confirm_delete_pmclass'] = 'Are you sure you want to delete the class {$a->idnumber}?';
$string['confirm_delete_user'] = 'Are you sure you want to delete the user named {$a->firstname} {$a->lastname} (ID number: {$a->idnumber})?';
$string['cost'] = 'Cost';
$string['course'] = 'Course';
$string['courseform:completion_grade'] = 'Completion grade';
$string['courseform:completion_grade_help'] = '<p>Minimum grade to complete/pass the course.</p>';
$string['courseform:cost'] = 'Cost';
$string['courseform:cost_help'] = '<p>Registration fee (if any).</p>';
$string['courseform:course_idnumber'] = 'ID Number';
$string['courseform:course_idnumber_help'] = '<p>Course ID number; may be the same as the code, may be something compoletely different. This number can contain numbers, letters, spaces and special characters and will show up on reports.</p>';
$string['courseform:course_name'] = 'Name';
$string['courseform:course_name_help'] = '<p>Name of course. A course may have many &ldquo;classes&rdquo; (or sometimes called sections). This is the name of the parent course.</p>';
$string['courseform:course_syllabus'] = 'Description';
$string['courseform:course_syllabus_help'] = '<p>Course description.</p>';
$string['courseform:course_code'] = 'Code';
$string['courseform:course_code_help'] = '<p>Roughly corresponds to the &ldquo;short name&rdquo; in Moodle.</p>';
$string['courseform:course_version'] = 'Version';
$string['courseform:course_version_help'] = '<p>Version of course being used. If you update your courses periodically, the update date could be entered here.</p>';
$string['courseform:coursetemplate'] = 'Course Template in Moodle';
$string['courseform:coursetemplate_help'] = '<p>Moodle course template/master course, if applicable. This field is only necessary if master Moodle courses are being used to create new instances of courses. This allows the Moodle course template to automatically be copied as a new course when a new instance of a track is created.</p>';
$string['courseform:credits'] = 'Credits';
$string['courseform:credits_help'] = '<p>Number of credits the course is worth. If credits are not being used, this field can be left blank.</p>';
$string['courseform:curriculum'] = 'Curriculum';
$string['courseform:curriculum_help'] = '<p>Select the curriculum the course will be assigned to.  A course can be assigned to more than one curricula by contol click.</p>';
$string['courseform:duration'] = 'Duration';
$string['courseform:duration_help'] = '<p>Number of units the course runs.</p>';
$string['courseform:environment'] = 'Environment';
$string['courseform:environment_help'] = '<p>Where/how is the course delivered. Select from the choices available.  If no enviroments have been entered into the system, go to Curriculum Administration block &gt; Information Elements &gt; Environments to enter environment options, such as online, face-to-face, blended, etc.</p>';
$string['courseform:length_description'] = 'Length Description';
$string['courseform:length_description_help'] = '<p>Defines the units for duration, such as days, weeks, months, semesters, etc.</p>';
$string['course_classes'] = 'Classes';
$string['course_curricula'] = 'Curricula';
$string['course_code'] = 'Code';
$string['course_curricula'] = 'Curricula';
$string['course_idnumber'] = 'ID Number';
$string['course_name'] = 'Name';
$string['course_syllabus'] = 'Description';
$string['course_version'] = 'Version';
$string['coursetemplate'] = 'Course Template in Moodle';
$string['credits'] = 'Credits';
$string['curriculum'] = 'Curriculum';

$string['delete'] = 'Delete';
$string['delete_class'] = 'Delete Class';
$string['delete_course'] = 'Delete Course';
$string['delete_label'] = 'Delete';
$string['delete_pmclass'] = 'Delete Class';
$string['delete_user'] = 'Delete user';
$string['deleting_completion_element'] = 'Deleting Completion Element';
$string['detail'] = 'Detail';
$string['duration'] = 'Duration';

$string['edit'] = 'Edit';
$string['editing_completion_element'] = 'Editing Completion Element';
$string['elispmversion'] = '<strong>ELIS Program Manager Version:</strong> {$a}';
$string['elis_doc_class_link'] = '<strong>Documentation for ELIS</strong> &mdash; we have over 200
pages of documentation for ELIS in our <a href="http://training.remote-learner.net/course/view.php?id=1090">ELIS Support Course</a>.
You can access this course by logging in as a guest.  If you have problems
accessing this course, please contact your sales representative.';
$string['email2'] = 'Email address 2';
$string['enrolment'] = 'Enrollment';
$string['enrolments'] = 'Enrollments';
$string['environment'] = 'Environment';
$string['error_date_range'] = 'Start date must be before the end date.';
$string['error_duration'] = 'Start time must be before the end time.';
$string['error_n_overenrol'] = 'The over enrol capability is required for this';

$string['failclustcpycls'] = 'Failed to copy class with idnumber {$a->idnumber}';
$string['failclustcpycurrcrs'] = 'Failed to copy course {$a->name}';
$string['fax'] = 'Fax';
$string['female'] = 'Female';

$string['health_check_link'] = 'The <a href="{$a}/curriculum/index.php?s=health">ELIS health page</a> may help diagnose potential problems with the site.';

$string['id'] = 'ID';
$string['idnumber_already_used'] = 'ID Number is already in use';
$string['id_same_as_user'] = 'Same as username';
$string['inactive'] = 'Inactive';
$string['instructors'] = 'Instructors';

$string['learningplan'] = 'Learning Plan';
$string['length_description'] = 'Length Description';

$string['makecurcourse'] = 'Make a curriculum for this course';
$string['male'] = 'Male';
$string['manage_class'] = 'Manage Classes';
$string['manage_course'] = 'Manage Courses';
$string['manage_pmclass'] = 'Manage Classes';
$string['manage_student'] = 'Manage Students';
$string['manage_user'] = 'Manage users';
$string['moodlecourse'] = 'Moodle course';

$string['moodleenrol'] = 'You have been removed from the waiting list for class {$a->class->idnumber}.
Please visit {$a->wwwroot}/course/enrol.php?id={$a->crs->id} to complete your enrolment.';
$string['moodleenrol_subj'] = 'Ready to enrol in {$a->class->idnumber}.';

$string['no_completion_elements'] = 'There are no completion elements defined.';
$string['no_items_matching'] = 'No items matching ';
$string['no_moodlecourse'] = 'No Moodle courses on this site';
$string['none'] = 'None';
$string['notice_class_deleted'] = 'Deleted the class {$a->idnumber}';
$string['notice_course_deleted'] = 'Deleted the course named {$a->name} (ID number: {$a->idnumber})';
$string['notice_pmclass_deleted'] = 'Deleted the class {$a->idnumber}';
$string['notifycourserecurrencemessagedef'] = "%%%%userenrolname%%%% is due to re-take the course %%%%coursename%%%%.";
$string['nouser'] = 'No user found for specified user id.';
$string['nowenroled'] = 'You have been removed from the waiting list and placed in class {$a->idnum}.';
$string['num_class_found'] = '{$a} class(es) found';
$string['num_course_found'] = '{$a} course(s) found';
$string['num_pmclass_found'] = '{$a} class(es) found';
$string['num_user_found'] = '{$a} users found';

$string['o_active'] = 'Only active';
$string['o_inactive'] = 'Only inactive';

$string['phone2'] = 'Phone 2';
$string['pmclassform:class_idnumber'] = 'ID Number';
$string['pmclassform:class_idnumber_help'] = '<p>Class ID number.</p>';
$string['pmclassform:class_startdate'] = 'Start Date';
$string['pmclassform:class_startdate_help'] = '<p>Enter the course start and end date, if applicable.</p>';
$string['pmclassform:class_starttime'] = 'Start Time';
$string['pmclassform:class_starttime_help'] = '<p>Enter the course start and end time, if applicable.  This is appropriate for synchronous online sessions, as well as face-to-face classes.</p>';
$string['pmclassform:class_maxstudents'] = 'Max # of Students';
$string['pmclassform:class_maxstudents_help'] = '';
$string['pmclassform:class_unassigntrackhead'] = 'Unassigned Tracks';
$string['pmclassform:class_unassigntrackhead_help'] = '<p>If tracks have been created in the system, tracks will be displayed here. If this class should be included in a track, select the appropriate track.</p>';
$string['pmclassform:course'] = 'Course';
$string['pmclassform:course_help'] = '<p>Select the course this class is an instance of. The drop down menu will show all courses created in the system.</p>';
$string['pmclassform:environment'] = 'Environment';
$string['pmclassform:environment_help'] = '<p>Select the appropriate environment from the drop down menu. If no
environments have been entered into the system, they can be entered by going to
Curriculum Administration &gt; Information Elements &gt; Environments.</p>';
$string['pmclassform:moodlecourse'] = 'Moodle course';
$string['pmclassform:moodlecourse_help'] = '<p>The Moodle course that this class is attached to and is an instance of.</p>';
$string['pmclassform:waitlistenrol'] = 'Auto enrol from waitlist';
$string['pmclassform:waitlistenrol_help'] = '<p>on to automatically enrol students from the waitlist into the course when an erolled student completes (passes or fails) the course.</p>';
$string['pmclass_delete_warning'] = 'Warning!  Deleting this class will also delete all stored enrollment information for the class.';
$string['pmclass_delete_warning_continue'] = 'I understand all enrollments for the class will be deleted, continue ...';
$string['postalcode'] = 'Postal code';
$string['progman'] = 'Program Manager';

$string['registered_date'] = 'Registered date';
$string['required'] = 'Required';

$string['showinactive'] = 'Show inactive';

$string['tags'] = 'Tags';
$string['timecreated'] = 'Creation time';
$string['transfercredits'] = 'Transfer credits';

$string['userbirthdate'] = 'Birth date';
$string['usergender'] = 'Gender';
$string['usermi'] = 'Middle initials';
$string['useridnumber'] = 'ID number';
$string['useridnumber_help'] = 'An id number is a unique value used to identify you within your organization.
It also serves as way to tie Curriculum Management users to Moodle users.';

$string['waiting'] = 'Waiting';
$string['waitlist'] = 'waitlist';
$string['waitlistenrol'] = 'Auto enrol from waitlist';


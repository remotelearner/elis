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
$string['add_class'] = 'Add Class Instance';
$string['add_coreq'] = 'Add co-requisites';
$string['add_coreq_to_curriculum'] = 'Add co-requisites to program';
$string['add_course'] = 'Add Course Description';
$string['add_curriculum'] = 'Add Program';
$string['add_grade'] = 'Add Grade';
$string['add_pmclass'] = 'Add Class Instance';
$string['add_prereq'] = 'Add prerequisites';
$string['add_prereq_to_curriculum'] = 'Add prerequisites to curriculum';
$string['add_to_waitinglist'] = 'Add {$a->name}({$a->username}) to wait list?';
$string['add_track'] = 'Add Track';
$string['add_user'] = 'Add user';
$string['add_userset'] = 'Add User Set';
$string['added_corequisite'] = 'Added <b>{$a}</b> corequisite';
$string['added_corequisites'] = 'Added <b>{$a}</b> corequisites';
$string['added_prerequisite'] = 'Added <b>{$a}</b> prerequisite';
$string['added_prerequisites'] = 'Added <b>{$a}</b> prerequisites';
$string['added_to_waitlist_message'] = 'you have been added to the waitlist for class instance {$a->idnumber}';
$string['adding_completion_element'] = 'Adding Learning Objective';
$string['address'] = 'Address';
$string['address2'] = 'Address 2';
$string['admin_dashboard'] = 'Administrator Dashboard';
$string['all_items_assigned'] = 'All available items assigned.';
$string['assign'] = 'Assign';
$string['assign_selected'] = 'Assign Selected';
$string['assigned'] = 'Assigned';
$string['assigntime'] = 'Assigned Time';
$string['association_clustercurriculum'] = 'Associate Userset';
$string['association_clustertrack'] = 'Associate Userset';
$string['association_curriculumcourse'] = 'Associate Program';
$string['association_info_group'] = 'Association Information';
$string['association_instructor'] = 'Associate Instructor';
$string['association_student'] = 'Associate Student'; // TBD
$string['association_trackassignment'] = 'Associate Track';
$string['association_usertrack'] = 'Associate User';
$string['autocreate'] = 'Auto Create Moodle course from template';
$string['auto_collapse_setting'] = 'Number of curricula to display before automatically collapsing';
$string['auto_create_help'] = 'Moodle courses that are linked to ELIS classes are marked as having been auto-created or created manually since ELIS 1.8.7. For courses created prior to 1.8.7, the auto-created status is unknown. This setting indicates whether these courses should be treated as having been auto-created or not.

Currently, this only affects the functionality for copying curricula to a cluster.';
$string['auto_create_setting'] = 'Moodle courses with unknown status treated as auto-created';
$string['auto_create_settings'] = 'Auto-create Settings';
$string['auto_idnumber_help'] = 'Automatically set a Moodle user\'s ID number to be the same as his/her username if he/she does not have one already set.

If a Moodle user does not have an ID number, then no corresponding user will be created in the Curriculum Management system.

However, changing a user\'s ID number may result in duplicate users within the Curriculum Management system, so this option should be turned off if users will be created in Moodle before they are assigned a permanent ID number.

In general, this option should be set to off unless a user\'s ID number will always be the same as his/her username. However, the default is on for backwards compatibility.';
$string['auto_idnumber_setting'] = 'Automatically assign an ID number to Moodle users without one';
$string['available_course_corequisites'] = 'Available Course Corequisites';
$string['available_course_prerequisites'] = 'Available Course Prerequisites';

// TBD: associationpage.class.php::get_title_default()
$string['breadcrumb_trackassignmentpage'] = 'Assign Class Instances';
$string['breadcrumb_usertrackpage'] = 'Assign Tracks';
$string['breadcrumb_trackuserpage'] = 'Assign Users';
$string['breadcrumb_userclusterpage'] = 'Assign User Sets';
$string['breadcrumb_clusteruserpage'] = 'Assign Users';
$string['breadcrumb_clustertrackpage'] = 'Assign Tracks';
$string['breadcrumb_trackclusterpage'] = 'Assign User Sets';
$string['breadcrumb_clustercurriculumpage'] = 'Assign Programs';
$string['breadcrumb_curriculumclusterpage'] = 'Assign User Sets';
$string['breadcrumb_coursecurriculumpage'] = 'Assign Programs';
$string['breadcrumb_curriculumcoursepage'] = 'Assign Course Descriptions';
$string['breadcrumb_studentpage'] = 'Assign Students';
$string['breadcrumb_instructorpage'] = 'Assign Instructors';
$string['breadcrumb_studentcurriculumpage'] = 'Assign Programs';
$string['breadcrumb_curtaginstancepage'] = 'Assign Tags';
$string['breadcrumb_clstaginstancepage'] = 'Assign Tags';
$string['breadcrumb_crstaginstancepage'] = 'Assign Tags';
$string['breadcrumb_curriculumstudentpage'] = 'Assign Students';
$string['breadcrumb_waitlistpage'] = 'Waiting List';
$string['browse'] = 'Browse';
$string['bulkedit_select_all'] = 'Select All';

$string['cert_border_help'] = 'The certificate border image is what gets displayed as the background for certificates in the curriculum.
You can add more border images by uploading them to your moodledata directory under the directory: TBD/pix/certificate/borders/';
$string['cert_border_setting'] = 'Certificate border image';
$string['cert_seal_help'] = 'The certificate seal image is what gets displayed as the logo on certificates in the curriculum.
You can add more seal images by uploading them to your moodledata directory under the directory: TBD/pix/certificate/seals/';
$string['cert_seal_setting'] = 'Certificate seal image';
$string['certificates'] = 'Certificates';
$string['class'] = 'Class Instance';
$string['classreportlinks'] = 'Reports';
$string['class_assigntrackhead'] = 'Assigned Tracks';
$string['class_attached_course'] = 'This class instance is already attached to the Moodle course';
$string['class_course'] = 'Course Description';
$string['class_enddate'] = 'End Date';
$string['class_endtime'] = 'End Time';
$string['class_idnumber'] = 'ID Number';
$string['class_maxstudents'] = 'Max # of Students';
$string['class_moodle_course'] = 'Moodle Course';
$string['class_role_help'] = 'This is the default role to assign to a Curriculum Management user in any classes they create.
This type of role assignment will not take place for a particular class if that user is already permitted to edit that class.
To disable this functionality, select "N/A" from the list.';
$string['class_role_setting'] = 'Default Class Role';
$string['class_startdate'] = 'Start Date';
$string['class_starttime'] = 'Start Time';
$string['class_unassigntrackhead'] = 'Unassigned Tracks';
$string['clear'] = 'Clear';
$string['cluster'] = 'User Set';
$string['clusters'] = 'User Sets';
$string['cluster_grp_settings'] = 'Cluster Group Settings';
$string['cluster_role_help'] = 'This is the default role to assign to a Curriculum Management user in any clusters they create.
This type of role assignment will not take place for a particular cluster if that user is already permitted to edit that cluster.
To disable this functionality, select "N/A" from the list.';
$string['cluster_role_setting'] = 'Default Cluster Role';
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
$string['completion_elements'] = 'Learning Objectives';
$string['completion_idnumber'] = 'ID Number';
$string['completion_grade'] = 'Completion grade';
$string['completion_name'] = 'Name';
$string['completion_status'] = 'Completion Status';
$string['completion_time'] = 'Completion Time';
$string['confirm_delete_association'] = 'Are you sure you want to delete this entry?';
$string['confirm_delete_category'] = 'Are you sure you want to delete the category named "{$a->name}"?  This will delete all fields in that category.';
$string['confirm_delete_class'] = 'Are you sure you want to delete the class instance {$a->idnumber}?';
$string['confirm_delete_completion'] = 'Are you sure you want to delete the learning objective "name: {$a->name}"?';
$string['confirm_delete_course'] = 'Are you sure you want to delete the course description named {$a->name} (ID number: {$a->idnumber})?';
$string['confirm_delete_curriculum'] = 'Are you sure you want to delete the program named {$a->name} (ID number: {$a->idnumber})?';
$string['confirm_delete_field'] = 'Are you sure you want to delete the {$a->datatype} field named "{$a->name}"?';
$string['confirm_delete_instructor'] = 'Are you sure you want to delete the instructor "name: {$a->name}"?';
$string['confirm_delete_pmclass'] = 'Are you sure you want to delete the class instance {$a->idnumber}?';
$string['confirm_delete_track'] = 'Are you sure you want to delete the track named {$a->idnumber}?';
$string['confirm_delete_user'] = 'Are you sure you want to delete the user named {$a->firstname} {$a->lastname} (ID number: {$a->idnumber})?';
$string['confirm_delete_userset'] = 'Are you sure you want to delete the user set named {$a->name}?';
$string['confirm_delete_with_usersubsets'] = 'Are you sure you want to delete the user set named {$a->name}?  This user set has {$a->subclusters} subset(s).';
$string['confirm_delete_with_usersubsets_and_descendants'] = 'Are you sure you want to delete the user set named {$a->name}?  This user set has $a->subclusters subset(s) and $a->descendants other descendant user sets.';
$string['confirm_waitlist'] = 'Are you sure to {$a->action} {$a->num} entries in the waiting list?';
$string['corequisites'] = 'Corequisite';
$string['cost'] = 'Cost';
$string['country'] = 'Country';
$string['course'] = 'Course Description';
$string['courses'] = 'Course Descriptions';
$string['courseform:completion_grade'] = 'Completion grade';
$string['courseform:completion_grade_help'] = '<p>Minimum grade to complete/pass the course description.</p>';
$string['courseform:cost'] = 'Cost';
$string['courseform:cost_help'] = '<p>Registration fee (if any).</p>';
$string['courseform:course_idnumber'] = 'ID Number';
$string['courseform:course_idnumber_help'] = '<p>Course Description ID number; may be the same as the code, may be something compoletely different. This number can contain numbers, letters, spaces and special characters and will show up on reports.</p>';
$string['courseform:course_name'] = 'Name';
$string['courseform:course_name_help'] = '<p>Name of course description. A course description may have many &ldquo;class instances&rdquo; (or sometimes called sections). This is the name of the parent course description.</p>';
$string['courseform:course_syllabus'] = 'Description';
$string['courseform:course_syllabus_help'] = '<p>Course description.</p>';
$string['courseform:course_code'] = 'Code';
$string['courseform:course_code_help'] = '<p>Roughly corresponds to the &ldquo;short name&rdquo; in Moodle.</p>';
$string['courseform:course_version'] = 'Version';
$string['courseform:course_version_help'] = '<p>Version of course description being used. If you update your course descriptions periodically, the update date could be entered here.</p>';
$string['courseform:coursetemplate'] = 'Course Template in Moodle';
$string['courseform:coursetemplate_help'] = '<p>Moodle course template/master course, if applicable. This field is only necessary if master Moodle courses are being used to create new instances of courses. This allows the Moodle course template to automatically be copied as a new course when a new instance of a track is created.</p>';
$string['courseform:credits'] = 'Credits';
$string['courseform:credits_help'] = '<p>Number of credits the course description is worth. If credits are not being used, this field can be left blank.</p>';
$string['courseform:curriculum'] = 'Program';
$string['courseform:curriculum_help'] = '<p>Select the program the course description will be assigned to.  A course description can be assigned to more than one program by contol click.</p>';
$string['courseform:duration'] = 'Duration';
$string['courseform:duration_help'] = '<p>Number of units the course descriptions runs.</p>';
$string['courseform:environment'] = 'Environment';
$string['courseform:environment_help'] = '<p>Where/how is the course description delivered. Select from the choices available.  If no enviroments have been entered into the system, go to Program Administration block &gt; Information Elements &gt; Environments to enter environment options, such as online, face-to-face, blended, etc.</p>';
$string['courseform:length_description'] = 'Length Description';
$string['courseform:length_description_help'] = '<p>Defines the units for duration, such as days, weeks, months, semesters, etc.</p>';
$string['course_assigncurriculum'] = 'Assign Program';
$string['course_classes'] = 'Class Instances';
$string['course_curricula'] = 'Programs';
$string['course_code'] = 'Code';
$string['course_curricula'] = 'Programs';
$string['course_idnumber'] = 'ID Number';
$string['course_name'] = 'Name';
$string['course_role_help'] = 'This is the default role to assign to a Curriculum Management user in any courses they create.
This type of role assignment will not take place for a particular course if that user is already permitted to edit that course.
To disable this functionality, select "N/A" from the list.';
$string['course_role_setting'] = 'Default Course Role';
$string['course_syllabus'] = 'Description';
$string['course_version'] = 'Version';
$string['coursetemplate'] = 'Course Template in Moodle';
$string['credits'] = 'Credits';
$string['credits_rec'] = 'Credits Rec\'vd.';
$string['crlm_admin_blk_settings'] = 'Curriculum Administration Block Settings';
$string['crlm_expire_setting'] = 'Enable curriculum expiration';
$string['curricula'] = 'Programs';
$string['curriculaform:curriculum_description'] = 'Long description';
$string['curriculaform:curriculum_description_help'] = '<p>Description information about the program. A complete and thorough
description will help administrators, teachers and students know if this program is correct for them.</p>';
$string['curriculaform:curriculum_idnumber'] = 'ID Number';
$string['curriculaform:curriculum_idnumber_help'] = '<p>Program ID number. This number will display in reports.</p>';
$string['curriculaform:curriculum_name'] = 'Name';
$string['curriculaform:curriculum_name_help'] = '<p>Name of program.</p>';
$string['curriculaform:expiration'] = 'Expiration';
$string['curriculaform:expiration_help'] = '<p>The date on which credit for the program expires.<br/>
For example, 4y = every four years.<br/>
If the user\'s credit for the given program does not expire, this field should be left blank.<br/><br/>
The expiration date is printed on the Program report, the Individual User report, and on program certificates.<br/>
This expiration is informational only - credit is not removed from the system, but the student\'s transcript and certificate show the expiration date.</p>';
$string['curriculaform:priority'] = 'Display priority';
$string['curriculaform:priority_help'] = '<p>Determines the order in which programs are displayed from an enrolled user\'s perspective.
The lower the priority number, the higher the program will display on the user\'s listing.</p>';
$string['curriculaform:required_credits'] = 'Required Credits';
$string['curriculaform:required_credits_help'] = '<p>Number of credits the learner must receive before the program is
complete.  Each course description can be assigned a credit value. If credits are not being used this field can be left blank.</p>';
$string['curriculaform:time_to_complete'] = 'Time to complete';
$string['curriculaform:time_to_complete_help'] = '<p>The time a learner has to complete the program once they have been
assigned to the program. For example, 18m = 18 months. If there is not a time limit for the program, this field can be left blank.</p>';
$string['curriculum'] = 'Program';
$string['curriculumcourse'] = 'Course Description';
$string['curriculumcourse_assigncourse'] = 'Assign course description';
$string['curriculumcourse_position'] = 'Position';
$string['curriculumcourseform:course'] = 'Course Description';
$string['curriculumcourseform:course_help'] = '<p>The name of the Program Administration course description being associated with a program.</p>';
$string['curriculumcourseform:curriculum'] = 'Program';
$string['curriculumcourseform:curriculum_help'] = '<p>The name of the program being associated with a course description.</p>';
$string['curriculumcourseform:frequency'] = 'Frequency';
$string['curriculumcourseform:frequency_help'] = '<p>The frequency the course description must be repeated, if necessary.  For example,
4y = every four years. If the course description does not need to be repeated periodically, this field should be left blank.</p>
<p>This field is for information only, and does not affect the behaviour of the system.</p>';
$string['curriculumcourseform:position'] = 'Position';
$string['curriculumcourseform:position_help'] = '<p>Determines the order in which course descriptions are listed within this program. Course descriptions with lower position numbers are displayed first.</p>';
$string['curriculumcourseform:required'] = 'Required';
$string['curriculumcourseform:required_help'] = '<p>If enabled, completion of the associated course description is required in order for students to complete the selected program.</p>';
$string['curriculumcourseform:time_period'] = 'Timepreriod';
$string['curriculumcourseform:time_period_help'] = '<p>The units used in specifying the course description frequency.</p>';
$string['curriculum_expire_enrol_start'] = 'enrolled into a curriculum';
$string['curriculum_expire_enrol_complete'] = 'completed a curriculum';

$string['curriculum_idnumber'] = 'ID Number';
$string['curriculum_description'] = 'Long description';
$string['curriculum_name'] = 'Name';
$string['curriculum_reqcredits'] = ' Required Credits';
$string['curriculum_role_help'] = 'This is the default role to assign to a Curriculum Management user in any curricula they create.
This type of role assignment will not take place for a particular curriculum if that user is already permitted to edit that curriculum.
To disable this functionality, select "N/A" from the list.';
$string['curriculum_role_setting'] = 'Default Curriculum Role';
$string['curriculum_shortdescription'] = 'Short description';

$string['date_completed'] = 'Date Completed';
$string['default_role_settings'] = 'Default Role Assignments Settings';
$string['delete'] = 'Delete';
$string['deleted_corequisite'] = 'Deleted <b>{$a}</b> corequisite';
$string['deleted_corequisites'] = 'Deleted <b>{$a}</b> corequisites';
$string['deleted_prerequisite'] = 'Deleted <b>{$a}</b> prerequisite';
$string['deleted_prerequisites'] = 'Deleted <b>{$a}</b> prerequistes';
$string['delete_class'] = 'Delete Class Instance';
$string['delete_course'] = 'Delete Course Description';
$string['delete_curriculum'] = 'Delete Program';
$string['delete_label'] = 'Delete';
$string['delete_pmclass'] = 'Delete Class Instance';
$string['delete_track'] = 'Delete Track';
$string['delete_user'] = 'Delete user';
$string['delete_userset'] = 'Delete User Set';
$string['deleting_completion_element'] = 'Deleting Learning Objective';
$string['description'] = 'Description';
$string['detail'] = 'Detail';
$string['disable_cert_setting'] = 'Disable Certificates';
$string['duration'] = 'Duration';

$string['edit'] = 'Edit';
$string['editing_completion_element'] = 'Editing Learning Objective';
$string['edit_cancelled'] = 'Edit cancelled';
$string['edit_course_corequisites'] = 'Edit Course Description Corequisites';
$string['edit_course_prerequisites'] = 'Edit Course Description Prerequisites';
$string['edit_student_attendance'] = 'Edit Student Attendance';
$string['elis_config'] = 'ELIS Configuration';
$string['elis_doc_class_link'] = '<strong>Documentation for ELIS</strong> &mdash; we have over 200
pages of documentation for ELIS in our <a href="http://training.remote-learner.net/course/view.php?id=1090">ELIS Support Course</a>.
You can access this course by logging in as a guest.  If you have problems
accessing this course, please contact your sales representative.';
$string['elis_settings'] = 'ELIS Settings';
$string['elispmversion'] = '<strong>ELIS Program Manager Version:</strong> {$a->version}';
$string['email'] = 'Email address';
$string['email2'] = 'Email address 2';
$string['enrol'] = 'Enrol'; // TBD: Enroll ?
$string['enrol_elis_help'] = 'If this setting is set, then ELIS will not enrol a user in an ELIS class that is linked with a Moodle class that uses an enrolment plugin other than the ELIS enrolment plugin.

In brief, if an ELIS class is:

* Not linked to a Moodle class: it is enrollable.
* Linked to a Moodle class that uses the ELIS enrolment plugin: it is enrollable.
* Linked to a Moodle class that does not use the ELIS enrolment plugin: it is not enrollable.

This does not affect enrolments from Moodle.';
$string['enrol_elis_setting'] = 'Only allow enrolments to Moodle courses that use the ELIS plugin';
$string['enrol_select_all'] = 'Select All';
$string['enrol_selected'] = 'Enrol Selected';
$string['enrole_sync_settings'] = 'Enrolment Role Sync Settings';
$string['enrolment'] = 'Enrollment';
$string['enrolment_time'] = 'Enrollment Time';
$string['enrolments'] = 'Enrollments';
$string['enrolstudents'] = 'Enrol Student'; // TBD (s) ?
$string['environment'] = 'Environment';
$string['error_date_range'] = 'Start date must be before the end date.';
$string['error_duration'] = 'Start time must be before the end time.';
$string['error_n_overenrol'] = 'The over enrol capability is required for this';
$string['error_not_timeformat'] = 'time not in proper format';
$string['error_not_durrationformat'] = 'durration not in proper format';
$string['error_not_using_elis_enrolment'] = 'The associated Moodle course is not using the ELIS enrolment plugin';
$string['error_waitlist_remove'] = 'Error removing users from waitlist.';
$string['error_waitlist_overenrol'] = 'Error over enrolling.';
$string['errorroleassign'] = 'Failed to assign role to Moodle course.';
$string['errorsynchronizeuser'] = 'Could not create an associated Moodle user.';
$string['existing_course_corequisites'] = 'Existing Course Description Corequisites';
$string['existing_course_prerequisites'] = 'Existing Course Description Prerequisites';
$string['exit'] = 'Exit';
$string['expiration'] = 'Expiration';
$string['expire_basis_setting'] = 'Calculate curriculum expiration based on the time a student';

$string['failclustcpycls'] = 'Failed to copy class instance with idnumber {$a->idnumber}';
$string['failclustcpycurr'] = 'Program {$a->name} failed to copy';
$string['failclustcpycurrcrs'] = 'Failed to copy course description {$a->name}';
$string['failed'] = 'Failed';
$string['fax'] = 'Fax';
$string['female'] = 'Female';
$string['field_category_deleted'] = 'Deleted category {$a->name}';
$string['field_category_saved'] = 'Saved category {$a->name}';
$string['field_create_category'] = 'Create a new category';
$string['field_create_new'] = 'Create a new field';
$string['field_confirm_force_resync'] = 'Are you sure you want to force re-synchronization of custom user profile data with Moodle at this time?  This normally does not need to be done, but can be a useful function if the data is out of sync for any reason.  This may take a long time, depending on the number of users in the site.';
$string['field_datatype'] = 'Data type';
$string['field_datatype_bool'] = 'Boolean (yes/no)';
$string['field_datatype_char'] = 'Short text';
$string['field_datatype_int'] = 'Integer';
$string['field_datatype_num'] = 'Decimal number';
$string['field_datatype_text'] = 'Long text';
$string['field_deleted'] = 'Field successfully deleted';
$string['field_force_resync'] = 'Force re-sync of custom profile fields';
$string['field_from_moodle'] = 'Create field from Moodle field';
$string['field_multivalued'] = 'Multivalued';
$string['field_no_categories_defined'] = 'No categories defined';
$string['field_no_fields_defined'] = 'No fields defined';
$string['field_no_sync'] = 'No synchronization';
$string['field_resyncing'] = 'Please wait.  Resynchronizing data with Moodle.';
$string['field_saved'] = 'Field saved';
$string['field_sync_from_moodle'] = 'Use values from Moodle';
$string['field_sync_to_moodle'] = 'Copy values to Moodle';
$string['field_syncwithmoodle'] = 'Sync with Moodle';
$string['form_error'] = 'Selection page form error - expecting array!';
$string['fp_grp_cluster_help'] = 'Enabling these setting allows the Curriculum Management system to automatically add users to groups in Moodle courses based on cluster membership. Groups will be created as needed.

For this to work, the associated cluster setting must be turned on for each appropriate cluster as well.

Also, be cautious when enabling these setting, as it will cause the Curriculum Management system to immediately search for all appropriate users across all necessary clusters, which may take a long time.';
$string['fp_grp_cluster_setting'] = 'Allow front page grouping creation from cluster-based groups';
$string['fp_pop_clusters_setting'] = 'Allow front page group population from clusters';
$string['frequency'] = 'Frequency';

$string['grp_pop_cluster_setting'] = 'Allow course-level group population from clusters';

$string['health_checking'] = "Checking...\n<ul>\n";
$string['health_check_link'] = 'The <a href="{$a->wwwroot}/elis/program/index.php?s=health">ELIS health page</a> may help diagnose potential problems with the site.';
$string['health_cluster_orphans'] = 'Orphaned clusters found!';
$string['health_cluster_orphansdesc'] = 'There are {$a->count} sub-clusters which have had their parent clusters deleted.<br/><ul>';
$string['health_cluster_orphansdescnone'] = 'There were no orphaned clusters found.';
$string['health_cluster_orphanssoln'] = 'From the command line change to the directory {$a->dirroot}/elis/program/scripts<br/>
                Run the script fix_cluster_orphans.php to convert all clusters with missing parent clusters to top-level.';
$string['health_completion'] = 'Completion export';
$string['health_completiondesc'] = 'The Completion Export block, which conflicts with Integration Point, is present.';
$string['health_completionsoln'] = 'The completion export block should be automatically removed when the site is properly upgraded via CVS or git.
If it is still present, go to the <a href="{$a->wwwroot}/admin/blocks.php">Manage blocks</a> page and delete the completion export block,
and then remove the <tt>{$a->dirroot}/blocks/completion_export</tt> directory.';
$string['health_curriculum'] = 'Stale ELIS Course - Moodle Program record';
$string['health_curriculumdesc'] = 'There are {$a->count} records in the {$a->table} table referencing nonexistent ELIS courses';
$string['health_curriculumsoln'] = 'These records need to be removed from the database.<br/>Suggested SQL:';
$string['health_duplicate'] = 'Duplicate enrolment records';
$string['health_duplicatedesc'] = 'There were {$a->count} duplicate enrolments records in the ELIS enrolments table.';
$string['health_duplicatesoln'] = 'The duplicate enrolments need to be removed directly from the database.  <b>DO NOT</b> try to remove them via the UI.<br/><br/>
Recommended to escalate to development for solution.';
$string['health_stale'] = 'Stale CM Class - Moodle Course record';
$string['health_staledesc'] = 'There were {$a->count} records in the crlm_class_moodle table referencing nonexistent ELIS classes.';
$string['health_stalesoln'] = 'These records need to be removed from the database.<br/>Suggested SQL:';
$string['health_trackcheck'] = 'Unassociated classes found in tracks';
$string['health_trackcheckdesc'] = 'Found {a->count} classes that are attached to tracks when associated courses are not attached to the curriculum.';
$string['health_trackcheckdescnone'] = 'There were no issues found.';
$string['health_trackchecksoln'] = 'Need to remove all classes in tracks that do not have an associated course in its associated curriculum by running the script linked below.<br/><br/>' .
               '<a href="{$a->wwwroot}/elis/program/scripts/fix_track_classes.php">Fix this now</a>';
$string['health_user_sync'] = 'User Records Mismatch - Synchronize Users';
$string['health_user_syncdesc'] = 'There are {$a->count} extra user records for Moodle which don\'t exist for ELIS.';
$string['health_user_syncsoln'] = 'Users need to be synchronized by running the script which is linked below.<br/><br/>
                This process can take a long time, we recommend you run it during non-peak hours, and leave this window open until you see a success message.
                If the script times out (stops loading before indicating success), please open a support ticket to have this run for you.<br/><br/>
                <a href="{$a->wwwroot}/elis/program/scripts/migrate_moodle_users.php">Fix this now</a>';

$string['icon_collapse_help'] = 'This setting determines the number of icons of each type to display in the Curriculum Administration block.
This setting applies at the top level and also for nest entities.
Please set this value to a number greater than zero.';
$string['icon_collapse_setting'] = 'Number of entity icons to display before collapsing';
$string['id'] = 'ID';
$string['idnumber'] = 'ID Number';
$string['idnumber_already_used'] = 'ID Number is already in use';
$string['id_same_as_user'] = 'Same as username';
$string['inactive'] = 'Inactive';
$string['instructor_add'] = 'Add Instructor';
$string['instructor_assignment'] = 'Assignment Time';
$string['instructor_completion'] = 'Completion Time';
$string['instructor_deleted'] = 'Instructor: {$a->name} deleted.';
$string['instructor_idnumber'] = 'ID Number';
$string['instructor_name'] = 'Name';
$string['instructor_notdeleted'] = 'Instructor: {$a->name} not deleted.';
$string['instructor_role_help'] = 'The default role assigned to instructors when they are synchronized into Moodle.
This synchronization typically takes place when user is assigned as an instructor of a class or when a class becomes associated with a Moodle course.
If this setting not associated with a valid Moodle role, instructors will not be assigned roles when this synchonization takes place.';
$string['instructor_role_setting'] = 'Default Instructor Role';
$string['instructors'] = 'Instructors';
$string['interface_settings'] = 'Interface Settings';
$string['invalid_category_id'] = 'Invalid category ID';
$string['invalid_context_level'] = 'Invalid context level';
$string['invalid_field_id'] = 'Invalid field ID';
$string['invalid_objectid'] = 'Invalid object id: {$a->id}';
$string['invalidconfirm'] = 'Invalid confirmation code!';
$string['items_found'] = '{$a->num} items found.';

$string['lastname'] = 'Last Name';
$string['learningplan'] = 'Learning Plan';
$string['learning_plan_setting'] = 'Turn off learning plan';
$string['length_description'] = 'Length Description';

$string['makecurcourse'] = 'Make a program for this course description';
$string['male'] = 'Male';
$string['management'] = 'Management';
$string['manage_class'] = 'Manage Class Instances';
$string['manage_course'] = 'Manage Course Descriptions';
$string['manage_curriculum'] = 'Manage Programs';
$string['manage_pmclass'] = 'Manage Class Instances';
$string['manage_student'] = 'Manage Students';
$string['manage_track'] = 'Manage Tracks';
$string['manage_user'] = 'Manage users';
$string['manage_userset'] = 'Manage user sets';
$string['moodle_field_sync_warning'] = '* <strong>Warning:</strong> this field is set to synchronize with Moodle user profile fields, but there is no Moodle profile field with the same short name.';
$string['moodlecourse'] = 'Moodle course';
$string['moodlecourseurl'] = 'Mooodle Course URL';
$string['moodleenrol'] = 'You have been removed from the waiting list for class instance {$a->class->idnumber}.
Please visit {$a->wwwroot}/course/enrol.php?id={$a->crs->id} to complete your enrolment.';
$string['moodleenrol_subj'] = 'Ready to enrol in {$a->class->idnumber}.';

$string['name'] = 'Name';
$string['n_completed'] = 'Not Completed';
$string['no_completion_elements'] = 'There are no learning objectives defined.';
$string['no_courses'] = 'No courses found';
$string['no_default_role'] = 'N/A';
$string['no_instructor_matching'] = 'No instructors matching {$a->match}';
$string['no_items_matching'] = 'No items matching ';
$string['no_items_selected'] = 'No items selected';
$string['no_moodlecourse'] = 'No Moodle courses on this site';
$string['no_users_matching'] = 'No users matching {$a->match}';
$string['none'] = 'None';
$string['noroleselected'] = 'N/A';
$string['notemplate'] = 'Could not auto-create Moodle course: no template defined in course.  Created class without an associated Moodle course.';
$string['notice_class_deleted'] = 'Deleted the class instance {$a->idnumber}';
$string['notice_clustercurriculum_deleted'] = 'Deleted the cluster/track association {$a->id}';
$string['notice_clustertrack_deleted'] = 'Deleted the cluster/track association {$a->id}';
$string['notice_course_deleted'] = 'Deleted the course description named {$a->name} (ID number: {$a->idnumber})';
$string['notice_curriculum_deleted'] = 'Deleted the program named {$a->name} (ID number: {$a->idnumber})';
$string['notice_curriculumcourse_deleted'] = 'Deleted the program/course description association {$a->id}';
$string['notice_curriculumstudent_deleted'] = 'Deleted the program/student association {$a->id}';
$string['notice_pmclass_deleted'] = 'Deleted the class instance {$a->idnumber}';
$string['notice_user_deleted'] = 'Deleted the user named {$a->firstname} {$a->lastname} (ID number: {$a->idnumber})';
$string['notice_userset_deleted'] = 'Deleted the user set named {$a->name}';
$string['notice_usertrack_deleted'] = 'Unenroled the user from track: {$a->trackid}';
$string['notice_track_deleted'] = 'Deleted the track {$a->idnumber}';
$string['notice_trackassignment_deleted'] = 'Deleted the track assignment: {$a->id}';
$string['notifications'] = 'Notifications';
$string['notificationssettings'] = 'Notifications Settings';
$string['notifications_notifyuser'] = 'User';
$string['notifications_notifyrole'] = 'User with {$a} capability at system context';
$string['notifications_notifysupervisor'] = 'User with {$a} capability at target user\'s context';
$string['notifyclasscompletedmessage'] = 'Message template for class completion';
$string['notifyclasscompletedmessagedef'] = "%%userenrolname%% has completed the class %%classname%%.";
$string['notifyclassenrolmessage'] = 'Message template for class enrollment';
$string['notifyclassenrolmessagedef'] = "%%userenrolname%% has been enrolled in the class %%classname%%.";
$string['notifyclassnotstarteddays'] = 'Number of days after enrollment to send message';
$string['notifyclassnotstartedmessage'] = 'Message template for class not started';
$string['notifyclassnotstartedmessagedef'] = "%%userenrolname%% has not started the class %%classname%%.";
$string['notifyclassnotcompleteddays'] = 'Number of days before class ends to send message';
$string['notifyclassnotcompletedmessage'] = 'Message template for class not completed';
$string['notifyclassnotcompletedmessagedef'] = "%%userenrolname%% has not completed the class %%classname%%.";
$string['notifycourserecurrencedays'] = 'Number of days before course expires to send message';
$string['notifycourserecurrencemessage'] = 'Message template for course expiration';
$string['notifycourserecurrencemessagedef'] = "%%userenrolname%% is due to re-take the course %%coursename%%.";
$string['notifycurriculumcompletedmessage'] = 'Message template for curriculum completion';
$string['notifycurriculumcompletedmessagedef'] = "%%userenrolname%% has completed the curriculum %%curriculumname%%.";
$string['notifycurriculumnotcompleteddays'] = 'Number of days before curriculum ends to send message';
$string['notifycurriculumnotcompletedmessage'] = 'Message template for curriculum not completed';
$string['notifycurriculumnotcompletedmessagedef'] = "%%userenrolname%% has not completed the curriculum %%curriculumname%%.";
$string['notifycurriculumrecurrencedays'] = 'Number of days before curriculum expires to send message';
$string['notifycurriculumrecurrencemessage'] = 'Message template for curriculum expiration';
$string['notifycurriculumrecurrencemessagedef'] = "%%userenrolname%% is due to re-take the curriculum %%curriculumname%%.";
$string['notifytrackenrolmessage'] = 'Message template for track enrollment';
$string['notifytrackenrolmessagedef'] = "%%userenrolname%% has been enrolled in the track %%trackname%%.";
$string['notify_classcomplete'] = "Receive class completion notifications";
$string['notify_classenrol'] = "Receive class enrollment notifications";
$string['notify_classnotstart'] = "Receive class not started notifications";
$string['notify_classnotcomplete'] = "Receive class not completed notifications";
$string['notify_coursedue'] = "Receive course due to begin notifications";
$string['notify_courserecurrence'] = "Receive course expiration notifications";
$string['notify_curriculumcomplete'] = "Receive curriculum completed notifications";
$string['notify_curriculumdue'] = "Receive curriculum due to begin notifications";
$string['notify_curriculumnotcomplete'] = "Receive curriculum not completed notifications";
$string['notify_curriculumrecurrence'] = "Receive curriculum expiration notifications";
$string['notify_trackenrol'] = "Receive track enrollment notifications";
$string['nouser'] = 'No user found for specified user id.';
$string['nowenroled'] = 'You have been removed from the waiting list and placed in class instance {$a->idnum}.';
$string['num_class_found'] = '{$a->num} class instance(s) found';
$string['num_course_found'] = '{$a->num} course description(s) found';
$string['num_courses'] = 'Num Course Descriptions';
$string['num_curricula_assigned'] = '{$a->num} programs assigned';
$string['num_curricula_unassigned'] = '{$a->num} programs unassigned';
$string['num_curriculum_found'] = '{$a->num} programs found';
$string['num_max_students'] = 'Max # of Students';
$string['num_not_shown'] = '{$a->num} not shown';
$string['num_pmclass_found'] = '{$a->num} class(es) found';
$string['num_students_failed'] = 'number of students failed';
$string['num_students_not_complete'] = 'number of students not complete';
$string['num_students_passed'] = 'number of students passed';
$string['num_track_found'] = '{$a->num} track(s) found';
$string['num_user_found'] = '{$a->num} user(s) found';
$string['num_users_assigned'] = '{$a->num} users assigned';
$string['num_users_unassigned'] = '{$a->num} users unassigned';
$string['num_userset_found'] = '{$a->num} user set(s) found';
$string['numselected'] = '{$a->num} currently selected';

$string['o_active'] = 'Only active';
$string['o_inactive'] = 'Only inactive';
$string['over_enrol'] = 'Over Enrol'; // TBD: Enroll

$string['passed'] = 'Passed';
$string['phone2'] = 'Phone 2';
$string['pm_date_format'] = 'M j, Y';
$string['pmclassform:class_idnumber'] = 'ID Number';
$string['pmclassform:class_idnumber_help'] = '<p>Class Instance ID number.</p>';
$string['pmclassform:class_startdate'] = 'Start Date';
$string['pmclassform:class_startdate_help'] = '<p>Enter the course description start and end date, if applicable.</p>';
$string['pmclassform:class_starttime'] = 'Start Time';
$string['pmclassform:class_starttime_help'] = '<p>Enter the course description start and end time, if applicable.  This is appropriate for synchronous online sessions, as well as face-to-face classes.</p>';
$string['pmclassform:class_maxstudents'] = 'Max # of Students';
$string['pmclassform:class_maxstudents_help'] = '';
$string['pmclassform:class_unassigntrackhead'] = 'Unassigned Tracks';
$string['pmclassform:class_unassigntrackhead_help'] = '<p>If tracks have been created in the system, tracks will be displayed here. If this class instance should be included in a track, select the appropriate track.</p>';
$string['pmclassform:course'] = 'Course Description';
$string['pmclassform:course_help'] = '<p>Select the course this class instance is an instance of. The drop down menu will show all courses created in the system.</p>';
$string['pmclassform:environment'] = 'Environment';
$string['pmclassform:environment_help'] = '<p>Select the appropriate environment from the drop down menu. If no
environments have been entered into the system, they can be entered by going to
Program Administration &gt; Information Elements &gt; Environments.</p>';
$string['pmclassform:moodlecourse'] = 'Moodle course';
$string['pmclassform:moodlecourse_help'] = '<p>The Moodle course that this class instance is attached to and is an instance of.</p>';
$string['pmclassform:waitlistenrol'] = 'Auto enrol from waitlist';
$string['pmclassform:waitlistenrol_help'] = '<p>on to automatically enrol students from the waitlist into the course description when an erolled student completes (passes or fails) the course description.</p>';
$string['pmclass_delete_warning'] = 'Warning!  Deleting this class instance will also delete all stored enrollment information for the class instance.';
$string['pmclass_delete_warning_continue'] = 'I understand all enrollments for the class instance will be deleted, continue ...';
$string['position'] = 'Position';
$string['postalcode'] = 'Postal code';
$string['prerequisites'] = 'Prerequisite';
$string['priority'] = 'Display priority';
$string['progman'] = 'Program Manager';
$string['program_copy_mdlcrs_copyalways'] = 'Always copy';
$string['program_copy_mdlcrs_copyautocreated'] = 'Copy auto-created courses';
$string['program_copy_mdlcrs_autocreatenew'] = 'Auto-create from template';
$string['program_copy_mdlcrs_link'] = 'Link to existing course';
$string['program_display'] = 'Display';
$string['program_info_group'] = 'Program Information';
$string['program_name'] = 'Name';
$string['program_numcourses'] = 'Num Courses';
$string['program_reqcredits'] = ' Required Credits';

$string['record_not_created'] = 'Record not created.';
$string['record_not_created_reason'] = 'Record not created. Reason: {$a->message}';
$string['record_not_updated'] = 'Record not updated. Reason: {$a->message}';
$string['redirect_dashbrd_setting'] = 'Redirect users accessing My Moodle to the dashboard';
$string['registered_date'] = 'Registered date';
$string['remove_coreq'] = 'Remove co-requisites';
$string['remove_prereq'] = 'Remove prerequisites';
$string['required'] = 'Required';
$string['required_credits'] = 'Required Credits';
$string['required_field'] = 'Error: {$a->name} is a required field';

$string['save_enrolment_changes'] = 'Save Changes';
$string['saved'] = 'saved';
$string['search'] = 'Search';
$string['selectedonly'] = 'Show selected items only';
$string['showallitems'] = 'Show all items';
$string['show_all_users'] = 'Show All Users';
$string['showinactive'] = 'Show inactive';
$string['student_credits'] = 'Credits';
$string['student_deleteconfirm'] = 'Are you sure you want to unenrol the student name: {$a->name} ?<br />'.
                                   'NOTE: This will delete all records for this student in this class instance and will unenrol them from any connected Moodle class!';
$string['student_email'] = 'Email'; // TBD
$string['student_grade'] = 'Grade';
$string['student_id'] = 'ID'; // TBD
$string['student_idnumber'] = 'ID Number';
$string['student_locked'] = 'Locked';
$string['student_name'] = 'Student Name';
$string['student_name_1'] = 'Name';
$string['student_status'] = 'Status';
$string['studentnotunenrolled'] = 'Student: {$a->name} not unenrolled.';
$string['students'] = 'Students';
$string['studentunenrolled'] = 'Student: {$a->name} unenrolled.';
$string['success_waitlist_remove'] = 'Successfully removed from waitlist.';
$string['success_waitlist_overenrol'] = 'Successfully over enrolled.';
$string['sync_instructor_role_help'] = 'If you select a role here, then any user with this role in an ELIS class will be assigned as an instructor in the class.';
$string['sync_instructor_role_setting'] = 'Instructor Role';
$string['sync_student_role_help'] = 'If you select a role here, then any user with this role in an ELIS class will be enrolled as a student in the class.';
$string['sync_student_role_setting'] = 'Student Role';

$string['tag_name'] = 'Name';
$string['tags'] = 'Tags';
$string['timecreated'] = 'Creation time';
$string['time_12h_setting'] = 'Display time selection in a 12 hour format';
$string['time_period'] = 'Timeperiod';
$string['time_settings'] = 'Time Settings';
$string['tips_time_format'] = "The format of this is ' *h, *d, *w, *m, *y ' (representing hours, days, weeks, months and years - where * can be any number) Each format must be separated by a comma";
$string['time_to_complete'] = 'Time to complete';
$string['top_clusters_help'] = 'This setting controls whether existing clusters are listed at the top level of the Curriculum Administration block.
When changing the value of this setting, please navigate to another page to determine whether this functionality is working as expected.';
$string['top_clusters_setting'] = 'Display Clusters as the Top Level';
$string['top_curricula_help'] = 'This setting controls whether existing curricula are listed at the top level of the Curriculum Administration block.
When changing the value of this setting, please navigate to another page to determine whether this functionality is working as expected.';
$string['top_curricula_setting'] = 'Display Curricula at the Top Level';
$string['track'] = 'Track';
$string['trackform:curriculum_curid'] = 'Program';
$string['trackform:curriculum_curid_help'] = '<p>The program this track is an instance or replica of.</p>';;
$string['trackform:curriculum_curidstatic'] = 'Program';
$string['trackform:curriculum_curidstatic_help'] = '<p>The program this track is an instance or replica of.</p>';
$string['trackform:track_autocreate'] = 'Create all class instances';
$string['trackform:track_autocreate_help'] = '<p>Enter the course description start and end date, if applicable.</p>';
$string['trackassignmentform:track_autoenrol'] = 'Auto-enrol';
$string['trackassignmentform:track_autoenrol_help'] = '<p>Auto enrol into this track.</p>';
$string['trackassignmentform:track_autoenrol_long'] = 'Auto-enrol users into this class when they are added to this track';
$string['trackform:curriculum_curid'] = 'Curriculum';
$string['trackform:curriculum_curid_help'] = '<p>The curriculum this track is an instance or replica of.</p>';
$string['trackform:curriculum_curidstatic'] = 'Curriculum';
$string['trackform:curriculum_curidstatic_help'] = '<p>The curriculum this track is an instance or replica of.</p>';
$string['trackform:track_autocreate'] = 'Create all classes';
$string['trackform:track_autocreate_help'] = '<p>Enter the course start and end date, if applicable.</p>';
$string['trackform:track_description'] = 'Description';
$string['trackform:track_description_help'] = '<p>Description of the track.</p>';
$string['trackform:track_idnumber'] = 'ID Number';
$string['trackform:track_idnumber_help'] = '<p>Enter an id number for the track. This number will appear in the class instance id number for each class instance which is a part of the track.</p>';
$string['trackform:track_name'] = 'Name';
$string['trackform:track_name_help'] = '<p>Name of the track.</p>';
$string['trackform:track_startdate'] = 'Start Date';
$string['trackform:track_startdate_help'] = '<p>Start and end date for the track, if applicable.</p>';
$string['tracks'] = 'Tracks';
$string['trackuserset_auto_enrol'] = 'Auto-enrol';
$string['track_assign_users'] = 'Assign users';
$string['track_autocreate'] = 'Create all class instances';
$string['track_autocreate_button'] = 'Auto-create class instances';
$string['track_auto_enrol'] = 'Auto-enrol';
$string['track_classes'] = 'Class Instances';
$string['track_click_user_enrol_track'] = 'Click on a user to enrol him/her in the track.';
$string['track_curriculumid'] = 'Program';
$string['track_description'] = 'Description';
$string['track_edit'] = 'Edit Track';
$string['track_enddate'] = 'End Date';
$string['track_idnumber'] = 'ID Number';
$string['track_info_group'] = 'Track Information';
$string['track_maxstudents'] = 'Max Students';
$string['track_name'] = 'Name';
$string['track_no_matching_users'] = 'No matching users.';
$string['track_num_classes'] = 'Number of class instances';
$string['track_parcur'] = 'Parent program';
$string['track_role_help'] = 'This is the default role to assign to a Curriculum Management user in any tracks they create.
This type of role assignment will not take place for a particular track if that user is already permitted to edit that track.
To disable this functionality, select "N/A" from the list.';
$string['track_role_setting'] = 'Default Track Role';
$string['track_settings'] = 'Track Settings';
$string['track_startdate'] = 'Start Date';
$string['track_success_autocreate'] = 'Auto-created class instances for this track';
$string['transfercredits'] = 'Transfer credits';

$string['unassign'] = 'unassign';
$string['unassigned'] = 'Unassigned';
$string['unenrol'] = 'Unenrol'; // TBD: Unenroll ?
$string['unsatisfiedprereqs'] = 'One or more prerequisites are not completed yet.';
$string['update_assignment'] = 'Update Assignment';
$string['update_enrolment'] = 'Update Enrollment';
$string['update_grade'] = 'Update Grade';
$string['user'] = 'User';
$string['user_language'] = 'Language';
$string['user_settings'] = 'User Settings';
$string['user_waitlisted'] = 'user added to waitlist';
$string['user_waitlisted_msg'] = 'user with idnumber {$a->user} has been added to the waitlist for class instances {$a->pmclass}';
$string['users'] = 'Users';
$string['userbirthdate'] = 'Birth date';
$string['userdef_tracks_setting'] = 'Turn off user defined tracks';
$string['usergender'] = 'Gender';
$string['usermi'] = 'Middle initials';
$string['useridnumber'] = 'ID number';
$string['useridnumber_help'] = 'An id number is a unique value used to identify you within your organization.
It also serves as way to tie Program Management users to Moodle users.';
$string['usersetprogramform:autoenrol'] = 'Auto-enrol';
$string['usersetprogramform:autoenrol_help'] = '<p>If this box is checked then new users will be enrolled in this program when they are added to this user set.</p>';
$string['usersetprogramform_auto_enrol'] = 'Auto-enrol users into this program when they are added to this user set';
$string['usersetprogram_auto_enrol'] = 'Auto-enrol';
$string['usersetprogram_unassociated'] = 'Unassociated';
$string['userset_addcurr_instruction'] = 'Use the add drop down to LINK this user set to a program.';
$string['userset_cpyclustname'] = 'User set';
$string['userset_cpycurname'] = 'Program';
$string['userset_cpycurr'] = 'Copy Program';
$string['userset_cpycurr_instruction'] = 'Use the copy button to make a copy of a program and link it to this user set.';
$string['userset_cpyadd'] = 'Add';
$string['userset_cpytrkcpy'] = 'Copy Tracks';
$string['userset_cpycrscpy'] = 'Copy Courses';
$string['userset_cpyclscpy'] = 'Copy Classes';
$string['userset_cpymdlclscpy'] = 'Copy Moodle Courses';
$string['userset_idnumber'] = 'ID Number';
$string['userset_info_group'] = 'User set Information';
$string['userset_name'] = 'Name';
$string['userset_name_help'] = 'Name of the user set';
$string['userset_description'] = 'Description';
$string['userset_description_help'] = 'Description of the user set.';
$string['userset_parent'] = 'Parent user set';
$string['userset_parent_help'] = 'Parent user set of this user set.  "Top Level" indicates no parent user set.';
$string['userset_saveexit'] = 'Save and Exit';
$string['userset_top_level'] = 'Top level';
$string['userset_userassociation'] = 'User association';
$string['usersettrack_autoenrol'] = 'Auto-enrol';
$string['usersettrack_auto_enrol'] = 'Auto-enrol users into this track when they are added to this cluster';
$string['usersettrackform:autoenrol'] = 'Auto-enrol';
$string['usersettrackform:autoenrol_help'] = '<p>If this box is checked then new users will be enrolled in this track when they are added to this cluster.</p>';
$string['usersubsets'] = 'User subsets';

$string['waiting'] = 'Waiting';
$string['waitinglistform_title'] = 'Class is full';
$string['waitlist'] = 'waitlist';
$string['waitlistenrol'] = 'Auto enrol from waitlist';


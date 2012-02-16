<?php

//sample setting to indicate that this config page is working normally and
//saving values to the config_plugins table
//todo: replace with real settings
$settings->add(new admin_setting_configcheckbox('rlipexport_version1/name', 'Visible Name',
                                                'Description', 0));

//link for configuring export fields
$url = $CFG->wwwroot.'/blocks/rlip/exportplugins/version1/config_fields.php';
$attributes = array('href' => $url,
                    'target' => '_blank');
$displaystring = get_string('configurelink', 'rlipexport_version1');
$headerstring = get_string('configureheader', 'rlipexport_version1');
$tag = html_writer::tag('a', $displaystring, $attributes);
$settings->add(new admin_setting_heading('block_rlip_version1_test', $headerstring, $tag));
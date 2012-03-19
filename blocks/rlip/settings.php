<?php

require_once($CFG->dirroot . '/blocks/rlip/lib.php');

//add all IP-related entities to the standard Moodle admin tree
rlip_admintree_setup($ADMIN);

//start of "scheduling" section
$settings->add(new admin_setting_heading('rlip/scheduling',
                                         get_string('rlip_global_scheduling', 'block_rlip'),
                                         ''));

//setting for disabling in Moodle cron
//todo: actually use this setting's value somewhere
$settings->add(new admin_setting_configcheckbox('rlip/disableincron',
                                                get_string('disableincron', 'block_rlip'),
                                                get_string('configdisableincron', 'block_rlip'), ''));
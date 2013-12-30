<?php

require_once($CFG->dirroot . '/blocks/rlip/lib.php');

//todo: convert setting to be part of block_rlip rather than rlip

//add all IP-related entities to the standard Moodle admin tree
rlip_admintree_setup($ADMIN);

//start of "scheduling" section
$settings->add(new admin_setting_heading('block_rlip/scheduling',
                                         get_string('rlip_global_scheduling', 'block_rlip'),
                                         ''));

//setting for disabling in Moodle cron
$settings->add(new admin_setting_configcheckbox('block_rlip/disableincron',
                                                get_string('disableincron', 'block_rlip'),
                                                get_string('configdisableincron', 'block_rlip'), ''));

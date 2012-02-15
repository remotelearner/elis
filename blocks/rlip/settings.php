<?php

require_once($CFG->dirroot . '/blocks/rlip/lib.php');

//add all IP-related entities to the standard Moodle admin tree
rlip_admintree_setup($ADMIN);

//add a bogus setting for now, just to show what this would look like
$settings->add(new admin_setting_heading('block_rlip_header', 'Replace Me',
                                         'Core IP Settings Should Go Here'));
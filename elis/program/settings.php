<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $ADMIN->add('elis', new admin_category('elis_program', get_string('progman', 'elis_program')));

    $settings = new admin_settingpage('elis_program_something', "Somethingorother");
    $settings->add(new admin_setting_configcheckbox('elis_program/foo', 'Foo',
                                                    'Help text', 0));
    $ADMIN->add('elis_program', $settings);

}

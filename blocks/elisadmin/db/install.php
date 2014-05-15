<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    block_elisadmin
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

function xmldb_block_elisadmin_install() {
    global $DB;

    // Migrate component.
    $oldcmp = 'block_curr_admin';
    $newcmp = 'block_elisadmin';
    $migrator = new \local_eliscore\install\migration\migrator($oldcmp, $newcmp);
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }

    // Update instances.
    $sql = 'UPDATE {block_instances} SET blockname = ? WHERE blockname = ?';
    $params = array('elisadmin', 'curr_admin');
    $DB->execute($sql, $params);

    $java_app = new stdClass;
    $java_app->name = 'java';
    $java_app->display_name = 'Java servlet';
    $java_app->xmlrpc_server_url = '/mnet/server';
    $java_app->sso_land_url = '/mnet/land.jsp';
    $DB->insert_record('mnet_application', $java_app);

}
<?php
/**
 * A script to run certain steps that are required before an upgrade to Moodle 2.x / ELIS 2.
 *
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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */


define('CLI_SCRIPT', true);

require_once('../../../config.php');




/*

The following are queries that can be used to detect the presence of duplicates and also queries that can be safely used to clean up duplicate values.

!!! NOTE: These queries should be wrapped inside a transaction (especially the removal of duplicates).

-- Detect duplicates

SELECT contextid, lowerboundary, letter, COUNT(*) count
FROM mdl_grade_letters
GROUP BY contextid, lowerboundary, letter
ORDER BY count DESC


-- Remove duplicates

CREATE TABLE mdl_grade_letters_temp LIKE mdl_grade_letters;

INSERT INTO mdl_grade_letters_temp (contextid, lowerboundary, letter) SELECT contextid, lowerboundary, letter FROM mdl_grade_letters GROUP BY contextid, lowerboundary, letter;

DROP TABLE mdl_grade_letters;

RENAME TABLE mdl_grade_letters_temp TO mdl_grade_letters;


-- ======================================================================

-- Detect duplicates

SELECT userid, name, value, COUNT(*) count
FROM mdl_user_preferences
GROUP BY userid, name, value
ORDER BY count DESC


-- Remove duplicates

CREATE TABLE mdl_user_preferences_temp LIKE mdl_user_preferences;

INSERT INTO mdl_user_preferences_temp (userid, name, value) SELECT userid, name, value FROM mdl_user_preferences GROUP BY userid, name, value;

DROP TABLE mdl_user_preferences;

RENAME TABLE mdl_user_preferences_temp TO mdl_user_preferences;
 */

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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

namespace local_elisprogram\context;

/**
 * This provides a definition of custom context levels used with ELIS program.
 */
class contextinfo {

    /**
     * Returns an array of context level definitions.
     *
     * @return array Array of context level definition.
     */
    public static function get_contextinfo() {
        return array(
                array(
                    'level' => 11,
                    'constant' => 'CONTEXT_ELIS_PROGRAM',
                    'name' => 'curriculum',
                    'class' => '\local_elisprogram\context\program',
                ),
                array(
                    'level' => 12,
                    'constant' => 'CONTEXT_ELIS_TRACK',
                    'name' => 'track',
                    'class' => '\local_elisprogram\context\track',
                ),
                array(
                    'level' => 13,
                    'constant' => 'CONTEXT_ELIS_COURSE',
                    'name' => 'course',
                    'class' => '\local_elisprogram\context\course',
                ),
                array(
                    'level' => 14,
                    'constant' => 'CONTEXT_ELIS_CLASS',
                    'name' => 'class',
                    'class' => '\local_elisprogram\context\pmclass',
                ),
                array(
                    'level' => 15,
                    'constant' => 'CONTEXT_ELIS_USER',
                    'name' => 'user',
                    'class' => '\local_elisprogram\context\user',
                ),
                array(
                    'level' => 16,
                    'constant' => 'CONTEXT_ELIS_USERSET',
                    'name' => 'cluster',
                    'class' => '\local_elisprogram\context\userset',
                ),
        );
    }
}
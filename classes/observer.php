<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Observer class for listening to events.
 *
 * @package tool_profileroles
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_profileroles;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for listening to events.
 */
class observer {
    /**
     * Method called when a user logged in.
     *
     * @param \core\event\user_loggedin $event
     * @throws \dml_exception
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        if (!logic::is_enabled()) {
            return;
        }

        $fields = logic::get_all_fields($event->userid);
        $roles = logic::get_appropriate_roles($fields);
        try {
            logic::apply_roles($event->userid, $roles);
        } catch (\moodle_exception $e) {
            debugging($e->getMessage());
        }
    }
}

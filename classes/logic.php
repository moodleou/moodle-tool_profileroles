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
 * All the logic for this plugin.
 *
 * @package tool_profileroles
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_profileroles;

defined('MOODLE_INTERNAL') || die();

/**
 * All the logic for this plugin.
 *
 * @package tool_profileroles
 */
class logic {
    /**
     * Checks if the plugin is enabled and might need to do something.
     *
     * @return bool True if is enabled
     * @throws \dml_exception
     */
    public static function is_enabled(): bool {
        $config = get_config('tool_profileroles');
        return $config->enabled && !empty($config->roleconfig);
    }

    /**
     * Gets all fields (from user table and extra profile fields that appear in user object) for
     * a specified user id.
     *
     * @param int $userid User id
     * @return array Array from field name to value
     * @throws \dml_exception
     */
    public static function get_all_fields(int $userid): array {
        global $CFG, $DB, $USER;

        // Get all fields (user table + profile fields).
        if ($USER->id === $userid) {
            // The current user object already has all required fields.
            $fields = (array)$USER;
        } else {
            // Load user object from database along with profile fields.
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $user = (array)$DB->get_record('user', ['id' => $userid]);
            $fields = $user + (array)profile_user_record($userid);
        }
        return $fields;
    }

    /**
     * Works out which system-level roles should apply to the user based on their fields.
     *
     * @param array $fields Array of fields for a user
     * @return array Array from role shortname to true/false
     */
    public static function get_appropriate_roles(array $fields): array {
        $roleconfig = get_config('tool_profileroles', 'roleconfig');
        $result = [];

        // Split into lines, ignoring blank lines.
        $lines = preg_split('~[\r\n]+~', $roleconfig, null, PREG_SPLIT_NO_EMPTY);
        $usedroles = [];
        foreach ($lines as $line) {
            // Allow comments beginning with #.
            if (preg_match('~^\s*#~', $line)) {
                continue;
            }

            // Line must be a role name, colon, and a number of clauses...
            if (!preg_match('~^\s*([^\s:]+)\s*:(.*)$~', $line, $matches)) {
                debugging('Invalid roleconfig line in tool_profileroles: ' . $line);
                continue;
            }
            $roleshortname = $matches[1];
            if (array_key_exists($roleshortname, $usedroles)) {
                debugging('Invalid roleconfig line in tool_profileroles: ' . $line .
                        ' (role shortname "' . $roleshortname . '" already defined)');
                continue;
            }
            $usedroles[$roleshortname] = true;

            $clauses = preg_split('~(\s*[,]\s*)~', $matches[2], null, PREG_SPLIT_NO_EMPTY);

            // Clauses are matched with OR, if you have any clause you get the role.
            $got = false;
            foreach ($clauses as $clause) {
                $clause = trim($clause);
                if (!preg_match('/^([^\s=~]+)\s*([=~])\s*(.*)$/', $clause, $matches)) {
                    debugging('Invalid roleconfig line in tool_profileroles: ' . $line .
                            ' (clause "' . $clause . '")');
                    continue 2;
                }
                $fieldname = $matches[1];
                $operator = $matches[2];
                $value = $matches[3];

                if (!array_key_exists($fieldname, $fields)) {
                    debugging('Invalid roleconfig line in tool_profileroles: ' . $line .
                            ' (no such field "' . $fieldname . '")');
                    continue 2;
                }

                switch ($operator) {
                    case '=' :
                        if ($fields[$fieldname] === $value) {
                            $got = true;
                        }
                        break;

                    case '~' :
                        $found = @preg_match('/' . $value . '/', $fields[$fieldname]);
                        if ($found === false) {
                            debugging('Invalid roleconfig line in tool_profileroles: ' . $line .
                                    ' (invalid regex "/' . $value . '/")');
                            continue 3;
                        }
                        if ($found) {
                            $got = true;
                        }
                        break;
                }
            }

            $result[$roleshortname] = $got;
        }

        return $result;
    }

    /**
     * Applies the specified sitewide roles, based on an array with list of which roles the user
     * should have. For each role, if the value in the array is 'false' it will be removed, or
     * if the value is 'true' it will be added. These changes will only be made if necessary, i.e.
     * there will be no change if the user already has the correct roles.
     *
     * @param int $userid User id
     * @param array $roles Desired roles array
     * @throws \moodle_exception If a named role does not exist
     */
    public static function apply_roles(int $userid, array $roles) {
        global $DB;

        // Get list of existing role shortnames for user in system context.
        $context = \context_system::instance();
        $existingroles = get_user_roles($context, $userid, false);
        $existingshortnames = [];
        foreach ($existingroles as $entry) {
            $existingshortnames[$entry->shortname] = true;
        }

        // Loop through the list.
        foreach ($roles as $shortname => $should) {
            if ($should && array_key_exists($shortname, $existingshortnames)) {
                // They should have the role and they do, so all is right with the world.
                continue;
            }
            if (!$should && !array_key_exists($shortname, $existingshortnames)) {
                // They shouldn't have the role and they don't, so all is right with the world.
                continue;
            }

            // We need to either add or remove the role, so get its id.
            $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname], IGNORE_MISSING);
            if (!$roleid) {
                throw new \moodle_exception('nosuchrole', 'tool_profileroles', '', $shortname);
            }
            if ($should) {
                role_assign($roleid, $userid, $context->id, 'tool_profileroles');
            } else {
                role_unassign_all(['roleid' => $roleid, 'userid' => $userid,
                        'contextid' => $context->id, 'component' => 'tool_profileroles']);
            }

            // Note - there is no specific logging here because role_assign/unassign cause logging
            // already (and you can work out it was this tool, either because it's in the component
            // or because there was a loggedin event at the same time).
        }
    }
}

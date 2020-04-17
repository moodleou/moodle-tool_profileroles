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
 * Language pack.
 *
 * @package tool_profileroles
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['nosuchrole'] = 'Role does not exist: {$a}';
$string['pluginname'] = 'Profile-based roles';
$string['privacy:metadata'] = 'The profile-based roles plugin does not store any personal data.';
$string['setting_enabled'] = 'Enabled';
$string['setting_enabled_desc'] = 'If not enabled, the plugin will do nothing even if the configuration below is filled in.';
$string['setting_roleconfig'] = 'Role configuration';
$string['setting_roleconfig_desc'] = 'This setting should list role shortnames, one per line, followed by the required user profile field conditions. All standard user fields (e.g. department) and short custom fields (not descriptive text fields) can be used.

On login, users who match any of the conditions for a role will be granted that role at system level. If the user already has one of these roles at system level but does not match any of the conditions, it will be removed.

Two operators are available for comparing the user field value, = (exact match) and ~ (regular expression match). When using a regular expression, do not include the / symbols either side: these are added automatically.

Example:

    # Comment line (ignored)
    sitewidestudent: userType = Student
    examstaff: userType = Examiner, userType = Invigilator
    brainiac: department ~ athemati, department ~ ^[Pp]hysics$
';

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
 * Admin settings
 *
 * @package tool_profileroles
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_profileroles', get_string('pluginname', 'tool_profileroles'));
    $ADMIN->add('tools', $settings);

    $settings->add(new admin_setting_configcheckbox(
            'tool_profileroles/enabled',
            new lang_string('setting_enabled', 'tool_profileroles'),
            new lang_string('setting_enabled_desc', 'tool_profileroles'), 0));

    $settings->add(new admin_setting_configtextarea(
            'tool_profileroles/roleconfig',
            new lang_string('setting_roleconfig', 'tool_profileroles'),
            new lang_string('setting_roleconfig_desc', 'tool_profileroles'),
            '', PARAM_RAW, 60, 10));
}

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
 * Test script for the observer class.
 *
 * @package tool_profileroles
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_profileroles;

defined('MOODLE_INTERNAL') || die();

/**
 * Test script for the observer class.
 */
class observer_test extends \advanced_testcase {

    /** @var string Some rules for assigning the two default sitewide roles */
    const SILLY_CONFIG = "manager: department=IT\ncoursecreator: country~^[Uu][Kk]$";

    /**
     * Tests the observer function does nothing when not enabled.
     */
    public function test_observer_not_enabled() {
        $this->resetAfterTest();

        // Set the config, but don't enable it.
        set_config('roleconfig', self::SILLY_CONFIG, 'tool_profileroles');

        // Create a user who would meet the requirements for both roles.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['department' => 'IT', 'country' => 'UK']);

        // Fake their login.
        self::fake_login($user->id);

        // Check they don't have either role.
        $this->assertFalse(self::has_role($user->id, 'manager'));
        $this->assertFalse(self::has_role($user->id, 'coursecreator'));
    }

    /**
     * Tests the observer function works to give roles to a user when enabled.
     *
     * @throws \moodle_exception
     */
    public function test_observer_enabled() {
        global $DB;

        $this->resetAfterTest();

        // Set the config.
        set_config('roleconfig', self::SILLY_CONFIG, 'tool_profileroles');
        set_config('enabled', 1, 'tool_profileroles');

        // Create a user who would meet the requirements for both roles.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['department' => 'IT', 'country' => 'UK']);

        // Fake their login.
        self::fake_login($user->id);

        // Check they have both roles.
        $this->assertTrue(self::has_role($user->id, 'manager'));
        $this->assertTrue(self::has_role($user->id, 'coursecreator'));

        // Change their department (no longer qualifies) and country (still qualifies).
        $DB->set_field('user', 'department', 'Maths', ['id' => $user->id]);
        $DB->set_field('user', 'country', 'uk', ['id' => $user->id]);
        self::fake_login($user->id);
        $this->assertFalse(self::has_role($user->id, 'manager'));
        $this->assertTrue(self::has_role($user->id, 'coursecreator'));

        // Change their country to not qualify either.
        $DB->set_field('user', 'country', 'DE', ['id' => $user->id]);
        self::fake_login($user->id);
        $this->assertFalse(self::has_role($user->id, 'manager'));
        $this->assertFalse(self::has_role($user->id, 'coursecreator'));
    }

    /**
     * Check it causes a debugging message if it tries to create a role that doesn't exist.
     *
     * @throws \moodle_exception
     */
    public function test_observer_brokenconfig() {
        $this->resetAfterTest();

        // Set the config with a nonexistent role.
        set_config('roleconfig', 'fakerole: department=IT', 'tool_profileroles');
        set_config('enabled', 1, 'tool_profileroles');

        // Create a user who would meet the requirements.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['department' => 'IT']);

        // Fake their login.
        self::fake_login($user->id);

        // Check the debugging message.
        $this->assertDebuggingCalled('Role does not exist: fakerole');
    }

    /**
     * Convenience function to check if user has a specified role at system level.
     *
     * @param int $userid User
     * @param string $shortname Role shortname
     * @return bool True if they got it
     * @throws \dml_exception
     */
    protected static function has_role(int $userid, string $shortname): bool {
        global $DB;
        $context = \context_system::instance();
        $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
        return user_has_role_assignment($userid, $roleid, $context->id);
    }

    /**
     * Sends a login event as if a user logged in.
     *
     * @param int $userid User id
     * @throws \moodle_exception
     */
    protected static function fake_login(int $userid) {
        global $DB;
        $event = \core\event\user_loggedin::create([
                'userid' => $userid,
                'objectid' => $userid,
                'other' => ['username' => $DB->get_field('user', 'username', ['id' => $userid])]
        ]);
        $event->trigger();
    }
}

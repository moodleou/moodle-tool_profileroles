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
 * Test script for the logic class.
 *
 * @package tool_profileroles
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_profileroles;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/field/text/field.class.php');
require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/field/text/define.class.php');

/**
 * Test script for the logic class.
 */
class logic_test extends \advanced_testcase {

    /**
     * Tests the is_enabled function.
     */
    public function test_is_enabled() {
        $this->resetAfterTest();

        // Default off.
        $this->assertFalse(logic::is_enabled());

        // Flag on, but settings empty.
        set_config('enabled', 1, 'tool_profileroles');
        $this->assertFalse(logic::is_enabled());

        // Flag on with settings.
        set_config('roleconfig', 'xxx', 'tool_profileroles');
        $this->assertTrue(logic::is_enabled());

        // Flag off with settings.
        set_config('enabled', 0, 'tool_profileroles');
        $this->assertFalse(logic::is_enabled());
    }

    /**
     * Creates two custom field 'frog' and 'zombie'.
     *
     * @throws \dml_exception
     */
    protected static function create_custom_fields() {
        global $DB;

        // Create custom field category.
        $category = (object)[
            'name' => 'Test fields',
            'sortorder' => 999
        ];
        $category->id = $DB->insert_record('user_info_category', $category);

        // Create two custom fields.
        $define = new \profile_define_text();
        $field = (object)[
            'shortname' => 'frog',
            'name' => 'Frog',
            'datatype' => 'text',
            'description' => '',
            'categoryid' => $category->id,
            'sortorder' => 1,
            'locked' => 1,
            'defaultdata' => '',
            'param1' => 8, // Display size.
            'param2' => 8 // Max length.
        ];
        $define->define_save($field);
        $field = (object)[
            'shortname' => 'zombie',
            'name' => 'Zombie',
            'datatype' => 'text',
            'description' => '',
            'categoryid' => $category->id,
            'sortorder' => 2,
            'locked' => 1,
            'defaultdata' => '',
            'param1' => 8, // Display size.
            'param2' => 8 // Max length.
        ];
        $define->define_save($field);
    }

    /**
     * Sets a field on a user.
     *
     * @param int $userid User id
     * @param string $fieldname Field name
     * @param string $value Field value
     * @throws \dml_exception
     */
    protected static function set_field(int $userid, string $fieldname, string $value) {
        global $DB;
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $fieldname]);
        $field = new \profile_field_text($fieldid, $userid);
        $data = (object)['id' => $userid, 'profile_field_' . $fieldname => $value];
        $field->edit_save_data($data);
    }

    /**
     * Tests the get_all_fields function with current and another user.
     */
    public function test_get_all_fields() {
        $this->resetAfterTest();
        self::create_custom_fields();

        // Create some users.
        $generator = $this->getDataGenerator();
        $u1 = $generator->create_user(['username' => 'u1', 'department' => 'Amphibians']);
        self::set_field($u1->id, 'frog', 'Kermit');

        $u2 = $generator->create_user(['username' => 'u2', 'department' => 'Undead']);
        self::set_field($u2->id, 'zombie', 'Brains');

        $u3 = $generator->create_user(['username' => 'u3', 'department' => 'Undead amphibians']);
        self::set_field($u3->id, 'frog', 'Mr Toad');
        self::set_field($u3->id, 'zombie', 'Braaaaaaaainssssssss');

        $u4 = $generator->create_user(['username' => 'u4', 'department' => 'Boring stuff']);

        // The first user is current.
        $this->setUser($u1);

        // Get and check fields.
        $fields = logic::get_all_fields($u1->id);
        $this->assertEquals('u1', $fields['username']);
        $this->assertEquals('Amphibians', $fields['department']);
        $this->assertEquals('Kermit', $fields['frog']);
        $this->assertEquals('', $fields['zombie']);

        // Other users are not current. Get and check fields for each.
        $fields = logic::get_all_fields($u2->id);
        $this->assertEquals('u2', $fields['username']);
        $this->assertEquals('Undead', $fields['department']);
        $this->assertEquals('', $fields['frog']);
        $this->assertEquals('Brains', $fields['zombie']);

        $fields = logic::get_all_fields($u3->id);
        $this->assertEquals('u3', $fields['username']);
        $this->assertEquals('Undead amphibians', $fields['department']);
        $this->assertEquals('Mr Toad', $fields['frog']);
        $this->assertEquals('Braaaaaaaainssssssss', $fields['zombie']);

        $fields = logic::get_all_fields($u4->id);
        $this->assertEquals('u4', $fields['username']);
        $this->assertEquals('Boring stuff', $fields['department']);
        $this->assertEquals('', $fields['frog']);
        $this->assertEquals('', $fields['zombie']);
    }

    /**
     * Tests the get_appropriate_roles function.
     */
    public function test_get_appropriate_roles() {
        $this->resetAfterTest();
        self::create_custom_fields();

        // Test comments, blank lines, and invalid lines.
        set_config('roleconfig',
                "# Comment\n" .
                "\n" .
                "Completely invalid line\n" .
                "role1: invalid bit afterwards\n" .
                "role2: username=a1, invalid clause, username=a3\n" .
                "role2: department=Duplicate role\n" .
                "role3: nosuchfield=x\n" .
                "role4: username~(((\n" .
                "role5: username=validline", 'tool_profileroles');
        $result = logic::get_appropriate_roles(['username' => 'a4', 'department' => 'Frogs']);
        $this->assertDebuggingCalledCount(6, [
                'Invalid roleconfig line in tool_profileroles: Completely invalid line',
                'Invalid roleconfig line in tool_profileroles: ' .
                    'role1: invalid bit afterwards (clause "invalid bit afterwards")',
                'Invalid roleconfig line in tool_profileroles: ' .
                    'role2: username=a1, invalid clause, username=a3 (clause "invalid clause")',
                'Invalid roleconfig line in tool_profileroles: ' .
                    'role2: department=Duplicate role (role shortname "role2" already defined)',
                'Invalid roleconfig line in tool_profileroles: ' .
                    'role3: nosuchfield=x (no such field "nosuchfield")',
                'Invalid roleconfig line in tool_profileroles: ' .
                    'role4: username~((( (invalid regex "/(((/")']);

        // The system should not mess with roles that were invalid, so it will only change role5.
        $this->assertEquals(['role5' => false], $result);

        // Test a working setup.
        set_config('roleconfig',
                "role1: department=Undead\n" .
                "role2: department ~ [Uu]ndead\n" .
                "  role3   :   department   = Amphibians , frog =     Kermit   \n" .
                "role4:frog=Kermit\n", 'tool_profileroles');

        $result = logic::get_appropriate_roles(['department' => 'Boring department', 'frog' => '']);
        $this->assertEquals(['role1' => false, 'role2' => false, 'role3' => false, 'role4' => false], $result);

        $result = logic::get_appropriate_roles(['department' => 'Undead', 'frog' => '']);
        $this->assertEquals(['role1' => true, 'role2' => true, 'role3' => false, 'role4' => false], $result);

        $result = logic::get_appropriate_roles(['department' => 'Largely undead', 'frog' => '']);
        $this->assertEquals(['role1' => false, 'role2' => true, 'role3' => false, 'role4' => false], $result);

        $result = logic::get_appropriate_roles(['department' => 'Boring department', 'frog' => 'Kermit']);
        $this->assertEquals(['role1' => false, 'role2' => false, 'role3' => true, 'role4' => true], $result);

        $result = logic::get_appropriate_roles(['department' => 'Amphibians', 'frog' => 'Mr Toad']);
        $this->assertEquals(['role1' => false, 'role2' => false, 'role3' => true, 'role4' => false], $result);
    }

    /**
     * Tests the apply_roles function.
     */
    public function test_apply_roles() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $u1 = $generator->create_user();
        $u2 = $generator->create_user();

        $context = \context_system::instance();

        $managerid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $coursecreatorid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);

        // User initially has no system roles.
        $this->assertFalse(user_has_role_assignment($u1->id, $managerid, $context->id));
        $this->assertFalse(user_has_role_assignment($u1->id, $coursecreatorid, $context->id));

        // Add them both.
        logic::apply_roles($u1->id, ['manager' => true, 'coursecreator' => true]);
        $this->assertTrue(user_has_role_assignment($u1->id, $managerid, $context->id));
        $this->assertTrue(user_has_role_assignment($u1->id, $coursecreatorid, $context->id));

        // Now remove one of them.
        logic::apply_roles($u1->id, ['manager' => true, 'coursecreator' => false]);
        $this->assertTrue(user_has_role_assignment($u1->id, $managerid, $context->id));
        $this->assertFalse(user_has_role_assignment($u1->id, $coursecreatorid, $context->id));

        // Remove one and add the other.
        logic::apply_roles($u1->id, ['manager' => false, 'coursecreator' => true]);
        $this->assertFalse(user_has_role_assignment($u1->id, $managerid, $context->id));
        $this->assertTrue(user_has_role_assignment($u1->id, $coursecreatorid, $context->id));

        // Just check we can set up u2 with different roles.
        $this->assertFalse(user_has_role_assignment($u2->id, $managerid, $context->id));
        $this->assertFalse(user_has_role_assignment($u2->id, $coursecreatorid, $context->id));
        logic::apply_roles($u2->id, ['manager' => true, 'coursecreator' => true]);
        $this->assertTrue(user_has_role_assignment($u2->id, $managerid, $context->id));
        $this->assertTrue(user_has_role_assignment($u2->id, $coursecreatorid, $context->id));

        // Only roles that are mentioned will be added/removed.
        logic::apply_roles($u2->id, ['manager' => false]);
        $this->assertFalse(user_has_role_assignment($u2->id, $managerid, $context->id));
        $this->assertTrue(user_has_role_assignment($u2->id, $coursecreatorid, $context->id));

        // If we mention a bogus shortname, it will throw exception.
        try {
            logic::apply_roles($u2->id, ['frog' => true]);
            $this->fail();
        } catch (\moodle_exception $e) {
            $this->assertEquals('Role does not exist: frog', $e->getMessage());
        }
    }
}

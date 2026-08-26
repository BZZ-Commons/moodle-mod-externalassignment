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

namespace mod_externalassignment\local;

use mod_externalassignment\external\update_grade;

/**
 * Unit tests for class update_grade
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_grade_test extends \advanced_testcase {
    /**
     * Test that a valid update from an external system is applied when the assignment is not overdue.
     * @covers \mod_externalassignment\external\update_grade::execute
     */
    public function test_execute_not_cutoff(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('external_username', 'github_user', 'mod_externalassignment');
        $field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'github_user',
            'name' => 'GitHub username',
        ]);

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(
            [
                'course' => $course->id,
                'externalname' => 'externalname',
                'externalgrademax' => 100,
                'cutoffdate' => time() + 3600,
            ]
        );

        $user = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $DB->insert_record('user_info_data', [
            'userid' => $user->id,
            'fieldid' => $field->id,
            'data' => 'octocat',
            'dataformat' => FORMAT_MOODLE,
        ]);

        $result = update_grade::execute('externalname', 'octocat', 45.0, 100.0, 'https://example.com/repo', '');

        $this->assertEquals('info', $result['type']);

        $grade = $DB->get_record('externalassignment_grades', [
            'externalassignment' => $instance->id,
            'userid' => $user->id,
        ]);
        $this->assertNotEmpty($grade);
        $this->assertEqualsWithDelta(45.0, (float)$grade->externalgrade, 0.001);
        $this->assertEquals('https://example.com/repo', $grade->externallink);
    }
}

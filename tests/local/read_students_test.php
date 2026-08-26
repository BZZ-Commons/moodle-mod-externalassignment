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

use core_external\external_api;
use mod_externalassignment\external\read_students;
use required_capability_exception;

/**
 * Unit tests for the read_students external function
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class read_students_test extends \advanced_testcase {
    /**
     * Creates a course, an externalassignment instance and a teacher who may review grades.
     * @return array [course, instance, teacher]
     */
    private function setup_assignment(): array {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        return [$course, $instance, $teacher];
    }

    /**
     * Inserts a grade record for a student, marking them as "done".
     * @param object $instance the externalassignment instance
     * @param int $userid
     * @return void
     */
    private function grade_student(object $instance, int $userid): void {
        global $DB;
        $DB->insert_record('externalassignment_grades', [
            'externalassignment' => $instance->id,
            'userid' => $userid,
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 42,
            'manualgrade' => 0,
        ]);
    }

    /**
     * Test that the students are returned sorted by the requested field and direction
     * @covers \mod_externalassignment\external\read_students::execute
     */
    public function test_execute_sorts_students(): void {
        $this->resetAfterTest(true);
        [$course, $instance, $teacher] = $this->setup_assignment();

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Smith']);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $this->setUser($teacher);

        $result = read_students::execute($instance->cmid, 'lastname', 'asc', '');
        $result = external_api::clean_returnvalue(read_students::execute_returns(), $result);
        $this->assertEquals('Doe', $result[0]['lastname']);
        $this->assertEquals('Smith', $result[1]['lastname']);

        $result = read_students::execute($instance->cmid, 'lastname', 'desc', '');
        $result = external_api::clean_returnvalue(read_students::execute_returns(), $result);
        $this->assertEquals('Smith', $result[0]['lastname']);
        $this->assertEquals('Doe', $result[1]['lastname']);

        $result = read_students::execute($instance->cmid, 'firstname', 'asc', '');
        $result = external_api::clean_returnvalue(read_students::execute_returns(), $result);
        $this->assertEquals('Alice', $result[0]['firstname']);
        $this->assertEquals('John', $result[1]['firstname']);
    }

    /**
     * Test that the students can be filtered by their grading status
     * @covers \mod_externalassignment\external\read_students::execute
     */
    public function test_execute_filters_by_status(): void {
        $this->resetAfterTest(true);
        [$course, $instance, $teacher] = $this->setup_assignment();

        $graded = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Graded']);
        $this->getDataGenerator()->enrol_user($graded->id, $course->id);
        $this->grade_student($instance, $graded->id);

        $ungraded = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Ungraded']);
        $this->getDataGenerator()->enrol_user($ungraded->id, $course->id);

        $this->setUser($teacher);

        $result = read_students::execute($instance->cmid, 'lastname', 'asc', '');
        $result = external_api::clean_returnvalue(read_students::execute_returns(), $result);
        $this->assertCount(2, $result);

        $result = read_students::execute($instance->cmid, 'lastname', 'asc', 'done');
        $result = external_api::clean_returnvalue(read_students::execute_returns(), $result);
        $this->assertCount(1, $result);
        $this->assertEquals($graded->id, $result[0]['userid']);
        $this->assertTrue($result[0]['done']);

        $result = read_students::execute($instance->cmid, 'lastname', 'asc', 'open');
        $result = external_api::clean_returnvalue(read_students::execute_returns(), $result);
        $this->assertCount(1, $result);
        $this->assertEquals($ungraded->id, $result[0]['userid']);
        $this->assertFalse($result[0]['done']);
    }

    /**
     * Test that a user without the reviewgrades capability cannot call this function
     * @covers \mod_externalassignment\external\read_students::execute
     */
    public function test_execute_requires_capability(): void {
        $this->resetAfterTest(true);
        [$course, $instance, ] = $this->setup_assignment();

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        $this->setUser($student);

        $this->expectException(required_capability_exception::class);
        read_students::execute($instance->cmid, 'lastname', 'asc', '');
    }
}

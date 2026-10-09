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

namespace mod_externalassignment\task;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the scheduled task that reports duplicate external names (GitHub issue #35).
 *
 * The task used to hard-code the "mdl_" table prefix and MySQL's UUID(), and joined
 * course_modules on the instance id without the module type. That made it read the wrong
 * tables on any site with another prefix (including the PHPUnit and Behat environments), fail
 * on PostgreSQL, and report false duplicates.
 *
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(duplicate_names_task::class, 'find_duplicates')]
#[CoversMethod(duplicate_names_task::class, 'execute')]
final class duplicate_names_task_test extends \advanced_testcase {
    /**
     * A student enrolled in two courses that both use the same external name is reported.
     */
    public function test_reports_duplicate_across_courses(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $student = $generator->create_user();
        foreach (['Course A', 'Course B'] as $fullname) {
            $course = $generator->create_course(['fullname' => $fullname]);
            $generator->create_module('externalassignment', ['course' => $course->id, 'externalname' => 'm319-a01']);
            $generator->enrol_user($student->id, $course->id, 'student');
        }

        $task = new duplicate_names_task();
        $this->assertEquals(['m319-a01' => ['Course A', 'Course B']], $task->find_duplicates());

        $this->expectOutputRegex('/"m319-a01" is duplicated in courses: Course A, Course B/');
        $task->execute();
    }

    /**
     * Teachers are enrolled in many courses and are never graded, so they are ignored.
     */
    public function test_ignores_teachers(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        foreach (['Course A', 'Course B'] as $fullname) {
            $course = $generator->create_course(['fullname' => $fullname]);
            $generator->create_module('externalassignment', ['course' => $course->id, 'externalname' => 'm319-a01']);
            $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        }

        $this->assertSame([], (new duplicate_names_task())->find_duplicates());
    }

    /**
     * A single assignment is not a duplicate - not even if the student has two enrolments in
     * the course, or another activity's course module happens to have the same instance id.
     */
    public function test_no_false_positives(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Course A']);
        $instance = $generator->create_module('externalassignment', ['course' => $course->id, 'externalname' => 'm319-a01']);
        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $DB->set_field('course_modules', 'instance', $instance->id, ['id' => $forum->cmid]);

        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student', 'manual');
        $generator->enrol_user($student->id, $course->id, 'student', 'self');

        $this->assertSame([], (new duplicate_names_task())->find_duplicates());
    }
}

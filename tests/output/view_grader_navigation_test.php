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

namespace mod_externalassignment\output;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class view_grader_navigation
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(view_grader_navigation::class, 'export_for_template')]
final class view_grader_navigation_test extends \advanced_testcase {
    /**
     * Regression test for GitHub issue #16 ("Wrong Due date"). Before the fix, the grader
     * navigation always displayed reset($assign->get_students()) - i.e. whichever enrolled
     * student happened to sort first - instead of the student the teacher had actually selected
     * on the grader page. That meant the name, email and due date shown next to the grading form
     * could belong to someone other than the student being graded.
     */
    public function test_export_for_template_reflects_selected_student(): void {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => 0]);

        // Alice sorts first alphabetically by lastname; the teacher will select Bob instead.
        $first = $this->getDataGenerator()->create_user(
            ['firstname' => 'Alice', 'lastname' => 'Anderson', 'email' => 'alice@example.com']
        );
        $this->getDataGenerator()->enrol_user($first->id, $course->id, 'student');
        $second = $this->getDataGenerator()->create_user(
            ['firstname' => 'Bob', 'lastname' => 'Brown', 'email' => 'bob@example.com']
        );
        $this->getDataGenerator()->enrol_user($second->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $renderer = $PAGE->get_renderer('core');

        $navigation = new view_grader_navigation($module->id, $context, $second->id);
        $data = $navigation->export_for_template($renderer);

        $this->assertEquals($second->id, $data->userid);
        $this->assertEquals('Bob', $data->firstname);
        $this->assertEquals('Brown', $data->lastname);
        $this->assertEquals('bob@example.com', $data->email);
    }

    /**
     * The due date text must stay empty when the assignment has no due date (issue #16).
     */
    public function test_export_for_template_due_text_empty_when_no_duedate(): void {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => 0]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $renderer = $PAGE->get_renderer('core');

        $navigation = new view_grader_navigation($module->id, $context, $student->id);
        $data = $navigation->export_for_template($renderer);

        $this->assertEquals(0, $data->duedate);
        $this->assertEquals('', $data->due_text);
    }

    /**
     * When a due date is set, due_text must be a formatted, non-empty string (issue #16).
     */
    public function test_export_for_template_due_text_set_when_duedate_present(): void {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() + DAYSECS;
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => $duedate]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $renderer = $PAGE->get_renderer('core');

        $navigation = new view_grader_navigation($module->id, $context, $student->id);
        $data = $navigation->export_for_template($renderer);

        $this->assertEquals($duedate, $data->duedate);
        $this->assertNotEmpty($data->due_text);
        $this->assertStringContainsString(get_string('duedate', 'externalassignment'), $data->due_text);
    }
}

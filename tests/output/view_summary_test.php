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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class view_summary, the teacher's overview of an assignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversClass(view_summary::class)]
final class view_summary_test extends \advanced_testcase {
    /**
     * The summary shows the settings and how many of the students have been graded.
     */
    public function test_export_for_template(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externallink' => 'https://www.example.com/a01',
            'externalgrademax' => 80,
            'manualgrademax' => 20,
            'duedate' => time() + DAYSECS + HOURSECS,
        ]);
        foreach ([true, false, false] as $graded) {
            $student = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
            if ($graded) {
                $DB->insert_record('externalassignment_grades', (object)[
                    'externalassignment' => $instance->id,
                    'userid' => $student->id,
                    'grader' => 2,
                    'externallink' => '',
                    'externalgrade' => 50,
                    'manualgrade' => 0,
                ]);
            }
        }
        $context = \context_module::instance($instance->cmid);

        $view = new view_summary($instance->cmid, $context);
        $data = $view->export_for_template($PAGE->get_renderer('core'));

        $this->assertEquals('https://www.example.com/a01', $data->externallink);
        $this->assertEquals(80, $data->externalgrademax);
        $this->assertEquals(20, $data->manualgrademax);
        $this->assertEquals(3, $data->student_count);
        $this->assertEquals(1, $data->graded_count);
        $this->assertEquals("view.php?id={$instance->cmid}&action=grading", $data->link_grading);
        $this->assertStringContainsString(get_string('day'), $data->timeremaining);
        $this->assertEquals($instance->cmid, $view->get_coursemoduleid());
        $this->assertSame($context, $view->get_context());
    }

    /**
     * Once the due date has passed the summary says so.
     */
    public function test_export_for_template_after_duedate(): void {
        global $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() - HOURSECS]);
        $context = \context_module::instance($instance->cmid);

        $data = (new view_summary($instance->cmid, $context))->export_for_template($PAGE->get_renderer('core'));

        $this->assertEquals(get_string('assignmentisdue', 'externalassignment'), $data->timeremaining);
    }

    /**
     * An assignment without a due date is never due - it used to show "Assignment is due"
     * because 0 - time() is negative.
     */
    public function test_export_for_template_without_duedate(): void {
        global $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => 0]);
        $context = \context_module::instance($instance->cmid);

        $data = (new view_summary($instance->cmid, $context))->export_for_template($PAGE->get_renderer('core'));

        $this->assertEquals(get_string('noduedate', 'externalassignment'), $data->timeremaining);
    }
}

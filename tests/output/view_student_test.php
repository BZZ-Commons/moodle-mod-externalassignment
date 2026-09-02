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

use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class view_student
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(view_student::class, 'export_for_template')]
final class view_student_test extends \advanced_testcase {
    /**
     * Regression test for GitHub issue #22 ("Zero division error in student view"): if the
     * teacher configures both the external and manual maximum grade as 0, export_for_template()
     * used to divide the actual grade by that maximum to compute a percentage and crash with a
     * DivisionByZeroError. The percentages must instead fall back to 0.00 without throwing.
     */
    public function test_export_for_template_handles_zero_grademax_without_division_by_zero(): void {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externalgrademax' => 0,
            'manualgrademax' => 0,
        ]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        $assign = new assign(null, $context);
        $assign->load_db($module->id);

        // No grade has been recorded yet - load_db() will simply leave the defaults (0.0) in place.
        $grade = new grade(null);
        $grade->load_db($assign->get_id(), $student->id);

        $renderer = $PAGE->get_renderer('core');
        $view = new view_student($module->id, $context, $assign, $grade, $student->id);

        $data = $view->export_for_template($renderer);

        $this->assertEquals('0.00', $data->externalpercentage);
        $this->assertEquals('0.00', $data->manualpercentage);
        $this->assertEquals('0.00', $data->totalpercentage);
        $this->assertEquals('0.00', $data->passinggrade);
    }

    /**
     * The percentages must still be calculated correctly when the maximum grades are non-zero
     * (baseline behaviour that the zero-division test above must not break).
     */
    public function test_export_for_template_calculates_percentages_with_nonzero_grademax(): void {
        global $PAGE, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externalgrademax' => 100,
            'manualgrademax' => 0,
            'passingpercentage' => 60,
        ]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'grader' => 2,
            'externallink' => 'https://example.com/repo',
            'externalgrade' => 50,
            'manualgrade' => 0,
        ]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        $assign = new assign(null, $context);
        $assign->load_db($module->id);

        $grade = new grade(null);
        $grade->load_db($assign->get_id(), $student->id);

        $renderer = $PAGE->get_renderer('core');
        $view = new view_student($module->id, $context, $assign, $grade, $student->id);

        $data = $view->export_for_template($renderer);

        $this->assertEquals('50.00', $data->externalpercentage);
        $this->assertEquals('50.00', $data->totalpercentage);
    }

    /**
     * Regression test for GitHub issue #27 ("Assignment link"): the link to the external
     * assignment must be part of the data exported to the student view template.
     */
    public function test_export_for_template_exposes_assignment_link(): void {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externallink' => 'https://example.com/the-assignment',
        ]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        $assign = new assign(null, $context);
        $assign->load_db($module->id);

        $grade = new grade(null);
        $grade->load_db($assign->get_id(), $student->id);

        $renderer = $PAGE->get_renderer('core');
        $view = new view_student($module->id, $context, $assign, $grade, $student->id);

        $data = $view->export_for_template($renderer);

        $this->assertEquals('https://example.com/the-assignment', $data->externallink);
    }
}

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

namespace mod_externalassignment\completion;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class custom_completion
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(custom_completion::class, 'get_state')]
final class custom_completion_test extends \advanced_testcase {
    /**
     * Regression test for GitHub issue #26 ("Manual grade": "After changing the manual grade,
     * the completion isn't updated"). custom_completion::get_state() recomputes the completion
     * state from the *current* external+manual grade total every time Moodle asks for it - which
     * is what happens after grade_control::process_feedback() saves a manual grade and calls
     * completion_info::update_state(..., COMPLETION_UNKNOWN, ...). This test drives get_state()
     * directly across "no grade" -> "failing total" -> "a manual grade pushes the total over the
     * passing threshold" to make sure the transition is actually picked up.
     */
    public function test_get_state_reflects_manual_grade_crossing_passing_threshold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        // Max = 100 (external) + 50 (manual) = 150, passing threshold = 150 * 60% = 90.
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externalgrademax' => 100,
            'manualgrademax' => 50,
            'passingpercentage' => 60,
            'needspassinggrade' => 1,
            'completion' => 2,
        ]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        // No grade at all yet: incomplete.
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            (new custom_completion($cm, $student->id))->get_state('needspassinggrade')
        );

        // External grade only (40/150 = below the 90-point threshold): failing.
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 40,
            'manualgrade' => 0,
        ]);
        $this->assertEquals(
            COMPLETION_COMPLETE_FAIL,
            (new custom_completion($cm, $student->id))->get_state('needspassinggrade')
        );

        // The teacher now enters a manual grade that pushes the total (40 + 50 = 90) up to the
        // passing threshold - completion must flip to COMPLETE, not remain stuck on FAIL.
        $DB->set_field(
            'externalassignment_grades',
            'manualgrade',
            50,
            ['externalassignment' => $instance->id, 'userid' => $student->id]
        );
        $this->assertEquals(
            COMPLETION_COMPLETE,
            (new custom_completion($cm, $student->id))->get_state('needspassinggrade')
        );
    }

    /**
     * When the assignment does not require a passing grade, the "needspassinggrade" custom rule
     * is not used by the activity at all (see externalassignment_get_coursemodule_info()), so
     * completion is driven by the main completion state instead of the custom rule. Recording a
     * grade and running the grade-update hook (as externalassignment_update_grades() does whenever
     * a grade is saved) must mark the activity complete.
     */
    public function test_get_state_complete_without_passing_grade_requirement(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'needspassinggrade' => 0,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 1,
            'manualgrade' => 0,
        ]);

        externalassignment_update_grades($instance, $student->id);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);
        $completion = new \completion_info($course);

        $this->assertEquals(
            COMPLETION_COMPLETE,
            $completion->get_data($cm, false, $student->id)->completionstate
        );
    }
}

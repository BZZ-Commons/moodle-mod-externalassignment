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
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class update_grade
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(update_grade::class, 'execute')]
final class update_grade_test extends \advanced_testcase {
    /**
     * Test that a valid update from an external system is applied when the assignment is not overdue.
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

    /**
     * Regression test for GitHub issue #32 ("The grade is only updated if the user is a student
     * but not a grader/teacher"): an update posted for a Moodle user who is a teacher/grader on the
     * course (i.e. has mod/externalassignment:grade) must be rejected instead of being recorded as
     * if they were the submitting student.
     */
    public function test_execute_rejects_teacher_or_grader(): void {
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
        $generator->create_instance(
            [
                'course' => $course->id,
                'externalname' => 'externalname',
                'externalgrademax' => 100,
                'cutoffdate' => time() + 3600,
            ]
        );

        // Enrol the user as an (editing) teacher, not as a student.
        $teacher = $this->getDataGenerator()->create_user(['firstname' => 'Ada', 'lastname' => 'Teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $DB->insert_record('user_info_data', [
            'userid' => $teacher->id,
            'fieldid' => $field->id,
            'data' => 'octocat-teacher',
            'dataformat' => FORMAT_MOODLE,
        ]);

        $result = update_grade::execute('externalname', 'octocat-teacher', 45.0, 100.0, 'https://example.com/repo', '');

        $this->assertEquals('error', $result['type']);
        $this->assertStringContainsString('no_assignment', $result['name']);

        $grade = $DB->get_record('externalassignment_grades', ['userid' => $teacher->id]);
        $this->assertFalse($grade);
    }

    /**
     * Regression test for GitHub issue #30 ("Override"): "Consider overrides during update_grade".
     * A student whose personal cutoffdate override is still in the future must have their grade
     * accepted even though the assignment-wide cutoffdate has already passed.
     */
    public function test_execute_uses_override_cutoffdate_when_assignment_is_overdue(): void {
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
                'cutoffdate' => time() - 3600, // The assignment as a whole is already overdue.
            ]
        );

        $student = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('user_info_data', [
            'userid' => $student->id,
            'fieldid' => $field->id,
            'data' => 'octocat-extended',
            'dataformat' => FORMAT_MOODLE,
        ]);

        // Grant this student a personal extension into the future.
        $generator->create_override_entry([
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'cutoffdate' => time() + 3600,
        ]);

        $result = update_grade::execute('externalname', 'octocat-extended', 45.0, 100.0, 'https://example.com/repo', '');

        $this->assertEquals('info', $result['type']);
        $this->assertStringContainsString('success', $result['name']);

        $grade = $DB->get_record('externalassignment_grades', [
            'externalassignment' => $instance->id,
            'userid' => $student->id,
        ]);
        $this->assertNotEmpty($grade);
        $this->assertEqualsWithDelta(45.0, (float)$grade->externalgrade, 0.001);
    }

    /**
     * Companion test: without an override, the same overdue assignment must reject the update
     * (baseline behaviour that the override test above depends on).
     */
    public function test_execute_rejects_overdue_assignment_without_override(): void {
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
                'cutoffdate' => time() - 3600,
            ]
        );

        $student = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('user_info_data', [
            'userid' => $student->id,
            'fieldid' => $field->id,
            'data' => 'octocat-overdue',
            'dataformat' => FORMAT_MOODLE,
        ]);

        $result = update_grade::execute('externalname', 'octocat-overdue', 45.0, 100.0, 'https://example.com/repo', '');

        $this->assertEquals('warning', $result['type']);
        $this->assertStringContainsString('overdue', $result['name']);

        $grade = $DB->get_record('externalassignment_grades', [
            'externalassignment' => $instance->id,
            'userid' => $student->id,
        ]);
        $this->assertFalse($grade);
    }

    /**
     * Regression test for GitHub issue #39 ("Update grades with manual completion"): posting a
     * grade for an assignment whose completion tracking is set to "Students manually mark the
     * activity as completed" must not throw completion_info's "Unexpected manual completion
     * state ...: -1" error. update_grades() used to unconditionally call
     * completion_info::update_state(..., COMPLETION_UNKNOWN, ...) to ask completion_info to
     * recompute the state - but COMPLETION_UNKNOWN is only a valid value for *automatic*
     * tracking; manual tracking only ever accepts an explicit complete/incomplete and is driven
     * by the student's own toggle, not by grades.
     */
    public function test_execute_does_not_error_with_manual_completion(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('external_username', 'github_user', 'mod_externalassignment');
        $field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'github_user',
            'name' => 'GitHub username',
        ]);

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(
            [
                'course' => $course->id,
                'externalname' => 'externalname',
                'externalgrademax' => 100,
                'completion' => COMPLETION_TRACKING_MANUAL,
            ]
        );

        $student = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('user_info_data', [
            'userid' => $student->id,
            'fieldid' => $field->id,
            'data' => 'octocat-manual',
            'dataformat' => FORMAT_MOODLE,
        ]);

        // This must not throw completion_info's "Unexpected manual completion state" error.
        $result = update_grade::execute('externalname', 'octocat-manual', 45.0, 100.0, 'https://example.com/repo', '');

        $this->assertEquals('info', $result['type']);
        $this->assertStringContainsString('success', $result['name']);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);
        $completion = new \completion_info($course);

        // Manual tracking is driven by the student's own toggle, not by grades - updating the
        // grade must leave the completion state untouched (still incomplete, since the student
        // has not marked the activity complete themselves).
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $completion->get_data($cm, false, $student->id)->completionstate
        );
    }

    /**
     * Companion test for GitHub issue #39: the fix above must only skip the completion update for
     * *manual* tracking - automatic tracking must still have its state recomputed exactly as
     * before, so a passing grade posted through the webservice still marks the activity complete.
     */
    public function test_execute_still_updates_automatic_completion(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('external_username', 'github_user', 'mod_externalassignment');
        $field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'github_user',
            'name' => 'GitHub username',
        ]);

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(
            [
                'course' => $course->id,
                'externalname' => 'externalname',
                'externalgrademax' => 100,
                'manualgrademax' => 0,
                'passingpercentage' => 40,
                'needspassinggrade' => 1,
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
            ]
        );

        $student = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('user_info_data', [
            'userid' => $student->id,
            'fieldid' => $field->id,
            'data' => 'octocat-auto',
            'dataformat' => FORMAT_MOODLE,
        ]);

        // 45/100 clears the 40% passing threshold.
        $result = update_grade::execute('externalname', 'octocat-auto', 45.0, 100.0, 'https://example.com/repo', '');

        $this->assertEquals('info', $result['type']);
        $this->assertStringContainsString('success', $result['name']);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);
        $completion = new \completion_info($course);

        $this->assertNotEquals(
            COMPLETION_INCOMPLETE,
            $completion->get_data($cm, false, $student->id)->completionstate,
            'Automatic completion must still be recomputed and reflect the passing grade.'
        );
    }
}

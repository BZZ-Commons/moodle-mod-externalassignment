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

namespace mod_externalassignment\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use core_privacy\local\request\approved_userlist;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the mod_externalassignment privacy provider.
 *
 * Regression coverage for GitHub issue #19 ("Missed Privacy API implementation"): the plugin
 * stores personal data (grades, feedback, per-student overrides) and must correctly report,
 * export and delete it via the Privacy API.
 *
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(provider::class, 'get_contexts_for_userid')]
#[CoversMethod(provider::class, 'get_users_in_context')]
#[CoversMethod(provider::class, 'export_user_data')]
#[CoversMethod(provider::class, 'delete_data_for_all_users_in_context')]
#[CoversMethod(provider::class, 'delete_data_for_user')]
#[CoversMethod(provider::class, 'delete_data_for_users')]
final class provider_test extends provider_testcase {
    public function test_get_contexts_for_userid_finds_context_with_a_grade(): void {
        [, , $context, $student] = $this->create_assignment_with_grade();

        $contextlist = provider::get_contexts_for_userid($student->id);

        $this->assertContains((string) $context->id, $contextlist->get_contextids());
    }

    public function test_get_contexts_for_userid_empty_for_unrelated_user(): void {
        $this->create_assignment_with_grade();
        $unrelated = self::getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($unrelated->id);

        $this->assertEmpty($contextlist->get_contextids());
    }

    public function test_get_users_in_context_lists_graded_student(): void {
        [, , $context, $student] = $this->create_assignment_with_grade();

        $userlist = new \core_privacy\local\request\userlist($context, 'mod_externalassignment');
        provider::get_users_in_context($userlist);

        $this->assertContains((int) $student->id, $userlist->get_userids());
    }

    public function test_export_user_data_exports_grade_information(): void {
        [, , $context, $student] = $this->create_assignment_with_grade();

        $approvedlist = new approved_contextlist($student, 'mod_externalassignment', [$context->id]);
        provider::export_user_data($approvedlist);

        $this->assertTrue(writer::with_context($context)->has_any_data());
    }

    public function test_delete_data_for_all_users_in_context_removes_all_grades(): void {
        global $DB;
        [, $instance, $context] = $this->create_assignment_with_grade();

        $this->assertNotEmpty($DB->get_records('externalassignment_grades', ['externalassignment' => $instance->id]));

        provider::delete_data_for_all_users_in_context($context);

        $this->assertEmpty($DB->get_records('externalassignment_grades', ['externalassignment' => $instance->id]));
    }

    public function test_delete_data_for_user_only_removes_that_users_grade(): void {
        global $DB;
        [$course, $instance, $context, $student] = $this->create_assignment_with_grade();

        $other = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($other->id, $course->id, 'student');
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $other->id,
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 5,
            'manualgrade' => 0,
        ]);

        $approvedlist = new approved_contextlist($student, 'mod_externalassignment', [$context->id]);
        provider::delete_data_for_user($approvedlist);

        $this->assertEmpty(
            $DB->get_records('externalassignment_grades', ['externalassignment' => $instance->id, 'userid' => $student->id])
        );
        $this->assertNotEmpty(
            $DB->get_records('externalassignment_grades', ['externalassignment' => $instance->id, 'userid' => $other->id])
        );
    }

    public function test_delete_data_for_users_only_removes_listed_users_grades(): void {
        global $DB;
        [$course, $instance, $context, $student] = $this->create_assignment_with_grade();

        $other = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($other->id, $course->id, 'student');
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $other->id,
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 5,
            'manualgrade' => 0,
        ]);

        $userlist = new approved_userlist($context, 'mod_externalassignment', [$student->id]);
        provider::delete_data_for_users($userlist);

        $this->assertEmpty(
            $DB->get_records('externalassignment_grades', ['externalassignment' => $instance->id, 'userid' => $student->id])
        );
        $this->assertNotEmpty(
            $DB->get_records('externalassignment_grades', ['externalassignment' => $instance->id, 'userid' => $other->id])
        );
    }

    /**
     * A student who only has an override (an extension) but no grade yet must be exportable.
     */
    public function test_export_user_data_with_override_only(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $generator = self::getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $student = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $duedate = time() + WEEKSECS;
        $generator->create_override_entry([
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'duedate' => $duedate,
        ]);
        $context = \context_module::instance($instance->cmid);

        $contextlist = provider::get_contexts_for_userid($student->id);
        $this->assertEquals([$context->id], array_values($contextlist->get_contextids()));

        provider::export_user_data(new approved_contextlist($student, 'mod_externalassignment', [$context->id]));

        $subcontext = [get_string('privacy:export:externalassignment:overrides', 'externalassignment')];
        $data = writer::with_context($context)->get_data($subcontext);
        $this->assertEquals(\core_privacy\local\request\transform::datetime($duedate), $data->duedate);
    }

    /**
     * Privacy requests are typically made for users who have left the course: a graded user who
     * is no longer enrolled must still be exportable.
     */
    public function test_export_user_data_for_unenrolled_user(): void {
        [$course, , $context, $student] = $this->create_assignment_with_grade();
        $enrol = enrol_get_plugin('manual');
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $enrol->unenrol_user($instance, $student->id);
            }
        }

        provider::export_user_data(new approved_contextlist($student, 'mod_externalassignment', [$context->id]));

        $subcontext = [get_string('privacy:export:externalassignment:grades', 'externalassignment')];
        $data = writer::with_context($context)->get_data($subcontext);
        $this->assertEquals(42, $data->externalgrade);
    }

    /**
     * Course module instance ids are only unique per module type. The context of another
     * activity whose instance id happens to equal the externalassignment id must not be
     * reported, otherwise deleting that activity's data deletes external assignment grades.
     */
    public function test_contexts_and_users_ignore_other_modules_with_same_instance_id(): void {
        global $DB;
        [$course, $instance, $context, $student] = $this->create_assignment_with_grade();
        $forum = self::getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $DB->set_field('course_modules', 'instance', $instance->id, ['id' => $forum->cmid]);
        $forumcontext = \context_module::instance($forum->cmid);

        $contextids = provider::get_contexts_for_userid($student->id)->get_contextids();
        $this->assertEquals([$context->id], array_values($contextids));

        $userlist = new \core_privacy\local\request\userlist($forumcontext, 'mod_externalassignment');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());

        $approved = new approved_userlist($forumcontext, 'mod_externalassignment', [$student->id]);
        provider::delete_data_for_users($approved);
        $this->assertTrue($DB->record_exists('externalassignment_grades', ['userid' => $student->id]));

        provider::delete_data_for_user(
            new approved_contextlist($student, 'mod_externalassignment', [$forumcontext->id])
        );
        $this->assertTrue($DB->record_exists('externalassignment_grades', ['userid' => $student->id]));
    }

    /**
     * get_users_in_context() must also report users who only have an override.
     */
    public function test_get_users_in_context_lists_user_with_override_only(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $generator = self::getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $student = self::getDataGenerator()->create_user();
        $generator->create_override_entry(['externalassignment' => $instance->id, 'userid' => $student->id]);
        $context = \context_module::instance($instance->cmid);

        $userlist = new \core_privacy\local\request\userlist($context, 'mod_externalassignment');
        provider::get_users_in_context($userlist);

        $this->assertEquals([$student->id], $userlist->get_userids());
    }

    /**
     * Creates a course with one externalassignment instance, one enrolled student and a recorded
     * grade for that student.
     * @return array [course, instance, module context, student]
     */
    private function create_assignment_with_grade(): array {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = self::getDataGenerator()->create_course();
        $generator = self::getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $student = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'grader' => 2,
            'externallink' => 'https://example.com/repo',
            'externalgrade' => 42,
            'manualgrade' => 0,
        ]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        return [$course, $instance, $context, $student];
    }
}

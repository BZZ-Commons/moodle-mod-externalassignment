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

namespace mod_externalassignment;

use backup;
use backup_controller;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use restore_controller;
use restore_dbops;

/**
 * Unit tests for the classes in backup/moodle2.
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter <marcel.suter@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversClass(\backup_externalassignment_activity_task::class)]
#[CoversClass(\backup_externalassignment_activity_structure_step::class)]
#[CoversClass(\restore_externalassignment_activity_task::class)]
#[CoversClass(\restore_externalassignment_activity_structure_step::class)]
final class backup_restore_test extends \advanced_testcase {
    /**
     * Load the backup and restore classes.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
        require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
        require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');
        require_once($CFG->dirroot . '/mod/externalassignment/backup/moodle2/backup_externalassignment_activity_task.class.php');
        require_once($CFG->dirroot . '/mod/externalassignment/backup/moodle2/restore_externalassignment_activity_task.class.php');
    }

    /**
     * Backs up a course and restores it into a new course.
     *
     * @param int $courseid the course to back up
     * @param bool $users whether user data is part of the backup
     * @param int|null $startdate the start date of the restored course, null to keep the original
     * @return int the id of the new course
     */
    private function backup_and_restore(int $courseid, bool $users = true, ?int $startdate = null): int {
        global $DB;
        $userid = get_admin()->id;
        $backupid = 'backup_restore_test_' . $courseid . ($users ? 'u' : 'n');

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $courseid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid
        );
        $bc->get_plan()->get_setting('users')->set_value($users);
        $bc->execute_plan();
        $results = $bc->get_results();
        $results['backup_destination']->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            make_backup_temp_directory('') . '/' . $backupid
        );
        $bc->destroy();

        $categoryid = $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}');
        $newcourseid = restore_dbops::create_new_course('Restored course', 'restored' . $backupid, $categoryid);
        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid,
            backup::TARGET_NEW_COURSE
        );
        if ($startdate !== null) {
            $rc->get_plan()->get_setting('course_startdate')->set_value($startdate);
        }
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
        return $newcourseid;
    }

    /**
     * Links to the activity and to the activity index must be encoded for the backup.
     */
    public function test_encode_content_links(): void {
        global $CFG;
        $base = $CFG->wwwroot;
        $content = "See {$base}/mod/externalassignment/view.php?id=42 and {$base}/mod/externalassignment/index.php?id=7"
            . " but not {$base}/mod/assign/view.php?id=5 or https://example.com/mod/externalassignment/view.php?id=9.";

        $encoded = \backup_externalassignment_activity_task::encode_content_links($content);

        $this->assertStringContainsString('$@EXTERNALASSIGNMENTVIEWBYID*42@$', $encoded);
        $this->assertStringContainsString('$@EXTERNALASSIGNMENTINDEX*7@$', $encoded);
        $this->assertStringContainsString("{$base}/mod/assign/view.php?id=5", $encoded);
        $this->assertStringContainsString('https://example.com/mod/externalassignment/view.php?id=9', $encoded);
    }

    /**
     * Every token the backup writes must have a matching decode rule in the restore.
     */
    public function test_decode_rules_match_the_encoded_tokens(): void {
        global $CFG;
        $encoded = \backup_externalassignment_activity_task::encode_content_links(
            "{$CFG->wwwroot}/mod/externalassignment/view.php?id=3 {$CFG->wwwroot}/mod/externalassignment/index.php?id=4"
        );
        preg_match_all('/\$@([A-Z]+)\*/', $encoded, $matches);
        $this->assertCount(2, $matches[1]);

        $rulenames = [];
        foreach (\restore_externalassignment_activity_task::define_decode_rules() as $rule) {
            $rulenames[] = (new \ReflectionProperty($rule, 'linkname'))->getValue($rule);
        }
        foreach ($matches[1] as $token) {
            $this->assertContains($token, $rulenames, "No restore decode rule for the backup token $token");
        }
    }

    /**
     * Only the description of the activity is decoded when restoring.
     */
    public function test_decode_contents(): void {
        $contents = \restore_externalassignment_activity_task::define_decode_contents();
        $this->assertCount(1, $contents);
        $this->assertSame('externalassignment', (new \ReflectionProperty($contents[0], 'tablename'))->getValue($contents[0]));
        $this->assertSame(['intro'], (new \ReflectionProperty($contents[0], 'fields'))->getValue($contents[0]));
    }

    /**
     * The restore log rules cover the module's actions and the course level "view all" rules.
     */
    public function test_restore_log_rules(): void {
        $rules = \restore_externalassignment_activity_task::define_restore_log_rules();
        $this->assertNotEmpty($rules);
        foreach ($rules as $rule) {
            $this->assertInstanceOf(\restore_log_rule::class, $rule);
            $this->assertSame('externalassignment', (new \ReflectionProperty($rule, 'module'))->getValue($rule));
        }

        $courserules = \restore_externalassignment_activity_task::define_restore_log_rules_for_course();
        $this->assertCount(2, $courserules);
        foreach ($courserules as $rule) {
            $this->assertInstanceOf(\restore_log_rule::class, $rule);
        }
    }

    /**
     * All settings of the activity survive a backup and restore into a new course.
     */
    public function test_settings_survive_backup_and_restore(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Original',
            'externalname' => 'm123-original',
            'externallink' => 'https://example.com/task',
            'alwaysshowdescription' => 0,
            'alwaysshowlink' => 0,
            'allowsubmissionsfromdate' => time() + HOURSECS,
            'duedate' => time() + DAYSECS,
            'cutoffdate' => time() + 2 * DAYSECS,
            'externalgrademax' => 80,
            'manualgrademax' => 15,
            'passingpercentage' => 55,
            'needspassinggrade' => 0,
        ]);
        $original = $DB->get_record('externalassignment', ['id' => $instance->id], '*', MUST_EXIST);

        $newcourseid = $this->backup_and_restore($course->id);

        $restored = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertNotEquals($original->id, $restored->id);
        foreach (
            [
                'name', 'externalname', 'externallink', 'alwaysshowdescription', 'alwaysshowlink',
                'allowsubmissionsfromdate', 'duedate', 'cutoffdate', 'externalgrademax', 'manualgrademax',
                'passingpercentage', 'needspassinggrade',
            ] as $field
        ) {
            $this->assertEquals($original->$field, $restored->$field, "Field $field was not restored");
        }
    }

    /**
     * Without user data neither grades nor extensions are part of the backup.
     */
    public function test_restore_without_user_data_skips_grades_and_overrides(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'grader' => get_admin()->id,
            'externallink' => '',
            'externalgrade' => 42,
            'manualgrade' => 0,
        ]);
        $generator->create_override_entry(['externalassignment' => $instance->id, 'userid' => $student->id]);

        $newcourseid = $this->backup_and_restore($course->id, false);

        $restored = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertFalse($DB->record_exists('externalassignment_grades', ['externalassignment' => $restored->id]));
        $this->assertFalse($DB->record_exists('externalassignment_overrides', ['externalassignment' => $restored->id]));
        // The original data is untouched.
        $this->assertTrue($DB->record_exists('externalassignment_grades', ['externalassignment' => $instance->id]));
    }

    /**
     * Grades and extensions of users that cannot be restored are skipped instead of failing.
     */
    public function test_restore_skips_grades_and_overrides_of_unknown_users(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $missinguserid = $student->id + 1000;
        foreach ([$student->id, $missinguserid] as $userid) {
            $DB->insert_record('externalassignment_grades', (object)[
                'externalassignment' => $instance->id,
                'userid' => $userid,
                'grader' => get_admin()->id,
                'externallink' => '',
                'externalgrade' => 10,
                'manualgrade' => 0,
            ]);
            $generator->create_override_entry(['externalassignment' => $instance->id, 'userid' => $userid]);
        }

        $newcourseid = $this->backup_and_restore($course->id);

        $restored = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $grades = $DB->get_records('externalassignment_grades', ['externalassignment' => $restored->id]);
        $this->assertCount(1, $grades);
        $this->assertEquals($student->id, reset($grades)->userid);
        $overrides = $DB->get_records('externalassignment_overrides', ['externalassignment' => $restored->id]);
        $this->assertCount(1, $overrides);
        $this->assertEquals($student->id, reset($overrides)->userid);
    }

    /**
     * A restored grade without a grader keeps grader 0 instead of an unmapped user id.
     */
    public function test_restore_keeps_missing_grader_as_zero(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'grader' => 0,
            'externallink' => '',
            'externalgrade' => 10,
            'manualgrade' => 0,
        ]);

        $newcourseid = $this->backup_and_restore($course->id);

        $restored = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $grade = $DB->get_record('externalassignment_grades', ['externalassignment' => $restored->id], '*', MUST_EXIST);
        $this->assertEquals(0, $grade->grader);
    }

    /**
     * The files of the description are backed up and restored.
     */
    public function test_description_files_are_restored(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($instance->cmid)->id,
            'component' => 'mod_externalassignment',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'task.txt',
        ], 'the task description');

        $newcourseid = $this->backup_and_restore($course->id);

        $restored = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('externalassignment', $restored->id);
        $file = get_file_storage()->get_file(
            \context_module::instance($cm->id)->id,
            'mod_externalassignment',
            'intro',
            0,
            '/',
            'task.txt'
        );
        $this->assertNotFalse($file);
        $this->assertSame('the task description', $file->get_content());
    }

    /**
     * Links to the activity inside the description point to the restored copy.
     */
    public function test_links_in_the_description_are_decoded(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $DB->set_field('externalassignment', 'intro', "See {$CFG->wwwroot}/mod/externalassignment/view.php?id={$instance->cmid}", [
            'id' => $instance->id,
        ]);

        $newcourseid = $this->backup_and_restore($course->id);

        $restored = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('externalassignment', $restored->id);
        $this->assertEquals(
            "See {$CFG->wwwroot}/mod/externalassignment/view.php?id={$newcm->id}",
            $restored->intro
        );
    }

    /**
     * Restoring into the course that already contains an activity with the same external name
     * renames the copy, so that the external names stay unique within a course.
     */
    public function test_duplicate_external_name_is_replaced_on_restore(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'externalname' => 'm123-unique']);

        \core_courseformat\formatactions::cm($course->id)->duplicate($instance->cmid);

        $names = $DB->get_fieldset_select('externalassignment', 'externalname', 'course = ?', [$course->id]);
        $this->assertCount(2, $names);
        $this->assertContains('m123-unique', $names);
        $this->assertContains('FIXME', $names);
    }
}

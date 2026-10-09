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
use mod_externalassignment\local\assign_control;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use restore_controller;
use restore_dbops;

/**
 * Unit tests for lib.php, in particular externalassignment_refresh_events().
 *
 * These are regression tests for GitHub issue #31 ("No calendar event on duplication"): when
 * duplicating an external assignment (or restoring a course that contains one), no "due" calendar
 * event was created for the copy. Action-type calendar events are deliberately excluded from
 * activity backups (see restore_calendarevents_structure_step in Moodle core), so the fix is to
 * implement the *_refresh_events() hook that core's core\task\refresh_mod_calendar_events_task
 * adhoc task calls automatically at the end of every restore/duplication.
 *
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversFunction('externalassignment_add_instance')]
#[CoversFunction('externalassignment_delete_instance')]
#[CoversFunction('externalassignment_get_coursemodule_info')]
#[CoversFunction('externalassignment_cm_info_view')]
#[CoversFunction('externalassignment_extend_settings_navigation')]
#[CoversFunction('externalassignment_grade_item_update')]
#[CoversFunction('externalassignment_update_grades')]
#[CoversFunction('externalassignment_reset_gradebook')]
#[CoversFunction('externalassignment_refresh_events')]
#[CoversFunction('externalassignment_refresh_instance_events')]
#[CoversFunction('mod_externalassignment_core_calendar_is_event_visible')]
#[CoversFunction('mod_externalassignment_core_calendar_provide_event_action')]
#[CoversMethod(assign_control::class, 'update_calendar_event')]
#[CoversMethod(assign_control::class, 'add_instance')]
#[CoversMethod(assign_control::class, 'delete_instance')]
final class lib_test extends \advanced_testcase {
    /**
     * Load the backup and restore classes.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * externalassignment_refresh_events() must (re-)create the due-date calendar event for a
     * single given instance, exactly as assign_control::add_instance()/update_instance() do.
     */
    public function test_refresh_events_creates_event_for_single_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() + DAYSECS;
        $instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Refresh events assignment',
            'duedate' => $duedate,
        ]);

        // Simulate the situation right after a restore: no calendar event exists yet for the
        // instance, because restoring/duplicating does not recreate action-type events.
        $DB->delete_records('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]);
        $this->assertFalse($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));

        externalassignment_refresh_events(0, $instance->id);

        $event = $DB->get_record('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
        ], '*', MUST_EXIST);
        $this->assertEquals('Refresh events assignment is due', $event->name);
        $this->assertEquals($duedate, $event->timestart);
        $this->assertEquals($course->id, $event->courseid);
    }

    /**
     * Passing an instance object (rather than an id) must work identically.
     */
    public function test_refresh_events_accepts_instance_object(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() + DAYSECS;
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => $duedate]);

        $DB->delete_records('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id], '*', MUST_EXIST);
        externalassignment_refresh_events(0, $record);

        $this->assertTrue($DB->record_exists('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
        ]));
    }

    /**
     * Regression test for GitHub issue #37 ("Status: overdue despite overwrite"), as it interacts
     * with issue #31's fix: a student's personal "due" override event (see
     * assign_control::update_override_calendar_event()) is, like the shared event, excluded from
     * activity backups. Without also refreshing it here, a restored/duplicated course would
     * silently drop every student's extension and immediately reintroduce issue #37 for them.
     */
    public function test_refresh_events_recreates_override_events(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() - DAYSECS;
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => $duedate]);

        $overrideduedate = time() + DAYSECS;
        $DB->insert_record('externalassignment_overrides', (object) [
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'allowsubmissionsfromdate' => 0,
            'duedate' => $overrideduedate,
            'cutoffdate' => $overrideduedate,
        ]);

        // Simulate the situation right after a restore: no calendar events exist at all yet.
        $DB->delete_records('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]);

        externalassignment_refresh_events(0, $instance->id);

        $sharedevent = $DB->get_record('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $this->assertEquals($duedate, $sharedevent->timestart);

        $overrideevent = $DB->get_record('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'userid' => $student->id,
        ], '*', MUST_EXIST);
        $this->assertEquals(0, $overrideevent->courseid);
        $this->assertEquals($overrideduedate, $overrideevent->timestart);
    }

    /**
     * With no instance given, externalassignment_refresh_events() must refresh every instance in
     * the given course - this is the code path the refresh_mod_calendar_events_task adhoc task
     * actually uses when it processes a course after a restore/duplication.
     */
    public function test_refresh_events_for_whole_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');

        $duedate1 = time() + DAYSECS;
        $duedate2 = time() + (2 * DAYSECS);
        $instance1 = $generator->create_instance([
            'course' => $course->id,
            'externalname' => 'assignment-one',
            'duedate' => $duedate1,
        ]);
        $instance2 = $generator->create_instance([
            'course' => $course->id,
            'externalname' => 'assignment-two',
            'duedate' => $duedate2,
        ]);
        // An instance in a different course must not be touched when refreshing $course.
        $otherinstance = $generator->create_instance([
            'course' => $othercourse->id,
            'externalname' => 'assignment-other',
            'duedate' => time() + DAYSECS,
        ]);

        $DB->delete_records('event', ['modulename' => 'externalassignment']);
        $this->assertEquals(0, $DB->count_records('event', ['modulename' => 'externalassignment']));

        externalassignment_refresh_events($course->id);

        $this->assertTrue($DB->record_exists('event', [
            'modulename' => 'externalassignment', 'instance' => $instance1->id, 'eventtype' => 'due',
        ]));
        $this->assertTrue($DB->record_exists('event', [
            'modulename' => 'externalassignment', 'instance' => $instance2->id, 'eventtype' => 'due',
        ]));
        $this->assertFalse($DB->record_exists('event', [
            'modulename' => 'externalassignment', 'instance' => $otherinstance->id, 'eventtype' => 'due',
        ]));
    }

    /**
     * End-to-end regression test for GitHub issue #31: duplicating an activity (the same
     * \core_courseformat\formatactions::cm()->duplicate() call the "Duplicate" UI action and the
     * "I duplicate ... activity" Behat step use) must result in a calendar event for the copy.
     *
     * Note: duplicate() always renames the copy (appending "(copy)" unless a $newname is given),
     * and that rename step calls course_module_update_calendar_events() synchronously - which is
     * what actually invokes externalassignment_refresh_events() here, before any adhoc task runs.
     * A plain course restore (tested separately below) has no such rename step, so it depends on
     * the adhoc task instead.
     */
    public function test_duplicating_activity_creates_calendar_event_for_the_copy(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() + DAYSECS;
        $instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Original assignment',
            'externalname' => 'original-external-name',
            'duedate' => $duedate,
        ]);

        $cm = get_coursemodule_from_instance('externalassignment', $instance->id, $course->id, false, MUST_EXIST);

        $newcm = \core_courseformat\formatactions::cm($course->id)->duplicate($cm->id);
        $this->assertNotNull($newcm, 'Duplicating the activity must succeed.');

        $newinstance = $DB->get_record('externalassignment', ['id' => $newcm->instance], '*', MUST_EXIST);
        $this->assertNotEquals($instance->id, $newinstance->id);
        $this->assertEquals($duedate, $newinstance->duedate);

        $event = $DB->get_record('event', [
            'modulename' => 'externalassignment',
            'instance' => $newinstance->id,
            'eventtype' => 'due',
        ], '*', MUST_EXIST);
        $this->assertEquals($newinstance->name . ' is due', $event->name);
        $this->assertEquals($duedate, $event->timestart);
        $this->assertEquals($course->id, $event->courseid);

        // The original assignment's own event must be unaffected.
        $this->assertTrue($DB->record_exists('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
        ]));
    }

    /**
     * End-to-end regression test for GitHub issue #31, covering the plain "backup a course and
     * restore it into a new course" path (as opposed to the single-activity "duplicate" tested
     * above). This path does not rename the restored activity, so there is nothing to trigger a
     * synchronous calendar refresh - the copy's event only appears once the
     * core\task\refresh_mod_calendar_events_task adhoc task (queued by Moodle's own
     * restore_calendar_action_events restore step) has been processed.
     */
    public function test_restoring_a_course_creates_calendar_events_after_adhoc_tasks_run(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() + DAYSECS;
        $generator->create_instance([
            'course' => $course->id,
            'name' => 'Backup and restore assignment',
            'duedate' => $duedate,
        ]);

        $userid = get_admin()->id;
        $backuptempdir = make_backup_temp_directory('');
        $packer = get_file_packer('application/vnd.moodle.backup');

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $results['backup_destination']->extract_to_pathname($packer, "$backuptempdir/issue31_backup_test");
        $bc->destroy();
        $backupid = 'issue31_backup_test';

        $categoryid = $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}');
        $newcourseid = restore_dbops::create_new_course('Restored course', 'restoredcourse', $categoryid);

        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $newinstance = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);

        // Immediately after restore, before adhoc tasks have run, the restored copy's event does
        // not exist yet - this is the exact symptom reported in issue #31.
        $this->assertFalse($DB->record_exists('event', [
            'modulename' => 'externalassignment',
            'instance' => $newinstance->id,
            'eventtype' => 'due',
        ]), 'The restored copy must not have a calendar event yet before adhoc tasks have run.');

        // The adhoc tasks (including core's own refresh_mod_calendar_events_task) mtrace()
        // progress output; suppress it so it doesn't get flagged as unexpected test output.
        ob_start();
        $this->run_all_adhoc_tasks();
        ob_end_clean();

        $event = $DB->get_record('event', [
            'modulename' => 'externalassignment',
            'instance' => $newinstance->id,
            'eventtype' => 'due',
        ], '*', MUST_EXIST);
        $this->assertEquals($newinstance->name . ' is due', $event->name);
        $this->assertEquals($duedate, $event->timestart);
        $this->assertEquals($newcourseid, $event->courseid);
    }

    /**
     * An assignment without a due date must not get an "is due" calendar event. The duedate
     * column is NOT NULL DEFAULT 0, so "no due date" is 0, never null - the event used to be
     * created at timestamp 0 (1 January 1970) and showed up as overdue.
     */
    public function test_no_calendar_event_without_duedate(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => 0]);

        $this->assertFalse($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));

        externalassignment_refresh_events(0, $instance->id);
        $this->assertFalse($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));
    }

    /**
     * Removing the due date (e.g. with report_editdates, GitHub issue #38) must delete the
     * existing "is due" event instead of moving it to 1 January 1970.
     */
    public function test_removing_duedate_deletes_calendar_event(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);
        $this->assertTrue($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));

        $DB->set_field('externalassignment', 'duedate', 0, ['id' => $instance->id]);
        externalassignment_refresh_events(0, $instance->id);

        $this->assertFalse($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));
    }

    /**
     * Creating the shared "is due" event is part of saving the activity, not a calendar action
     * of the current user: it must not depend on moodle/calendar:manageentries (otherwise e.g.
     * a restore running as a user without that capability fails).
     */
    public function test_calendar_event_created_without_calendar_capability(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setUser($this->getDataGenerator()->create_user());

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);

        $this->assertTrue($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));
    }

    /**
     * externalassignment_update_grades() is the hook core calls to push a module's grades to the
     * gradebook (e.g. when regrading). It must actually write the grades.
     */
    public function test_update_grades_pushes_grades_to_gradebook(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $students = [];
        foreach ([42, 17] as $points) {
            $student = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
            $DB->insert_record('externalassignment_grades', (object)[
                'externalassignment' => $instance->id,
                'userid' => $student->id,
                'grader' => 2,
                'externallink' => '',
                'externalgrade' => $points,
                'manualgrade' => 3,
            ]);
            $students[$student->id] = $points + 3;
        }

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $firstid = array_key_first($students);
        externalassignment_update_grades($record, $firstid);
        $grades = grade_get_grades($course->id, 'mod', 'externalassignment', $instance->id, array_keys($students));
        $this->assertEquals(45, $grades->items[0]->grades[$firstid]->grade);
        $this->assertNull($grades->items[0]->grades[array_key_last($students)]->grade);

        // Userid 0 means all users.
        externalassignment_update_grades($record);
        $grades = grade_get_grades($course->id, 'mod', 'externalassignment', $instance->id, array_keys($students));
        foreach ($students as $userid => $total) {
            $this->assertEquals($total, $grades->items[0]->grades[$userid]->grade);
        }
    }

    /**
     * Updating the grades must never mark the activity as complete for the acting user (it used
     * to call update_state(COMPLETION_COMPLETE) for userid 0, i.e. the current user).
     */
    public function test_update_grades_does_not_complete_activity_for_current_user(): void {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        externalassignment_update_grades($DB->get_record('externalassignment', ['id' => $instance->id]));

        $this->assertFalse($DB->record_exists('course_modules_completion', [
            'coursemoduleid' => $instance->cmid,
            'userid' => $USER->id,
        ]));
    }

    /**
     * Resetting the gradebook of a course must work (it used to call mod_assign's
     * assign_grade_item_update(), which is not defined unless mod_assign happens to be loaded).
     */
    public function test_reset_gradebook(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        externalassignment_grade_item_update(
            $DB->get_record('externalassignment', ['id' => $instance->id]),
            (object)['userid' => $student->id, 'rawgrade' => 50]
        );

        externalassignment_reset_gradebook($course->id);

        $grades = grade_get_grades($course->id, 'mod', 'externalassignment', $instance->id, $student->id);
        $this->assertNull($grades->items[0]->grades[$student->id]->grade);
    }

    /**
     * Restoring a course with user data must restore grades and extensions for the restored
     * users and shift the extension dates by the same offset as the assignment's own dates
     * (GitHub issue #14: user ids were copied unmapped and override dates were not shifted).
     */
    public function test_restore_with_user_data_maps_users_and_shifts_override_dates(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $startdate = mktime(0, 0, 0, 9, 1, 2026);
        $course = $this->getDataGenerator()->create_course(['startdate' => $startdate]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = $startdate + WEEKSECS;
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => $duedate]);
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
        $generator->create_override_entry([
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'duedate' => $duedate + DAYSECS,
        ]);

        $userid = get_admin()->id;
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $results['backup_destination']->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            make_backup_temp_directory('') . '/issue14_backup_test'
        );
        $bc->destroy();

        $categoryid = $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}');
        $newcourseid = restore_dbops::create_new_course('Restored course', 'restoredcourse', $categoryid);
        $rc = new restore_controller(
            'issue14_backup_test',
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid,
            backup::TARGET_NEW_COURSE
        );
        $rc->get_plan()->get_setting('course_startdate')->set_value($startdate + 10 * DAYSECS);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $newinstance = $DB->get_record('externalassignment', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertEquals($duedate + 10 * DAYSECS, $newinstance->duedate);

        $grade = $DB->get_record('externalassignment_grades', ['externalassignment' => $newinstance->id], '*', MUST_EXIST);
        $this->assertEquals($student->id, $grade->userid);
        $this->assertEquals($userid, $grade->grader);

        $override = $DB->get_record('externalassignment_overrides', ['externalassignment' => $newinstance->id], '*', MUST_EXIST);
        $this->assertEquals($student->id, $override->userid);
        $this->assertEquals($duedate + DAYSECS + 10 * DAYSECS, $override->duedate);
    }

    /**
     * Deleting the activity must remove the instance together with its grades, extensions and
     * every calendar event (the shared one and the students' personal override events).
     */
    public function test_delete_instance_removes_all_data(): void {
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
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 42,
            'manualgrade' => 0,
        ]);
        $generator->create_override_entry([
            'externalassignment' => $instance->id,
            'userid' => $student->id,
            'duedate' => time() + WEEKSECS,
        ]);
        externalassignment_refresh_events(0, $instance->id);
        $this->assertEquals(2, $DB->count_records('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));

        $this->assertTrue(externalassignment_delete_instance($instance->id));

        $this->assertFalse($DB->record_exists('externalassignment', ['id' => $instance->id]));
        $this->assertFalse($DB->record_exists('externalassignment_grades', ['externalassignment' => $instance->id]));
        $this->assertFalse($DB->record_exists('externalassignment_overrides', ['externalassignment' => $instance->id]));
        $this->assertFalse($DB->record_exists('event', ['modulename' => 'externalassignment', 'instance' => $instance->id]));
    }

    /**
     * The course page shows the link to the external assignment and its dates.
     */
    public function test_cm_info_view_without_open_date(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() + DAYSECS;
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externallink' => 'https://www.example.com/a01',
            'duedate' => $duedate,
        ]);

        $content = get_fast_modinfo($course)->get_cm($instance->cmid)->content;

        $this->assertStringContainsString('href="https://www.example.com/a01"', $content);
        $this->assertStringContainsString(get_string('submissionsdue', 'externalassignment'), $content);
        $this->assertStringContainsString(userdate($duedate), $content);
        $this->assertStringNotContainsString(get_string('submissionsopen', 'externalassignment'), $content);
    }

    /**
     * Before submissions open the link is hidden (unless "always show link" is set) and the
     * opening date is announced.
     */
    public function test_cm_info_view_before_open_date(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $opendate = time() + DAYSECS;
        $hidden = $generator->create_instance([
            'course' => $course->id,
            'externallink' => 'https://www.example.com/hidden',
            'alwaysshowlink' => 0,
            'allowsubmissionsfromdate' => $opendate,
        ]);
        $shown = $generator->create_instance([
            'course' => $course->id,
            'externalname' => 'shown',
            'externallink' => 'https://www.example.com/shown',
            'alwaysshowlink' => 1,
            'allowsubmissionsfromdate' => $opendate,
        ]);

        $modinfo = get_fast_modinfo($course);
        $content = $modinfo->get_cm($hidden->cmid)->content;
        $this->assertStringNotContainsString('https://www.example.com/hidden', $content);
        $this->assertStringContainsString(get_string('submissionsopen', 'externalassignment'), $content);
        $this->assertStringContainsString(userdate($opendate), $content);

        $this->assertStringContainsString('href="https://www.example.com/shown"', $modinfo->get_cm($shown->cmid)->content);
    }

    /**
     * Once submissions have opened the link is shown together with the opening date.
     */
    public function test_cm_info_view_after_open_date(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'externallink' => 'https://www.example.com/a01',
            'alwaysshowlink' => 0,
            'allowsubmissionsfromdate' => time() - DAYSECS,
        ]);

        $content = get_fast_modinfo($course)->get_cm($instance->cmid)->content;

        $this->assertStringContainsString('href="https://www.example.com/a01"', $content);
        $this->assertStringContainsString(get_string('submissionsopened', 'externalassignment'), $content);
    }

    /**
     * The "Submissions" entry of the activity navigation is only added for users who may
     * review the grades.
     */
    public function test_extend_settings_navigation(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);

        $build = function () use ($cm, $course): \navigation_node {
            $page = new \moodle_page();
            $page->set_cm($cm, $course);
            $page->set_url('/mod/externalassignment/view.php', ['id' => $cm->id]);
            $node = \navigation_node::create('test');
            externalassignment_extend_settings_navigation(new \settings_navigation($page), $node);
            return $node;
        };

        $this->setUser($teacher);
        $node = $build()->get('mod_externalassignment_submissions');
        $this->assertNotFalse($node);
        $this->assertEquals('grading', $node->action->get_param('action'));
        $this->assertEquals($cm->id, $node->action->get_param('id'));

        $this->setUser($student);
        $this->assertFalse($build()->get('mod_externalassignment_submissions'));
    }

    /**
     * The "is due" event links to the activity on the dashboard's timeline.
     */
    public function test_calendar_event_callbacks(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $eventid = $DB->get_field('event', 'id', ['modulename' => 'externalassignment', 'instance' => $instance->id]);
        $event = \calendar_event::load($eventid);

        $this->assertTrue(mod_externalassignment_core_calendar_is_event_visible($event, $student->id));

        $action = mod_externalassignment_core_calendar_provide_event_action(
            $event,
            new \core_calendar\action_factory(),
            $student->id
        );
        $this->assertEquals('view', $action->get_name());
        $this->assertEquals($instance->cmid, $action->get_url()->get_param('id'));
        $this->assertTrue($action->is_actionable());
    }
}

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
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use restore_controller;
use restore_dbops;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

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
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(assign_control::class, 'update_calendar_event')]
final class lib_test extends \advanced_testcase {
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
}

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

use core\context\course;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class assign_control
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(assign_control::class, 'add_instance')]
#[CoversMethod(assign_control::class, 'update_instance')]
#[CoversMethod(assign_control::class, 'delete_instance')]
#[CoversMethod(assign_control::class, 'set_instance')]
#[CoversMethod(assign_control::class, 'get_instance')]
#[CoversMethod(assign_control::class, 'set_coursemoduleid')]
#[CoversMethod(assign_control::class, 'get_coursemoduleid')]
#[CoversMethod(assign_control::class, 'set_course')]
#[CoversMethod(assign_control::class, 'get_course')]
#[CoversMethod(assign_control::class, 'set_context')]
#[CoversMethod(assign_control::class, 'get_context')]
#[CoversMethod(assign_control::class, 'set_coursemodule')]
#[CoversMethod(assign_control::class, 'get_coursemodule')]
#[CoversMethod(assign_control::class, 'update_calendar_event')]
#[CoversMethod(assign_control::class, 'update_override_calendar_event')]
final class assign_control_test extends \advanced_testcase {
    /**
     * Test add_instance
     */
    public function test_add_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $this->assertNotEmpty($instance->id);
        $this->assertEquals($course->id, $instance->course);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $this->assertNotEmpty($record);
    }

    /**
     * Test update_instance
     */
    public function test_update_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        $assigncontrol = new assign_control($context, $cm);

        $formdata = new \stdClass();
        $formdata->instance = $instance->id;
        $formdata->course = $course->id;
        $formdata->coursemodule = $module->id;
        $formdata->name = 'Updated Assignment';
        $formdata->intro = 'Updated Description';
        $formdata->introformat = 1;
        $formdata->alwaysshowdescription = true;
        $formdata->externalname = 'Updated External Name';
        $formdata->externallink = 'http://updated.example.com';
        $formdata->alwaysshowlink = true;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;
        $formdata->externalgrademax = 100;
        $formdata->manualgrademax = 10;
        $formdata->passingpercentage = 60;
        $formdata->needspassinggrade = 1;

        $result = $assigncontrol->update_instance($formdata, $module->id);

        $this->assertTrue($result);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $this->assertEquals('Updated Assignment', $record->name);
    }

    /**
     * Test delete_instance
     */
    public function test_delete_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        $assigncontrol = new assign_control($context, $cm);
        $assigncontrol->delete_instance($instance->id);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $this->assertFalse($record);
    }

    /**
     * Test getters and setters
     */
    public function test_setters_getters(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        $assigncontrol = new assign_control($context, $cm);

        $instanceobj = new \stdClass();
        $instanceobj->id = 1;
        $instanceobj->name = 'Test';
        $assigncontrol->set_instance($instanceobj);
        $this->assertEquals(1, $assigncontrol->get_instance()->id);

        $assigncontrol->set_coursemoduleid(5);
        $this->assertEquals(5, $assigncontrol->get_coursemoduleid());

        $courseobj = new \stdClass();
        $courseobj->id = 10;
        $assigncontrol->set_course($courseobj);
        $this->assertEquals(10, $assigncontrol->get_course()->id);

        $this->assertInstanceOf(\core\context::class, $assigncontrol->get_context());
        $this->assertInstanceOf(\cm_info::class, $assigncontrol->get_coursemodule());
    }

    /**
     * Regression test for GitHub issue #35 ("Duplicate external name"): when a student has two
     * assignments with the same external name in the same course, saving an assignment with a
     * name that clashes with another one must not silently keep the duplicate - it should force
     * the external name to the "FIXME" placeholder so the clash is visible and the update_grade
     * webservice can no longer confuse the two assignments for the same student.
     */
    public function test_update_instance_renames_duplicate_external_name(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $generator->create_instance(['course' => $course->id, 'externalname' => 'shared-external-name']);
        $instance = $generator->create_instance(['course' => $course->id, 'externalname' => 'other-name']);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        $assigncontrol = new assign_control($context, $cm);
        $formdata = $this->build_formdata($course->id, 'Renamed Assignment', 'shared-external-name');
        $formdata->instance = $instance->id;
        $formdata->coursemodule = $module->id;

        $assigncontrol->update_instance($formdata, $module->id);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $this->assertNotEquals('shared-external-name', $record->externalname);
        $this->assertEquals('FIXME', $record->externalname);
    }

    /**
     * Regression test for GitHub issues #36 ("Losing the 'Needs passing grade'") and #12
     * ("Illegal completion conditions"): when the activity's completion tracking is set to
     * "Show activity as complete when conditions are met" (completion == COMPLETION_TRACKING_AUTOMATIC),
     * update_instance() must force needspassinggrade back to 1 even if the checkbox value was not
     * resubmitted with the form - otherwise a previously-completing student's requirement silently
     * disappears, and automatic completion can end up enabled with no completion rule selected at all.
     */
    public function test_update_instance_forces_needspassinggrade_for_automatic_completion(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'needspassinggrade' => 1]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        $assigncontrol = new assign_control($context, $cm);
        $formdata = $this->build_formdata($course->id, 'Updated Assignment', 'updated-external-name');
        $formdata->instance = $instance->id;
        $formdata->coursemodule = $module->id;
        // COMPLETION_TRACKING_AUTOMATIC: the teacher re-saved the settings form with automatic
        // completion selected, but the "needs passing grade" checkbox value was not part of the
        // submitted data (e.g. it was re-rendered unchecked by the browser).
        $formdata->completion = 2;
        unset($formdata->needspassinggrade);

        $assigncontrol->update_instance($formdata, $module->id);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $this->assertEquals(1, $record->needspassinggrade);
    }

    /**
     * Companion test to the one above, for *new* assignments: add_instance() should apply the
     * exact same "automatic completion implies needspassinggrade" guard as update_instance() does,
     * otherwise a brand-new assignment can be created with automatic completion enabled but no
     * completion rule selected at all - the same illegal state issue #12 describes.
     *
     * NOTE: as of this writing, assign_control::add_instance() does not contain the
     * "if ($formdata->completion == 2) { $formdata->needspassinggrade = 1; }" guard that
     * update_instance() has - only editing an existing assignment self-heals. If that is still
     * true, this test is expected to fail and highlights that issues #12/#36 are only half-fixed.
     */
    public function test_add_instance_forces_needspassinggrade_for_automatic_completion(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);

        $assigncontrol = new assign_control($context, $cm);
        $formdata = $this->build_formdata($course->id, 'New Automatic Assignment', 'new-automatic-name');
        // COMPLETION_TRACKING_AUTOMATIC selected at creation time, but the "needs passing grade"
        // checkbox was left unchecked.
        $formdata->completion = 2;
        $formdata->needspassinggrade = 0;

        $returnid = $assigncontrol->add_instance($formdata, $module->id);

        $record = $DB->get_record('externalassignment', ['id' => $returnid]);
        $this->assertEquals(
            1,
            $record->needspassinggrade,
            'A new assignment with automatic completion must not end up with no completion rule selected.'
        );
    }

    /**
     * Regression test for GitHub issue #37 ("Status: overdue despite overwrite"):
     * update_override_calendar_event() must create a userid-scoped "due" calendar event
     * (courseid=0, mirroring mod_assign's own per-user override events) using the *overridden*
     * due date, separate from the shared assignment-level event.
     */
    public function test_update_override_calendar_event_creates_personal_event(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() - DAYSECS]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $record = $DB->get_record('externalassignment', ['id' => $instance->id], '*', MUST_EXIST);
        $overrideduedate = time() + DAYSECS;

        assign_control::update_override_calendar_event($record, $module->id, $student->id, $overrideduedate);

        $event = $DB->get_record('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
            'userid' => $student->id,
        ], '*', MUST_EXIST);
        $this->assertEquals(0, $event->courseid);
        $this->assertEquals($overrideduedate, $event->timestart);
        $this->assertEquals($overrideduedate, $event->timesort);
    }

    /**
     * Companion test for GitHub issue #37: passing an empty/null due date (the override does not
     * extend the due date, e.g. only the cutoff date was changed) must not create an event, and
     * must remove one left over from a previous call.
     */
    public function test_update_override_calendar_event_deletes_when_no_duedate(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $record = $DB->get_record('externalassignment', ['id' => $instance->id], '*', MUST_EXIST);

        assign_control::update_override_calendar_event($record, $module->id, $student->id, time() + 2 * DAYSECS);
        $this->assertEquals(1, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'userid' => $student->id,
        ]));

        assign_control::update_override_calendar_event($record, $module->id, $student->id, null);
        $this->assertEquals(0, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'userid' => $student->id,
        ]));
    }

    /**
     * Regression test for GitHub issue #37: once a student has a personal override calendar
     * event, saving the assignment's own settings again (update_instance()) must still find and
     * update the single shared event rather than creating a duplicate - update_calendar_event()
     * can no longer rely on userid=0 to identify the shared event, because calendar_event
     * silently replaces an empty userid with the acting user's id on creation.
     */
    public function test_update_instance_does_not_duplicate_shared_event_when_override_exists(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $record = $DB->get_record('externalassignment', ['id' => $instance->id], '*', MUST_EXIST);
        assign_control::update_override_calendar_event($record, $module->id, $student->id, time() + 2 * DAYSECS);

        $context = \context_module::instance($module->id);
        $cm = get_fast_modinfo($course)->get_cm($module->id);
        $assigncontrol = new assign_control($context, $cm);
        $formdata = $this->build_formdata($course->id, 'Renamed Assignment', $instance->externalname);
        $formdata->instance = $instance->id;
        $formdata->coursemodule = $module->id;
        $formdata->duedate = time() + 3 * DAYSECS;

        $assigncontrol->update_instance($formdata, $module->id);
        $assigncontrol->update_instance($formdata, $module->id);

        $this->assertEquals(2, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'eventtype' => 'due',
        ]));
        $shared = $DB->get_record('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $this->assertEquals($formdata->duedate, $shared->timestart);
    }

    /**
     * Regression test for GitHub issue #37: deleting an assignment must clean up the shared
     * calendar event AND any per-student override events, not just the first one it finds.
     */
    public function test_delete_instance_removes_override_calendar_events(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $record = $DB->get_record('externalassignment', ['id' => $instance->id], '*', MUST_EXIST);
        assign_control::update_override_calendar_event($record, $module->id, $student->id, time() + 2 * DAYSECS);

        $this->assertEquals(2, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'eventtype' => 'due',
        ]));

        $context = \context_module::instance($module->id);
        $assigncontrol = new assign_control($context, null);
        $assigncontrol->delete_instance($instance->id);

        $this->assertEquals(0, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'eventtype' => 'due',
        ]));
    }

    /**
     * Builds a minimal, valid formdata object of the kind mod_form.php would submit.
     * @param int $courseid
     * @param string $name
     * @param string $externalname
     * @return \stdClass
     */
    private function build_formdata(int $courseid, string $name, string $externalname): \stdClass {
        $formdata = new \stdClass();
        $formdata->instance = 0;
        $formdata->course = $courseid;
        $formdata->coursemodule = 0;
        $formdata->name = $name;
        $formdata->intro = 'Description';
        $formdata->introformat = 1;
        $formdata->alwaysshowdescription = true;
        $formdata->externalname = $externalname;
        $formdata->externallink = 'http://example.com';
        $formdata->alwaysshowlink = true;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;
        $formdata->externalgrademax = 100;
        $formdata->manualgrademax = 10;
        $formdata->passingpercentage = 60;
        $formdata->needspassinggrade = 0;
        $formdata->completion = 0;
        return $formdata;
    }
}

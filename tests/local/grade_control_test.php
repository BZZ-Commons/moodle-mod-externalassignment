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

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class grade_control
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(grade_control::class, '__construct')]
#[CoversMethod(grade_control::class, 'set_coursemoduleid')]
#[CoversMethod(grade_control::class, 'get_coursemoduleid')]
#[CoversMethod(grade_control::class, 'set_courseid')]
#[CoversMethod(grade_control::class, 'get_courseid')]
#[CoversMethod(grade_control::class, 'set_context')]
#[CoversMethod(grade_control::class, 'get_context')]
#[CoversMethod(grade_control::class, 'set_assign')]
#[CoversMethod(grade_control::class, 'get_assign')]
#[CoversMethod(grade_control::class, 'set_userid')]
#[CoversMethod(grade_control::class, 'get_userid')]
#[CoversMethod(grade_control::class, 'override_update')]
final class grade_control_test extends \advanced_testcase {
    /**
     * Test constructor
     */
    public function test_constructor(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        $gradecontrol = new grade_control($module->id, $context, 0);

        $this->assertInstanceOf(grade_control::class, $gradecontrol);
        $this->assertEquals($module->id, $gradecontrol->get_coursemoduleid());
        $this->assertEquals($course->id, $gradecontrol->get_courseid());
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

        $gradecontrol = new grade_control($module->id, $context, 0);

        $gradecontrol->set_coursemoduleid(10);
        $this->assertEquals(10, $gradecontrol->get_coursemoduleid());

        $gradecontrol->set_courseid(20);
        $this->assertEquals(20, $gradecontrol->get_courseid());

        $newcontext = \context_course::instance($course->id);
        $gradecontrol->set_context($newcontext);
        $this->assertInstanceOf(\core\context::class, $gradecontrol->get_context());

        $assign = new assign(null);
        $gradecontrol->set_assign($assign);
        $this->assertInstanceOf(assign::class, $gradecontrol->get_assign());

        $gradecontrol->set_userid(5);
        $this->assertEquals(5, $gradecontrol->get_userid());

        $gradecontrol->set_userlist(['Bart', 'Lisa']);
        $this->assertEquals(['Bart', 'Lisa'], $gradecontrol->get_userlist());
    }

    /**
     * Regression test for GitHub issue #25 ("Error with sort_students() on grader page").
     * The grader page instantiates grade_control(), whose constructor loads the assignment via
     * assign::load_db(). assign::sort_students() declares non-nullable string $sort/$tdir
     * parameters, so a caller that ever passes null for those again (as grade_control's
     * constructor briefly did) triggers a fatal TypeError as soon as a real student is enrolled.
     * This test exercises that exact path with enrolled students to guard against a regression.
     */
    public function test_constructor_loads_and_sorts_students_without_error(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $userb = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Brown']);
        $this->getDataGenerator()->enrol_user($userb->id, $course->id, 'student');
        $usera = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Anderson']);
        $this->getDataGenerator()->enrol_user($usera->id, $course->id, 'student');

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        // This must not throw, and must sort by lastname ascending (the constructor's default).
        $gradecontrol = new grade_control($module->id, $context, 0);

        $students = $gradecontrol->get_assign()->get_students();
        $this->assertCount(2, $students);
        $this->assertEquals('Anderson', reset($students)->get_lastname());
    }

    /**
     * Regression test for GitHub issue #37 ("Status: overdue despite overwrite"): granting a
     * student an extension only wrote the override to externalassignment_overrides, it never
     * touched the calendar. Moodle's Dashboard "Timeline" block (and the calendar in general)
     * computes its own "Overdue" flag purely from the calendar event's timesort - see
     * calendar/classes/external/event_exporter_base.php - so a student with an extension kept
     * being shown as overdue once the *original* due date passed, even though the teacher's
     * grading table (which uses student::get_status()) correctly showed the extension.
     *
     * override_update() must now also create a personal "due" calendar event (userid-scoped,
     * courseid=0, mirroring mod_assign's own override events) with the *overridden* due date.
     */
    public function test_override_update_creates_personal_calendar_event(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $duedate = time() - DAYSECS; // The assignment's own due date has already passed.
        $instance = $generator->create_instance([
            'course' => $course->id,
            'duedate' => $duedate,
            'cutoffdate' => $duedate,
        ]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $gradecontrol = new grade_control($module->id, $context, $student->id);

        $overrideduedate = time() + DAYSECS; // The student was granted an extension into the future.
        $override = new override();
        $override->set_externalassignment($instance->id);
        $override->set_userid($student->id);
        $override->set_allowsubmissionsfromdate(0);
        $override->set_duedate($overrideduedate);
        $override->set_cutoffdate($overrideduedate);

        $gradecontrol->override_update($override);

        $personalevent = $DB->get_record('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
            'userid' => $student->id,
        ], '*', MUST_EXIST);
        $this->assertEquals(0, $personalevent->courseid, 'A personal override event must have courseid=0.');
        $this->assertEquals($overrideduedate, $personalevent->timestart);
        $this->assertEquals($overrideduedate, $personalevent->timesort);
        $this->assertFalse(
            $personalevent->timesort < time(),
            'The student\'s personal event must reflect the extended due date, not the original overdue one.'
        );

        // The shared assignment-level event must be untouched (still the original, past due date)
        // so that students WITHOUT an override are still correctly shown as overdue.
        $sharedevent = $DB->get_record('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
            'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $this->assertEquals($duedate, $sharedevent->timestart);
    }

    /**
     * Companion test for GitHub issue #37: re-saving the same override (e.g. the teacher reopens
     * the override form and saves again without changes) must update the existing personal event
     * in place, not create a duplicate calendar entry.
     */
    public function test_override_update_does_not_duplicate_personal_calendar_event(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $gradecontrol = new grade_control($module->id, $context, $student->id);

        $override = new override();
        $override->set_externalassignment($instance->id);
        $override->set_userid($student->id);
        $override->set_allowsubmissionsfromdate(0);
        $override->set_duedate(time() + 2 * DAYSECS);
        $override->set_cutoffdate(time() + 2 * DAYSECS);

        $gradecontrol->override_update($override);
        $gradecontrol->override_update($override);

        $count = $DB->count_records('event', [
            'modulename' => 'externalassignment',
            'instance' => $instance->id,
            'eventtype' => 'due',
            'userid' => $student->id,
        ]);
        $this->assertEquals(1, $count);
    }

    /**
     * Companion test for GitHub issue #37: if a previously-granted due-date extension is removed
     * again (the override is re-saved with no due date, e.g. only the cutoff date stays extended),
     * the now-stale personal calendar event must be removed so the student falls back to the
     * shared assignment event again.
     */
    public function test_override_update_removes_personal_calendar_event_when_duedate_cleared(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'duedate' => time() + DAYSECS]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);
        $gradecontrol = new grade_control($module->id, $context, $student->id);

        $override = new override();
        $override->set_externalassignment($instance->id);
        $override->set_userid($student->id);
        $override->set_allowsubmissionsfromdate(0);
        $override->set_duedate(time() + 2 * DAYSECS);
        $override->set_cutoffdate(time() + 2 * DAYSECS);
        $gradecontrol->override_update($override);

        $this->assertEquals(1, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'userid' => $student->id,
        ]));

        $override->set_duedate(0);
        $gradecontrol->override_update($override);

        $this->assertEquals(0, $DB->count_records('event', [
            'modulename' => 'externalassignment', 'instance' => $instance->id, 'userid' => $student->id,
        ]));
    }
}

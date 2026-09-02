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
}

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

/**
 * Unit tests for class grade_control
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
final class grade_control_test extends \advanced_testcase {

    /**
     * Test constructor
     * @covers \grade_control::__construct
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
     * @covers \grade_control::set_coursemoduleid
     * @covers \grade_control::get_coursemoduleid
     * @covers \grade_control::set_courseid
     * @covers \grade_control::get_courseid
     * @covers \grade_control::set_context
     * @covers \grade_control::get_context
     * @covers \grade_control::set_assign
     * @covers \grade_control::get_assign
     * @covers \grade_control::set_userid
     * @covers \grade_control::get_userid
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
    }
}

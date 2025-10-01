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

/**
 * Unit tests for class assign_control
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
final class assign_control_test extends \advanced_testcase {

    /**
     * Test add_instance
     * @covers \assign_control::add_instance
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
     * @covers \assign_control::update_instance
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

        $assigncontrol = new assign_control($context, $module);

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
     * @covers \assign_control::delete_instance
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

        $assigncontrol = new assign_control($context, $module);
        $assigncontrol->delete_instance($instance->id);

        $record = $DB->get_record('externalassignment', ['id' => $instance->id]);
        $this->assertFalse($record);
    }

    /**
     * Test getters and setters
     * @covers \assign_control::set_instance
     * @covers \assign_control::get_instance
     * @covers \assign_control::set_coursemoduleid
     * @covers \assign_control::get_coursemoduleid
     * @covers \assign_control::set_course
     * @covers \assign_control::get_course
     * @covers \assign_control::set_context
     * @covers \assign_control::get_context
     * @covers \assign_control::set_coursemodule
     * @covers \assign_control::get_coursemodule
     */
    public function test_setters_getters(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $module = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($module->id);

        $assigncontrol = new assign_control($context, $module);

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
}

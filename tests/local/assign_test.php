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
 * Unit tests for class assign
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
class assign_test extends \advanced_testcase {
    public function testConstructorWithFormData(): void {
        $formdata = new \stdClass();
        $formdata->instance = 5;
        $formdata->course = 4;
        $formdata->coursemodule = 3;
        $formdata->name = 'Test Assignment';
        $formdata->intro = 'Test Assignment Description';
        $formdata->introformat = 1;
        $formdata->alwaysshowdescription = true;
        $formdata->externalname = 'Test Assignment';
        $formdata->externallink = 'http://example.com';
        $formdata->alwaysshowlink = true;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;
        $formdata->externalgrademax = 100;
        $formdata->manualgrademax = 10;
        $formdata->passingpercentage = 60;
        $formdata->needspassingrade = 1;

        $assign = new assign($formdata);

        $this->assertEquals(5, $assign->get_id());
        $this->assertEquals(4, $assign->get_course());
        $this->assertEquals(0, $assign->get_coursemodule());
        $this->assertEquals('Test Assignment', $assign->get_name());
        $this->assertEquals('Test Assignment Description', $assign->get_intro());
        $this->assertEquals(1, $assign->get_introformat());
        $this->assertTrue($assign->is_alwaysshowdescription());
        $this->assertEquals('Test Assignment', $assign->get_externalname());
        $this->assertEquals('http://example.com', $assign->get_externallink());
        $this->assertTrue($assign->is_alwaysshowlink());
        $this->assertEquals(0, $assign->get_allowsubmissionsfromdate());
        $this->assertEquals(0, $assign->get_duedate());
        $this->assertEquals(0, $assign->get_cutoffdate());
        $this->assertEquals(100, $assign->get_externalgrademax());
        $this->assertEquals(10, $assign->get_manualgrademax());
        $this->assertEquals(60, $assign->get_passingpercentage());
        $this->assertEquals(1, $assign->get_needspassinggrade());

    }

    public function testConstructorWithoutFormData(): void {
        $assign = new assign(null);
        $this->assertNull($assign->get_id());
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function testLoadData(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(array('course' => $course->id));

        $assign = new assign(null);
        $assign->load_db($instance->coursemodule);
        $this->assertEquals($instance->id, $assign->get_id());
        $this->assertEquals($instance->course, $assign->get_course());
        $this->assertEquals($instance->coursemodule, $assign->get_coursemodule());
        $this->assertEquals($instance->name, $assign->get_name());
        $this->assertEquals($instance->intro, $assign->get_intro());
        $this->assertEquals($instance->introformat, $assign->get_introformat());
        $this->assertEquals($instance->alwaysshowdescription, $assign->is_alwaysshowdescription());
        $this->assertEquals($instance->externalname, $assign->get_externalname());
        $this->assertEquals($instance->externallink, $assign->get_externallink());
        $this->assertEquals($instance->alwaysshowlink, $assign->is_alwaysshowlink());
        $this->assertEquals($instance->allowsubmissionsfromdate, $assign->get_allowsubmissionsfromdate());
        $this->assertEquals($instance->duedate, $assign->get_duedate());
        $this->assertEquals($instance->cutoffdate, $assign->get_cutoffdate());
        $this->assertEquals($instance->externalgrademax, $assign->get_externalgrademax());
        $this->assertEquals($instance->manualgrademax, $assign->get_manualgrademax());
        $this->assertEquals($instance->passingpercentage, $assign->get_passingpercentage());
        $this->assertEquals($instance->needspassinggrade, $assign->get_needspassinggrade());
    }

    public function testSettersGetters(): void {
        $assign = new assign(null);
        $assign->set_id(5);
        $assign->set_course(4);
        $assign->set_coursemodule(3);
        $assign->set_name('Test Assignment');
        $assign->set_intro('Test Assignment Description');
        $assign->set_introformat(1);
        $assign->set_alwaysshowdescription(true);
        $assign->set_externalname('Test Assignment');
        $assign->set_externallink('http://example.com');
        $assign->set_alwaysshowlink(true);
        $assign->set_allowsubmissionsfromdate(0);
        $assign->set_duedate(0);
        $assign->set_cutoffdate(0);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_passingpercentage(60);
        $assign->set_needspassinggrade(1);


        $this->assertEquals(5, $assign->get_id());
        $this->assertEquals(4, $assign->get_course());
        $this->assertEquals(3, $assign->get_coursemodule());
        $this->assertEquals('Test Assignment', $assign->get_name());
        $this->assertEquals('Test Assignment Description', $assign->get_intro());
        $this->assertEquals(1, $assign->get_introformat());
        $this->assertTrue($assign->is_alwaysshowdescription());
        $this->assertEquals('Test Assignment', $assign->get_externalname());
        $this->assertEquals('http://example.com', $assign->get_externallink());
        $this->assertTrue($assign->is_alwaysshowlink());
        $this->assertEquals(0, $assign->get_allowsubmissionsfromdate());
        $this->assertEquals(0, $assign->get_duedate());
        $this->assertEquals(0, $assign->get_cutoffdate());
        $this->assertEquals(100, $assign->get_externalgrademax());
        $this->assertEquals(10, $assign->get_manualgrademax());
        $this->assertEquals(60, $assign->get_passingpercentage());
        $this->assertEquals(1, $assign->get_needspassinggrade());

    }


    public function testToStdClass(): void {
        $assign = new assign(null);
        $assign->set_id(1);
        $assign->set_name('Test Assignment');

        $stdclass = $assign->to_stdclass();

        $this->assertEquals(1, $stdclass->id);
        $this->assertEquals('Test Assignment', $stdclass->name);
    }
}

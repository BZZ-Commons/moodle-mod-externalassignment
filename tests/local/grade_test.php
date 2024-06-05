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
class grade_test extends \advanced_testcase {
    /**
     * Test constructor with formdata simulation the add/edit form
     * @covers \grade::__construct
     * @covers \grade::load_data
     */
    public function test_constructor_with_formdata(): void {
        $formdata = new \stdClass();
        $formdata->gradeid = 1;
        $formdata->externalassignmentid = 1;
        $formdata->userid = 1;
        $formdata->externallink = 'http://example.com';
        $formdata->externalgrade = 85.0;
        $formdata->externalfeedback = 'Good job!';
        $formdata->manualgrade = 90.0;
        $formdata->manualfeedback = 'Excellent work!';

        $grade = new grade($formdata);

        $this->assertEquals(1, $grade->get_id());
        $this->assertEquals(1, $grade->get_externalassignment());
        $this->assertEquals(1, $grade->get_userid());
        $this->assertEquals('http://example.com', $grade->get_externallink());
        $this->assertEquals(85.0, $grade->get_externalgrade());
        $this->assertEquals('Good job!', $grade->get_externalfeedback());
        $this->assertEquals(90.0, $grade->get_manualgrade());
        $this->assertEquals('Excellent work!', $grade->get_manualfeedback());
    }

    /**
     * Test constructor without formdata
     * @covers \grade::__construct
     */
    public function test_constructor_without_formdata(): void {
        $grade = new grade(null);

        $this->assertNull($grade->get_id());
        $this->assertNull($grade->get_externalassignment());
        $this->assertNull($grade->get_userid());
        $this->assertEquals('', $grade->get_externallink());
        $this->assertEquals(0.0, $grade->get_externalgrade());
        $this->assertEquals('', $grade->get_externalfeedback());
        $this->assertEquals(0.0, $grade->get_manualgrade());
        $this->assertEquals('', $grade->get_manualfeedback());
    }

    /**
     * Test setters and getters
     * @covers \grade::set_id
     * @covers \grade::get_id
     * @covers \grade::set_externalassignment
     * @covers \grade::get_externalassignment
     * @covers \grade::set_userid
     * @covers \grade::get_userid
     * @covers \grade::set_externallink
     * @covers \grade::get_externallink
     * @covers \grade::set_externalgrade
     * @covers \grade::get_externalgrade
     * @covers \grade::set_externalfeedback
     * @covers \grade::get_externalfeedback
     * @covers \grade::set_manualgrade
     * @covers \grade::get_manualgrade
     * @covers \grade::set_manualfeedback
     * @covers \grade::get_manualfeedback
     *
     */
    public function test_setters_getters() {
        $grade = new grade(null);
        $grade->set_id(7);
        $grade->set_externalassignment(6);
        $grade->set_userid(5);
        $grade->set_grader(4);
        $grade->set_externallink('http://example.com');
        $grade->set_externalgrade(85.0);
        $grade->set_externalfeedback('Good job!');
        $grade->set_manualgrade(9.8);
        $grade->set_manualfeedback('Excellent work!');

        $this->assertEquals(7, $grade->get_id());
        $this->assertEquals(6, $grade->get_externalassignment());
        $this->assertEquals(5, $grade->get_userid());
        $this->assertEquals(4, $grade->get_grader());
        $this->assertEquals('http://example.com', $grade->get_externallink());
        $this->assertEquals(85.0, $grade->get_externalgrade());
        $this->assertEquals('Good job!', $grade->get_externalfeedback());
        $this->assertEquals(9.8, $grade->get_manualgrade());
        $this->assertEquals('Excellent work!', $grade->get_manualfeedback());
    }
    /*  TODO
    public function testLoadDb() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(array('course' => $course->id));


    }
    */

    /**
     * Test casting to stdclass
     * @covers \grade::to_stdclass
     */
    public function test_to_stdclass(): void {
        $grade = new grade(null);
        $grade->set_id(1);
        $grade->set_externalassignment(1);
        $grade->set_userid(1);
        $grade->set_externallink('http://example.com');
        $grade->set_externalgrade(85.0);
        $grade->set_externalfeedback('Good job!');
        $grade->set_manualgrade(90.0);
        $grade->set_manualfeedback('Excellent work!');

        $stdclass = $grade->to_stdclass();

        $this->assertEquals(1, $stdclass->id);
        $this->assertEquals(1, $stdclass->externalassignment);
        $this->assertEquals(1, $stdclass->userid);
        $this->assertEquals('http://example.com', $stdclass->externallink);
        $this->assertEquals(85.0, $stdclass->externalgrade);
        $this->assertEquals('Good job!', $stdclass->externalfeedback);
        $this->assertEquals(90.0, $stdclass->manualgrade);
        $this->assertEquals('Excellent work!', $stdclass->manualfeedback);
    }
}

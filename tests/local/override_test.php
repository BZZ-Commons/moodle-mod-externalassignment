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
 * Unit tests for class override
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
final class override_test extends \advanced_testcase {
    /**
     * Test constructor with data
     * @covers \override::__construct
     * @covers \override::load_data
     */
    public function test_constructor_with_data(): void {
        $data = new \stdClass();
        $data->id = 1;
        $data->externalassignment = 2;
        $data->userid = 3;
        $data->allowsubmissionsfromdate = 100000;
        $data->duedate = 200000;
        $data->cutoffdate = 300000;

        $override = new override($data);

        $this->assertEquals(1, $override->get_id());
        $this->assertEquals(2, $override->get_externalassignment());
        $this->assertEquals(3, $override->get_userid());
        $this->assertEquals(100000, $override->get_allowsubmissionsfromdate());
        $this->assertEquals(200000, $override->get_duedate());
        $this->assertEquals(300000, $override->get_cutoffdate());
    }

    /**
     * Test constructor without data
     * @covers \override::__construct
     */
    public function test_constructor_without_data(): void {
        $override = new override(null);

        $this->assertNull($override->get_id());
        $this->assertNull($override->get_externalassignment());
        $this->assertNull($override->get_userid());
        $this->assertNull($override->get_allowsubmissionsfromdate());
        $this->assertNull($override->get_duedate());
        $this->assertNull($override->get_cutoffdate());
    }

    /**
     * Test setters and getters
     * @covers \override::set_id
     * @covers \override::get_id
     * @covers \override::set_externalassignment
     * @covers \override::get_externalassignment
     * @covers \override::set_userid
     * @covers \override::get_userid
     * @covers \override::set_allowsubmissionsfromdate
     * @covers \override::get_allowsubmissionsfromdate
     * @covers \override::set_duedate
     * @covers \override::get_duedate
     * @covers \override::set_cutoffdate
     * @covers \override::get_cutoffdate
     */
    public function test_setters_getters(): void {
        $override = new override();

        $override->set_id(10);
        $override->set_externalassignment(20);
        $override->set_userid(30);
        $override->set_allowsubmissionsfromdate(150000);
        $override->set_duedate(250000);
        $override->set_cutoffdate(350000);

        $this->assertEquals(10, $override->get_id());
        $this->assertEquals(20, $override->get_externalassignment());
        $this->assertEquals(30, $override->get_userid());
        $this->assertEquals(150000, $override->get_allowsubmissionsfromdate());
        $this->assertEquals(250000, $override->get_duedate());
        $this->assertEquals(350000, $override->get_cutoffdate());
    }

    /**
     * Test casting to stdclass
     * @covers \override::to_stdclass
     */
    public function test_to_stdclass(): void {
        $override = new override();
        $override->set_id(1);
        $override->set_externalassignment(2);
        $override->set_userid(3);
        $override->set_allowsubmissionsfromdate(100000);
        $override->set_duedate(200000);
        $override->set_cutoffdate(300000);

        $stdclass = $override->to_stdclass();

        $this->assertEquals(1, $stdclass->id);
        $this->assertEquals(2, $stdclass->externalassignment);
        $this->assertEquals(3, $stdclass->userid);
        $this->assertEquals(100000, $stdclass->allowsubmissionsfromdate);
        $this->assertEquals(200000, $stdclass->duedate);
        $this->assertEquals(300000, $stdclass->cutoffdate);
    }

    /**
     * Test to_stdclass with null values
     * @covers \override::to_stdclass
     */
    public function test_to_stdclass_with_null_values(): void {
        $override = new override();
        $override->set_id(1);
        $override->set_externalassignment(2);
        $override->set_userid(3);

        $stdclass = $override->to_stdclass();

        $this->assertEquals(1, $stdclass->id);
        $this->assertEquals(2, $stdclass->externalassignment);
        $this->assertEquals(3, $stdclass->userid);
        $this->assertObjectNotHasProperty('allowsubmissionsfromdate', $stdclass);
        $this->assertObjectNotHasProperty('duedate', $stdclass);
        $this->assertObjectNotHasProperty('cutoffdate', $stdclass);
    }

    /**
     * Test load_formdata
     * @covers \override::load_formdata
     */
    public function test_load_formdata(): void {
        $formdata = new \stdClass();
        $formdata->id = 5;
        $formdata->externalassignment = 10;
        $formdata->userid = 15;
        $formdata->allowsubmissionsfromdate = 100000;
        $formdata->duedate = 200000;
        $formdata->cutoffdate = 300000;

        $override = new override();
        $override->load_formdata($formdata);

        $this->assertEquals(5, $override->get_externalassignment());
        $this->assertEquals(15, $override->get_userid());
        $this->assertEquals(100000, $override->get_allowsubmissionsfromdate());
        $this->assertEquals(200000, $override->get_duedate());
        $this->assertEquals(300000, $override->get_cutoffdate());
    }
}

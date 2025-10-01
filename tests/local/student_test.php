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
 * Unit tests for class student
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
final class student_test extends \advanced_testcase {
    /**
     * Test constructor with student data
     * @covers \student::__construct
     */
    public function test_constructor_with_student_data(): void {
        $assign = new assign(null);
        $studentdata = new \stdClass();
        $studentdata->id = 1;
        $studentdata->username = 'testuser';
        $studentdata->firstname = 'Test';
        $studentdata->lastname = 'User';
        $studentdata->email = 'test@example.com';

        $student = new student($assign, $studentdata);

        $this->assertEquals(1, $student->get_userid());
        $this->assertEquals('testuser', $student->get_username());
        $this->assertEquals('Test', $student->get_firstname());
        $this->assertEquals('User', $student->get_lastname());
        $this->assertEquals('test@example.com', $student->get_email());
        $this->assertNull($student->get_grade());
        $this->assertNull($student->get_override());
    }

    /**
     * Test constructor without student data
     * @covers \student::__construct
     */
    public function test_constructor_without_student_data(): void {
        $assign = new assign(null);
        $student = new student($assign, null);

        $this->assertNull($student->get_grade());
        $this->assertNull($student->get_override());
        $this->assertInstanceOf(assign::class, $student->get_assign());
    }

    /**
     * Test setters and getters
     * @covers \student::set_userid
     * @covers \student::get_userid
     * @covers \student::set_username
     * @covers \student::get_username
     * @covers \student::set_firstname
     * @covers \student::get_firstname
     * @covers \student::set_lastname
     * @covers \student::get_lastname
     * @covers \student::set_email
     * @covers \student::get_email
     * @covers \student::set_grade
     * @covers \student::get_grade
     * @covers \student::set_override
     * @covers \student::get_override
     * @covers \student::set_assign
     * @covers \student::get_assign
     */
    public function test_setters_getters(): void {
        $assign = new assign(null);
        $student = new student($assign, null);

        $student->set_userid(5);
        $student->set_username('johndoe');
        $student->set_firstname('John');
        $student->set_lastname('Doe');
        $student->set_email('john.doe@example.com');

        $grade = new grade(null);
        $student->set_grade($grade);

        $override = new override();
        $student->set_override($override);

        $this->assertEquals(5, $student->get_userid());
        $this->assertEquals('johndoe', $student->get_username());
        $this->assertEquals('John', $student->get_firstname());
        $this->assertEquals('Doe', $student->get_lastname());
        $this->assertEquals('john.doe@example.com', $student->get_email());
        $this->assertInstanceOf(grade::class, $student->get_grade());
        $this->assertInstanceOf(override::class, $student->get_override());
        $this->assertInstanceOf(assign::class, $student->get_assign());
    }

    /**
     * Test casting to stdclass
     * @covers \student::to_stdclass
     */
    public function test_to_stdclass(): void {
        $assign = new assign(null);
        $assign->set_duedate(0);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_needspassinggrade(0);
        $assign->set_passingpercentage(60);

        $studentdata = new \stdClass();
        $studentdata->id = 1;
        $studentdata->username = 'testuser';
        $studentdata->firstname = 'Test';
        $studentdata->lastname = 'User';
        $studentdata->email = 'test@example.com';

        $student = new student($assign, $studentdata);

        $grade = new grade(null);
        $grade->set_id(1);
        $grade->set_externalgrade(85.0);
        $student->set_grade($grade);

        $stdclass = $student->to_stdclass();

        $this->assertEquals(1, $stdclass->userid);
        $this->assertEquals('testuser', $stdclass->username);
        $this->assertEquals('Test', $stdclass->firstname);
        $this->assertEquals('User', $stdclass->lastname);
        $this->assertEquals('test@example.com', $stdclass->email);
        $this->assertIsString($stdclass->status);
        $this->assertIsObject($stdclass->grade);
    }

    /**
     * Test to_stdclass with override
     * @covers \student::to_stdclass
     */
    public function test_to_stdclass_with_override(): void {
        $assign = new assign(null);
        $assign->set_duedate(0);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_needspassinggrade(0);
        $assign->set_passingpercentage(60);

        $studentdata = new \stdClass();
        $studentdata->id = 1;
        $studentdata->username = 'testuser';
        $studentdata->firstname = 'Test';
        $studentdata->lastname = 'User';
        $studentdata->email = 'test@example.com';

        $student = new student($assign, $studentdata);

        $override = new override();
        $override->set_id(1);
        $override->set_duedate(time() + 86400);
        $student->set_override($override);

        $stdclass = $student->to_stdclass();

        $this->assertEquals(1, $stdclass->userid);
        $this->assertIsObject($stdclass->override);
    }

    /**
     * Test get_status with no grade
     * @covers \student::get_status
     */
    public function test_get_status_no_grade(): void {
        $assign = new assign(null);
        $assign->set_duedate(0);

        $student = new student($assign, null);

        $status = $student->get_status();
        $this->assertIsString($status);
    }

    /**
     * Test get_status with passing grade
     * @covers \student::get_status
     */
    public function test_get_status_with_passing_grade(): void {
        $assign = new assign(null);
        $assign->set_duedate(time() + 86400);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_needspassinggrade(1);
        $assign->set_passingpercentage(60);

        $student = new student($assign, null);

        $grade = new grade(null);
        $grade->set_externalgrade(70.0);
        $grade->set_manualgrade(0.0);
        $student->set_grade($grade);

        $status = $student->get_status();
        $this->assertIsString($status);
    }

    /**
     * Test get_status with overdue assignment
     * @covers \student::get_status
     */
    public function test_get_status_overdue(): void {
        $assign = new assign(null);
        $assign->set_duedate(time() - 86400);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_needspassinggrade(1);
        $assign->set_passingpercentage(60);

        $student = new student($assign, null);

        $grade = new grade(null);
        $grade->set_externalgrade(30.0);
        $grade->set_manualgrade(0.0);
        $student->set_grade($grade);

        $status = $student->get_status();
        $this->assertIsString($status);
    }
}

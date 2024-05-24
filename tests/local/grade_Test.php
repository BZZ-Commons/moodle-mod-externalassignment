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

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class assign
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
class GradeTest extends TestCase
{
    public function constructorWithFormDataCreatesGrade(): void
    {
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

    public function constructorWithoutFormDataCreatesEmptyGrade(): void
    {
        $grade = new grade(null);

        $this->assertNull($grade->get_id());
        $this->assertNull($grade->get_externalassignment());
        $this->assertNull($grade->get_userid());
        $this->assertEquals('', $grade->get_externallink());
        $this->assertEquals(0.0, $grade->get_externalgrade());
        $this->assertNull($grade->get_externalfeedback());
        $this->assertEquals(0.0, $grade->get_manualgrade());
        $this->assertNull($grade->get_manualfeedback());
    }

    public function toStdClassReturnsGradeAsStdClass(): void
    {
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
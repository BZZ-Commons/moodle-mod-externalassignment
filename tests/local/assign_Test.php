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
class AssignTest extends TestCase
{
    public function testConstructorWithFormData(): void
    {
        $formdata = new \stdClass();
        $formdata->instance = 1;
        $assign = new assign($formdata);

        $this->assertEquals(1, $assign->get_id());
    }

    public function testConstructorWithoutFormData(): void
    {
        $assign = new assign(null);

        $this->assertNull($assign->get_id());
    }

    public function testSetAndGetId(): void
    {
        $assign = new assign(null);
        $assign->set_id(1);

        $this->assertEquals(1, $assign->get_id());
    }

    public function testSetAndGetCourse(): void
    {
        $assign = new assign(null);
        $assign->set_course(1);

        $this->assertEquals(1, $assign->get_course());
    }

    public function testSetAndGetName(): void
    {
        $assign = new assign(null);
        $assign->set_name('Test Assignment');

        $this->assertEquals('Test Assignment', $assign->get_name());
    }

    // ... continue with other getter and setter methods ...

    public function testToStdClass(): void
    {
        $assign = new assign(null);
        $assign->set_id(1);
        $assign->set_name('Test Assignment');

        $stdclass = $assign->to_stdclass();

        $this->assertEquals(1, $stdclass->id);
        $this->assertEquals('Test Assignment', $stdclass->name);
    }
}
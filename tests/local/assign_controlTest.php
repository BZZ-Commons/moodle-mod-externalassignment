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
 * Unit tests for class assign_control
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
class assign_controlTest extends TestCase {
    /** @var \context_module context */
    private $context;
    /** @var $coursemodule */
    private $coursemodule;
    /** @var $course */
    private $course;

    /**
     * Set up the test
     * @return void
     * @throws \coding_exception
     */
    public function test__construct() {
        $assigncontrol = new assign_control($this->context, $this->coursemodule);
        $this->assertNotNull($assigncontrol->get_coursemodule());
    }
}

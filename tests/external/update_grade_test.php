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
final class update_grade_test extends \advanced_testcase {
    public function test_execute_not_cutoff(): void {

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(
            [
                'course' => $course->id,
                'externalname' => 'externalname',
                'cutoffdate' => time() + 3600
            ]
        );
        $user = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($cm->id);
        $assign = new assign(null, $context);
        $assign->load_db_external('externalname', $user->id);

        $stub = $this->getMockBuilder(assign::class)
            ->onlyMethods(['update_grade'])
            ->getMock();
        $stub->expects($this->once())
            ->method('update_grade')
            ->with($this->equalTo($user->id), $this->equalTo(50.0));
    }
}
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

namespace mod_externalassignment\output;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class view_grading, the grading overview table
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversClass(view_grading::class)]
final class view_grading_test extends \advanced_testcase {
    /**
     * Every student gets a row in the requested order; ungraded students show 0.00.
     */
    public function test_export_for_template(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $graded = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Zeller']);
        $this->getDataGenerator()->enrol_user($graded->id, $course->id, 'student');
        $ungraded = $this->getDataGenerator()->create_user(['firstname' => 'Bert', 'lastname' => 'Adler']);
        $this->getDataGenerator()->enrol_user($ungraded->id, $course->id, 'student');
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $instance->id,
            'userid' => $graded->id,
            'grader' => 2,
            'externallink' => '',
            'externalgrade' => 42.5,
            'manualgrade' => 3,
        ]);
        $PAGE->set_url('/mod/externalassignment/view.php', ['id' => $instance->cmid, 'action' => 'grading']);
        $context = \context_module::instance($instance->cmid);

        $view = new view_grading($instance->cmid, $context, 'firstname', 'desc');
        $data = $view->export_for_template($PAGE->get_renderer('core'));

        $this->assertEquals('firstname', $data->sort);
        $this->assertEquals('desc', $data->tdir);
        $this->assertCount(2, $data->students);
        [$first, $second] = $data->students;

        $this->assertEquals($ungraded->id, $first->userid);
        $this->assertEquals('0.00', $first->externalgrade);
        $this->assertEquals('0.00', $first->manualgrade);
        $this->assertEquals('0.00', $first->gradefinal);
        $this->assertEquals($instance->cmid, $first->coursemoduleid);
        $this->assertEquals($course->id, $first->courseid);

        $this->assertEquals($graded->id, $second->userid);
        $this->assertEquals('42.50', $second->externalgrade);
        $this->assertEquals('3.00', $second->manualgrade);
        $this->assertEquals('45.50', $second->gradefinal);
    }

    /**
     * An assignment without students must render an empty table instead of failing.
     */
    public function test_export_for_template_without_students(): void {
        global $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $PAGE->set_url('/mod/externalassignment/view.php', ['id' => $instance->cmid, 'action' => 'grading']);
        $context = \context_module::instance($instance->cmid);

        $data = (new view_grading($instance->cmid, $context, 'lastname', 'asc'))->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame([], $data->students);
    }
}

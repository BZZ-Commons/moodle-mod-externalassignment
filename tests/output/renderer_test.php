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

use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Renders every page of the activity through the plugin renderer and its templates
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversClass(renderer::class)]
#[CoversClass(view_link::class)]
final class renderer_test extends \advanced_testcase {
    /** @var \stdClass the externalassignment instance */
    private \stdClass $instance;

    /** @var \context_module the context of the course module */
    private \context_module $context;

    /** @var \stdClass a graded student */
    private \stdClass $student;

    /** @var renderer the plugin renderer */
    private renderer $renderer;

    /**
     * Creates an assignment with one graded student
     */
    protected function setUp(): void {
        global $DB, $PAGE;
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $this->instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Lists',
            'externallink' => 'https://www.example.com/a01',
            'duedate' => time() + DAYSECS,
        ]);
        $this->student = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Zeller']);
        $this->getDataGenerator()->enrol_user($this->student->id, $course->id, 'student');
        $DB->insert_record('externalassignment_grades', (object)[
            'externalassignment' => $this->instance->id,
            'userid' => $this->student->id,
            'grader' => 2,
            'externallink' => 'https://www.example.com/anna/a01',
            'externalgrade' => 42,
            'externalfeedback' => 'All tests passed',
            'manualgrade' => 3,
            'manualfeedback' => 'Well done',
        ]);
        $this->context = \context_module::instance($this->instance->cmid);
        $PAGE->set_url('/mod/externalassignment/view.php', ['id' => $this->instance->cmid]);
        $PAGE->set_context($this->context);
        $this->renderer = $PAGE->get_renderer('mod_externalassignment');
    }

    /**
     * The link to the external assignment.
     */
    public function test_render_view_link(): void {
        $assign = new assign(null, $this->context);
        $assign->load_db($this->instance->cmid);

        $html = $this->renderer->render(new view_link($this->instance->cmid, $assign));

        $this->assertStringContainsString('href="https://www.example.com/a01"', $html);
        $this->assertStringContainsString(get_string('externallink', 'externalassignment'), $html);
    }

    /**
     * The teacher's summary of the assignment.
     */
    public function test_render_view_summary(): void {
        $html = $this->renderer->render(new view_summary($this->instance->cmid, $this->context));

        $this->assertStringContainsString('https://www.example.com/a01', $html);
        $this->assertStringContainsString("view.php?id={$this->instance->cmid}&amp;action=grading", $html);
    }

    /**
     * The grading overview lists the student with their grades.
     */
    public function test_render_view_grading(): void {
        $html = $this->renderer->render(new view_grading($this->instance->cmid, $this->context, 'lastname', 'asc'));

        $this->assertStringContainsString('Anna Zeller', $html);
        $this->assertStringContainsString('45.00', $html);
        $this->assertStringContainsString("userid={$this->student->id}", $html);
    }

    /**
     * The navigation above the grader form shows the student being graded.
     */
    public function test_render_view_grader_navigation(): void {
        $html = $this->renderer->render(
            new view_grader_navigation($this->instance->cmid, $this->context, $this->student->id)
        );

        $this->assertStringContainsString('Anna', $html);
        $this->assertStringContainsString('Zeller', $html);
        $this->assertStringContainsString('id="user_autocomplete_input"', $html);
    }

    /**
     * The student's view shows their grades and both feedbacks.
     */
    public function test_render_view_student(): void {
        $assign = new assign(null, $this->context);
        $assign->load_db($this->instance->cmid);
        $grade = new grade(null);
        $grade->load_db($assign->get_id(), $this->student->id);

        $html = $this->renderer->render(
            new view_student($this->instance->cmid, $this->context, $assign, $grade, $this->student->id)
        );

        $this->assertStringContainsString('https://www.example.com/anna/a01', $html);
        $this->assertStringContainsString('42.00', $html);
        $this->assertStringContainsString('All tests passed', $html);
        $this->assertStringContainsString('Well done', $html);
    }
}

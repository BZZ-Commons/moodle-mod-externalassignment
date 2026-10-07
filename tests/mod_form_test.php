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

namespace mod_externalassignment;

use mod_externalassignment_mod_form;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the validation rules of the activity settings form.
 *
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversClass(mod_externalassignment_mod_form::class)]
final class mod_form_test extends \advanced_testcase {
    /**
     * Load the form class.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/course/moodleform_mod.php');
        require_once($CFG->dirroot . '/mod/externalassignment/mod_form.php');
    }

    /**
     * Valid settings must pass without errors.
     */
    public function test_valid_settings(): void {
        [$form, $data] = $this->create_form();
        $this->assertSame([], $form->validation($data, []));
    }

    /**
     * Data provider for test_invalid_settings
     * @return array changed form fields => field expected to have an error and its message
     */
    public static function invalid_settings_provider(): array {
        $now = 1790000000;
        return [
            'due date before open date' => [
                ['allowsubmissionsfromdate' => $now, 'duedate' => $now - DAYSECS],
                'duedate',
                'duedateaftersubmissionvalidation',
            ],
            'due date equals open date' => [
                ['allowsubmissionsfromdate' => $now, 'duedate' => $now],
                'duedate',
                'duedateaftersubmissionvalidation',
            ],
            'cut-off date before due date' => [
                ['duedate' => $now, 'cutoffdate' => $now - 1],
                'cutoffdate',
                'cutoffdatevalidation',
            ],
            'cut-off date before open date' => [
                ['allowsubmissionsfromdate' => $now, 'cutoffdate' => $now - 1],
                'cutoffdate',
                'cutoffdatefromdatevalidation',
            ],
            'link without protocol' => [
                ['externallink' => 'www.example.com/assignment'],
                'externallink',
                'externallinkinvalidvalidation',
            ],
            'link with other protocol' => [
                ['externallink' => 'ftp://www.example.com/assignment'],
                'externallink',
                'externallinkinvalidvalidation',
            ],
            'negative external grade max' => [
                ['externalgrademax' => '-1'],
                'externalgrademax',
                'externalgrademaxnegativevalidation',
            ],
            'negative manual grade max' => [
                ['manualgrademax' => '-0.5'],
                'manualgrademax',
                'manualgrademaxnegativevalidation',
            ],
        ];
    }

    /**
     * Each validation rule reports its error on the right field.
     * @param array $changes the form fields to change
     * @param string $field the field expected to have an error
     * @param string $stringid the expected error message
     */
    #[DataProvider('invalid_settings_provider')]
    public function test_invalid_settings(array $changes, string $field, string $stringid): void {
        [$form, $data] = $this->create_form();
        $errors = $form->validation(array_merge($data, $changes), []);
        $this->assertSame([$field => get_string($stringid, 'externalassignment')], $errors);
    }

    /**
     * Optional dates that are not set (0) are not compared.
     */
    public function test_unset_dates_are_not_compared(): void {
        [$form, $data] = $this->create_form();
        $errors = $form->validation(
            array_merge($data, ['allowsubmissionsfromdate' => 1790000000, 'duedate' => 0, 'cutoffdate' => 0]),
            []
        );
        $this->assertSame([], $errors);
    }

    /**
     * The external name must be unique within the course - but saving an assignment again
     * with its own name is fine, and other courses may use the same name.
     */
    public function test_duplicate_external_name(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $existing = $generator->create_instance(['course' => $course->id, 'externalname' => 'm319-a01']);
        $generator->create_instance(['course' => $othercourse->id, 'externalname' => 'm319-a02']);

        // A new assignment with the name of an existing one in the same course.
        [$form, $data] = $this->create_form($course);
        $errors = $form->validation(array_merge($data, ['externalname' => 'm319-a01']), []);
        $this->assertSame(['externalname' => get_string('duplicatenamevalidation', 'externalassignment')], $errors);

        // The name used in another course.
        $this->assertSame([], $form->validation(array_merge($data, ['externalname' => 'm319-a02']), []));

        // Editing the existing assignment keeps its own name.
        [$form, $data] = $this->create_form($course, $existing);
        $this->assertSame([], $form->validation(array_merge($data, ['externalname' => 'm319-a01']), []));
    }

    /**
     * The "needs passing grade" rule is the activity's custom completion rule.
     */
    public function test_completion_rule(): void {
        [$form] = $this->create_form();
        $this->assertTrue($form->completion_rule_enabled(['needspassinggrade' => 1]));
        $this->assertFalse($form->completion_rule_enabled(['needspassinggrade' => 0]));
        $this->assertFalse($form->completion_rule_enabled([]));
    }

    /**
     * Creates the settings form and valid form data for a new or an existing assignment
     * @param \stdClass|null $course the course, a new one is created if null
     * @param \stdClass|null $instance the assignment to edit, null for a new one
     * @return array [form, form data]
     */
    private function create_form(?\stdClass $course = null, ?\stdClass $instance = null): array {
        global $COURSE, $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $course ?? $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $COURSE = $course;
        // A fresh page: the course of a page cannot change once a form has been rendered for it.
        $PAGE = new \moodle_page();
        $PAGE->set_course($course);

        $cm = null;
        $current = (object)[
            'course' => $course->id,
            'section' => 0,
            'modulename' => 'externalassignment',
            'instance' => 0,
            'coursemodule' => 0,
        ];
        if ($instance !== null) {
            $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
            $current->instance = $instance->id;
            $current->coursemodule = $cm->id;
            $current->update = $cm->id;
        }
        $form = new mod_externalassignment_mod_form($current, 0, $cm, $course);

        $data = [
            'course' => $course->id,
            'coursemodule' => $current->coursemodule,
            'instance' => $current->instance,
            'modulename' => 'externalassignment',
            'name' => 'External assignment',
            'externalname' => 'new-name',
            'externallink' => 'https://www.example.com/assignment',
            'allowsubmissionsfromdate' => 0,
            'duedate' => 0,
            'cutoffdate' => 0,
            'externalgrademax' => '100',
            'manualgrademax' => '0',
            'passingpercentage' => '60',
            'completion' => COMPLETION_TRACKING_NONE,
            // Standard fields every submitted activity form contains.
            'cmidnumber' => '',
            'availabilityconditionsjson' => '',
        ];
        return [$form, $data];
    }
}

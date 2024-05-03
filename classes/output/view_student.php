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

use coding_exception;
use core\context;
use dml_exception;
use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use function mod_externalassignment\output\format_text;
use function mod_externalassignment\output\format_time;
use function mod_externalassignment\output\get_string;

/**
 * Renderer for external assignment for students
 *
 * @package   mod_externalassignment
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_student implements renderable, templatable {
    private int|null $coursemoduleid;
    /** @var context the context of the course module for this assign instance
     *               (or just the course if we are creating a new one)
     */
    private context $context;

    /**
     * default constructor
     * @param int $coursemoduleid
     * @param context $context
     */
    public function __construct(int $coursemoduleid, context $context) {
        $this->coursemoduleid = $coursemoduleid;
        $this->context = $context;
    }

    /**
     * Export this data, so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws dml_exception|coding_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/externalassignment/classes/data/assign.php');
        $assignment = new assign();
        $assignment->load_db($this->coursemoduleid, $USER->id);
        require_once($CFG->dirroot . '/mod/externalassignment/classes/data/grade.php');
        $grade = new grade();
        $grade->load_db($this->coursemoduleid, $USER->id);

        $data = new stdClass();
        $data->externallink = $grade->get_externallink();
        $data->gradingstatus = 'TODO';
        $data->modified = format_time($assignment->get_timemodified());
        $timeremaining = $assignment->get_duedate() - time();
        if ($timeremaining <= 0) {
            $due = get_string('assignmentisdue', 'externalassignment');
        } else {
            $due = format_time($timeremaining);
        }
        $data->timeremaining = $due;

        $data->externalgrade = $grade->get_externalgrade();
        $data->externalgrademax = $assignment->get_externalgrademax();
        $data->manualgrade = $grade->get_manualgrade();
        $data->manualgrademax = $assignment->get_manualgrademax();
        $data->totalgrade = $data->externalgrade + $data->manualgrade;
        $data->totalgrademax = $data->externalgrademax + $data->manualgrademax;

        $data->externalfeedback = format_text($grade->get_externalfeedback(), FORMAT_MARKDOWN);
        $data->manualfeedback = $grade->get_manualfeedback();
        return $data;
    }
}

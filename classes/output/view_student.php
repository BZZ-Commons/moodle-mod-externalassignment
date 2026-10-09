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

use core\context;
use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;
use mod_externalassignment\local\student;
use renderable;
use renderer_base;
use templatable;


/**
 * Renderer for external assignment for students
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_student implements renderable, templatable {
    /**
     * @var int|null the id of the course module
     */
    private int|null $coursemoduleid;
    /** @var context the context of the course module for this assign instance
     *               (or just the course if we are creating a new one)
     */
    private context $context;

    /** @var assign $assignment the assignment */
    private assign $assignment;
    /** @var grade $grade the grade */
    private grade $grade;

    /** @var student $student the student */
    private student $student;

    /**
     * default constructor
     * @param int $coursemoduleid
     * @param context $context
     * @param assign $assign
     * @param grade $grade
     * @param int $userid
     */
    public function __construct(
        int $coursemoduleid,
        context $context,
        assign $assign,
        grade $grade,
        int $userid
    ) {
        $this->coursemoduleid = $coursemoduleid;
        $this->context = $context;
        $this->assignment = $assign;
        $this->grade = $grade;
        // Users who may view the activity without being a student (e.g. non-editing teachers or
        // guests) are not in the assignment's student list: show them the plain assignment.
        $this->student = $this->assignment->take_student($userid)
            ?? new student($this->assignment, \core_user::get_user($userid));
    }

    /**
     * Export this data, so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return \stdClass
     * @throws \dml_exception|\coding_exception
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $data = new \stdClass();
        $data->studentlink = $this->grade->get_externallink();
        $data->externallink = $this->assignment->get_externallink();
        $allowsubmissionsfromdate = $this->assignment->get_allowsubmissionsfromdate();
        $data->showexternallink = $this->assignment->is_alwaysshowlink() ||
            ($allowsubmissionsfromdate > 0 && $allowsubmissionsfromdate <= time());
        $timeremaining = $this->student->get_duedate() - time();
        if (empty($this->student->get_duedate())) {
            $due = get_string('noduedate', 'externalassignment');
        } else if ($timeremaining <= 0) {
            $due = get_string('assignmentisdue', 'externalassignment');
        } else {
            $due = format_time($timeremaining);
        }
        $data->timeremaining = $due;

        $data->externalgrade = number_format($this->grade->get_externalgrade(), 2);
        $data->externalgrademax = number_format($this->assignment->get_externalgrademax(), 2);
        if ($this->assignment->get_externalgrademax() == 0) {
            $data->externalpercentage = number_format(0, 2);
        } else {
            $data->externalpercentage = number_format(
                $this->grade->get_externalgrade() / $this->assignment->get_externalgrademax() * 100,
                2
            );
        }
        $data->manualgrade = number_format($this->grade->get_manualgrade(), 2);
        $data->manualgrademax = number_format($this->assignment->get_manualgrademax(), 2);
        if ($this->assignment->get_manualgrademax() == 0) {
            $data->manualpercentage = number_format(0, 2);
        } else {
            $data->manualpercentage = number_format(
                $this->grade->get_manualgrade() / $this->assignment->get_manualgrademax() * 100,
                2
            );
        }
        $data->hasmanualgrade = $data->manualgrademax > 0 || $data->manualgrade != 0;
        $data->totalgrade = number_format($data->externalgrade + $data->manualgrade, 2);
        $data->totalgrademax = number_format($data->externalgrademax + $data->manualgrademax, 2);
        if ($data->totalgrademax == 0) {
            $data->totalpercentage = number_format(0, 2);
            $data->passinggrade = number_format(0, 2);
        } else {
            $data->totalpercentage = number_format($data->totalgrade / $data->totalgrademax * 100, 2);
            $data->passinggrade = number_format(
                $data->totalgrademax * $this->assignment->get_passingpercentage() / 100,
                2
            );
        }

        $data->passingpercentage = number_format($this->assignment->get_passingpercentage(), 2);
        $data->externalfeedback = format_text($this->grade->get_externalfeedback(), FORMAT_MARKDOWN);
        $data->manualfeedback = $this->grade->get_manualfeedback();
        return $data;
    }
}

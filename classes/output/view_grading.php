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
use mod_externalassignment\local\grade_control;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderer for view_grading
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_grading implements renderable, templatable {
    /** @var int @var the id of the coursemodule */
    private int $coursemoduleid;
    /** @var context the context of the course module for this assign instance
     *               (or just the course if we are creating a new one)
     */
    private context $context;

    /** @var string sort the sort field */
    private String $sort;

    /** @var string tdir the sort direction */
    private String $tdir;
    /**
     * default constructor
     * @param int $coursemoduleid
     * @param context $context
     * @param String $sort the sort field
     * @param String $tdir the sort direction
     */
    public function __construct(
        int $coursemoduleid,
        context $context,
        String $sort,
        String $tdir
    ) {
        $this->coursemoduleid = $coursemoduleid;
        $this->context = $context;
        $this->sort = $sort;
        $this->tdir = $tdir;
    }

    /**
     * Export this data, so it can be used as the context for a mustache template.
     * @param renderer_base $output
     * @return \stdClass
     * @throws \dml_exception
     */
    public function export_for_template(renderer_base $output): \stdClass {
        global $PAGE;
        $assign = new assign(null, $this->context);
        $assign->load_db($this->coursemoduleid, $this->sort, $this->tdir);
        $students = $assign->get_students();
        $data = new \stdClass();
        $data->url = $PAGE->url;
        foreach($students as $student) {
            $gradedata = $student->to_stdclass();
            $gradedata->coursemoduleid = $this->coursemoduleid;
            $gradedata->courseid = $assign->get_course();
            $gradedata->externalgrade =  number_format($gradedata->grade->externalgrade,2);
            $gradedata->manualgrade =  number_format($gradedata->grade->manualgrade,2);
            $gradedata->gradefinal = number_format(
                $gradedata->grade->externalgrade + $gradedata->grade->manualgrade,
                2
            );
            $list[] = $gradedata;
        }
        $data->students = $list;
        return $data;
    }
}

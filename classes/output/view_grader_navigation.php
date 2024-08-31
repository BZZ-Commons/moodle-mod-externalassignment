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
use mod_externalassignment\local\grade_control;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderer for view_grader_navigation
 *
 * @package mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_grader_navigation implements renderable, templatable {
    /** @var int @var the id of the coursemodule */
    private int $coursemoduleid;
    /** @var context the context of the course module for this assign instance
     *               (or just the course if we are creating a new one)
     */
    private context $context;

    /** @var int|null the userid of the currently selected user */
    private ?int $userid;

    /**
     * default constructor
     * @param int $coursemoduleid
     * @param context $context
     * @param int|null $userid
     */
    public function __construct(int $coursemoduleid, context $context, ?int $userid) {
        $this->coursemoduleid = $coursemoduleid;
        $this->context = $context;
        $this->userid = $userid;
    }

    /**
     * Export this data, so it can be used as the context for a mustache template.
     * @param renderer_base $output
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function export_for_template(renderer_base $output): \stdClass {

        $gradecontrol = new grade_control($this->coursemoduleid, $this->context);
        $users = $gradecontrol->read_coursemodule_students($this->userid);
        $user = reset($users);

        $data = new \stdClass();
        $data->grades = $gradecontrol->list_grades();
        $data->courseid = $this->context->get_course_context()->instanceid;
        $data->cmid = $this->coursemoduleid;
        $data->name = $this->context->get_context_name();
        $data->userid = $this->get_userid();
        $data->firstname = $user->firstname;
        $data->lastname = $user->lastname;
        $data->email = $user->email;
        $data->duedate = $gradecontrol->get_assign()->get_duedate();
        return $data;
    }

    /**
     * Gets the coursemoduleid
     * @return int
     */
    public function get_coursemoduleid(): int {
        return $this->coursemoduleid;
    }

    /**
     * Sets the coursemoduleid
     * @param int $coursemoduleid
     */
    public function set_coursemoduleid(int $coursemoduleid): void {
        $this->coursemoduleid = $coursemoduleid;
    }

    /**
     * Gets the context
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * Sets the context
     * @param context $context
     */
    public function set_context(context $context): void {
        $this->context = $context;
    }

    /**
     * Gets the userid
     * @return int|null
     */
    public function get_userid(): ?int {
        return $this->userid;
    }

    /**
     * Sets the userid
     * @param int|null $userid
     */
    public function set_userid(?int $userid): void {
        $this->userid = $userid;
    }

}

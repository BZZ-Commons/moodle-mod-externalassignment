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
 * Represents the model of an external assignment
 *
 * @package   mod_externalassignment
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_externalassignment\local;

use core\context;

class grade_control {
    /** @var context the context of the course module for this grade instance
     *               (or just the course if we are creating a new one)
     */
    private context $context;

    /** @var assign $assign the assignexternal instance this grade belongs to */
    private assign $assign;

    /**
     * default constructor
     * @param $coursemoduleid
     * @param $context
     * @param $userid
     * @throws dml_exception
     */
    public function __construct($coursemoduleid, $context, $userid = 0) {
        $this->set_context ($context);
        $this->set_assign(new assign(null));
        $this->get_assign()->load_db($coursemoduleid);
    }

    /**
     * counts the students for the assignment
     * @return int
     */
    public function count_coursemodule_students(): int {
        $users = $this->read_coursemodule_students();
        return count($users);
    }

    /**
     * counts the number of grades
     * @return int
     * @throws dml_exception
     */
    public function count_grades(): int {
        $grades = $this->read_grades();
        return count($grades);
    }

    /**
     * reads the students for the coursemodule filtered by userid(s)
     * @param mixed $filter
     * @return array of students
     */
    private function read_coursemodule_students(mixed $filter = null): array {
        if ($filter != null && !is_array($filter)) {
            $filter = [$filter];
        }
        $userlist = [];
        $users = get_enrolled_users(
            $this->get_context(),
            'mod/assign:submit',
            0,
            'u.id, u.firstname, u.lastname, u.email'
        );
        foreach ($users as $user) {
            if ($filter == null || in_array($user->id, $filter))
                $userlist[$user->id] = $user;
        }
        return $userlist;
    }

    /**
     * reads all grades for the current coursemodule
     * @return array list of grades
     * @throws dml_exception
     */
    private function read_grades(): array {
        global $DB;
        $grades = $DB->get_records_list(
            'assignexternal_grades',
            'assignexternal',
            [$this->get_assign()->get_id()]
        );
        $gradelist = [];
        foreach ($grades as $grade) {
            $gradelist[$grade->userid] = $grade;
        }
        return $gradelist;
    }

    public function get_context(): context {
        return $this->context;
    }

    public function set_context(context $context): void {
        $this->context = $context;
    }

    public function get_assign(): assign {
        return $this->assign;
    }

    public function set_assign(assign $assign): void {
        $this->assign = $assign;
    }



}
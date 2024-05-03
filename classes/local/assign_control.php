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

use cm_info;

/**
 * Controller for the external assignment
 *
 * @package   mod_externalassignment
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class assign_control {
    /** @var \stdClass the assignment record that contains the global settings for this assign instance */
    private \stdClass $instance;
    /** @var ?cm_info the course module for this assign instance */
    private ?cm_info $coursemodule;

    /** @var stdClass the course this assign instance belongs to */
    private $course;

    public function __construct($coursemodulecontext, $coursemodule) {
        $this->coursemodule = cm_info::create($coursemodule);
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(\stdClass $formdata) {
        global $DB;
        $assign = new assign($formdata);
        $assign->set_coursemodule($formdata->coursemodule);
        $returnid = $DB->insert_record('externalassignment', $assign->to_stdclass());
        $this->set_instance($DB->get_record('externalassignment', ['id' => $returnid], '*', MUST_EXIST));
        // Cache the course record.
        $this->set_course($DB->get_record('course', ['id' => $formdata->course], '*', MUST_EXIST));
        $this->grade_item_update();
        return $returnid;
    }

    /**
     * Update this instance in the database.
     *
     * @param \stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     * @throws dml_exception
     */
    public function update_instance(\stdClass $formdata, int $coursemoduleid): bool {
        global $DB;
        global $CFG;
        $assign = new assign($formdata);
        $assign->set_coursemodule($coursemoduleid);
        $result = $DB->update_record('externalassignment', $assign->to_stdclass());
        $this->set_instance($DB->get_record('externalassignment', ['id' => $assign->get_id()], '*', MUST_EXIST));
        $this->grade_item_update();
        return $result;
    }

    /**
     * Delete this instance from the database.
     *
     * @param int $id  the id of the external assignment
     * @throws dml_exception
     */
    public function delete_instance(int $id): void {
        global $DB;
        $DB->delete_records('externalassignment_overrides', ['externalassignment' => $id]);
        $DB->delete_records('externalassignment_grades', ['externalassignment' => $id]);
        $DB->delete_records('externalassignment', ['id' => $id]);
    }

    /**
     * Inserts or updates the grade settings for this assignment in grade_items
     * @return int
     */
    private function grade_item_update(): int {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $params['itemname'] = $this->get_instance()->name;
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $this->get_instance()->externalgrademax + $this->get_instance()->manualgrademax;
        $params['grademin'] = 0;
        return grade_update(
            'mod/externalassignment',
            $this->get_instance()->course,
            'mod',
            'externalassignment',
            $this->get_instance()->id,
            0,
            null,
            $params);
    }

    public function get_instance(): \stdClass {
        return $this->instance;
    }

    public function set_instance(\stdClass $instance): void {
        $this->instance = $instance;
    }

    public function get_coursemodule(): ?cm_info {
        return $this->coursemodule;
    }

    public function set_coursemodule(?cm_info $coursemodule): void {
        $this->coursemodule = $coursemodule;
    }

    public function get_course(): stdClass {
        return $this->course;
    }

    public function set_course(stdClass $course): void {
        $this->course = $course;
    }


}
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
 * represents the override for one user
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class override {
    /** @var int|null the unique id of this grade */
    private ?int $id;
    /** @var int|null the id of the external assignment this grade belongs to */
    private ?int $externalassignment;
    /** @var int|null the id of the user this grade belongs to */
    private ?int $userid;
    /** @var int|null  the time when submissions are allowed */
    private ?int $allowsubmissionsfromdate;
    /** @var int|null the time this assignment is due */
    private ?int $duedate;
    /** @var int|null the time when submissions are no longer possible */
    private ?int $cutoffdate;

    /**
     * default constructor
     */
    public function __construct() {
        $this->set_id(null);
        $this->set_externalassignment(null);
        $this->set_userid(null);
        $this->set_allowsubmissionsfromdate(null);
        $this->set_duedate(null);
        $this->set_cutoffdate(null);
    }

    /**
     * casts the object to a stdClass
     * @return \stdClass
     */
    public function to_stdclass(): \stdClass {
        $result = new \stdClass();
        foreach ($this as $property => $value) {
            if ($value != null) {
                $result->$property = $value;
            }
        }
        return $result;
    }

    /**
     * loads the override for a user from the database
     * @param int $externalassignment
     * @param int $userid
     * @return void
     * @throws \dml_exception
     */
    public function load_db(int $externalassignment, int $userid): void {
        global $DB;
        $data = $DB->get_record(
            'externalassignment_overrides',
            ['externalassignment' => $externalassignment, 'userid' => $userid]
        );
        if (!empty($data)) {
            $this->load_data($data);
        }
    }

    /**
     * loads the attribute values from a stdClass
     * @param \stdClass $data
     * @return void
     */
    private function load_data(\stdClass $data): void {
        $this->set_id($data->id);
        $this->set_externalassignment($data->externalassignment);
        $this->set_userid($data->userid);
        if (!empty($data->allowsubmissionsfromdate)) {
            $this->set_allowsubmissionsfromdate($data->allowsubmissionsfromdate);
        }
        if (!empty($data->duedate)) {
            $this->set_duedate($data->duedate);
        }
        if (!empty($data->cutoffdate)) {
            $this->set_cutoffdate($data->cutoffdate);
        }
    }

    /**
     * initialize the attributes from the formdata
     * @param $formdata
     * @return void
     *
    public function load_formdata($formdata): void {
        $this->load_data($formdata);
        $this->externalassignment = $formdata->id;
    } */

    /**
     * gets the id of this override
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * sets the id of this override
     * @param int|null $id
     */
    public function set_id(?int $id): void {
        $this->id = $id;
    }

    /**
     * gets the id of the external assignment
     * @return int|null
     */
    public function get_externalassignment(): ?int {
        return $this->externalassignment;
    }

    /**
     * sets the id of the external assignment
     * @param int|null $externalassignment
     */
    public function set_externalassignment(?int $externalassignment): void {
        $this->externalassignment = $externalassignment;
    }

    /**
     * gets the id of the user
     * @return int|null
     */
    public function get_userid(): ?int {
        return $this->userid;
    }

    /**
     * sets the id of the user
     * @param int|null $userid
     */
    public function set_userid(?int $userid): void {
        $this->userid = $userid;
    }

    /**
     * gets the time when submissions are allowed
     * @return int|null
     */
    public function get_allowsubmissionsfromdate(): ?int {
        return $this->allowsubmissionsfromdate;
    }

    /**
     * sets the time when submissions are allowed
     * @param int|null $allowsubmissionsfromdate
     */
    public function set_allowsubmissionsfromdate(?int $allowsubmissionsfromdate): void {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
    }

    /**
     * gets the time this assignment is due
     * @return int|null
     */
    public function get_duedate(): ?int {
        return $this->duedate;
    }

    /**
     * sets the time this assignment is due
     * @param int|null $duedate
     */
    public function set_duedate(?int $duedate): void {
        $this->duedate = $duedate;
    }

    /**
     * gets the time when submissions are no longer possible
     * @return int|null
     */
    public function get_cutoffdate(): ?int {
        return $this->cutoffdate;
    }

    /**
     * sets the time when submissions are no longer possible
     * @param int|null $cutoffdate
     */
    public function set_cutoffdate(?int $cutoffdate): void {
        $this->cutoffdate = $cutoffdate;
    }

}

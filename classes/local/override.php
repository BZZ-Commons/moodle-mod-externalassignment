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
 * Represents the override of the submission dates for one user.
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
     * @param \stdClass|null $data
     */
    public function __construct(?\stdClass $data = null) {
        $this->set_id(null);
        $this->set_externalassignment(null);
        $this->set_userid(null);
        $this->set_allowsubmissionsfromdate(null);
        $this->set_duedate(null);
        $this->set_cutoffdate(null);

        if ($data != null) {
            $this->load_data($data);
        }
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
     * loads the override from the database
     * @param int $coursemodule
     * @param int $userid
     * @return void
     * @throws \dml_exception
     */
    public function load_db(int $coursemodule, int $userid): void {
        global $DB;
        $data = $DB->get_record(
            'externalassignment_overrides',
            ['externalassignment' => $coursemodule, 'userid' => $userid]
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
    private function load_data($data): void {
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
     */
    public function load_formdata($formdata): void {
        $this->load_data($formdata);
        $this->externalassignment = $formdata->id;
    }

    /**
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function set_id(?int $id): void {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function get_externalassignment(): ?int {
        return $this->externalassignment;
    }

    /**
     * @param int|null $externalassignment
     */
    public function set_externalassignment(?int $externalassignment): void {
        $this->externalassignment = $externalassignment;
    }

    /**
     * @return int|null
     */
    public function get_userid(): ?int {
        return $this->userid;
    }

    /**
     * @param int|null $userid
     */
    public function set_userid(?int $userid): void {
        $this->userid = $userid;
    }

    /**
     * @return int|null
     */
    public function get_allowsubmissionsfromdate(): ?int {
        return $this->allowsubmissionsfromdate;
    }

    /**
     * @param int|null $allowsubmissionsfromdate
     */
    public function set_allowsubmissionsfromdate(?int $allowsubmissionsfromdate): void {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
    }

    /**
     * @return int|null
     */
    public function get_duedate(): ?int {
        return $this->duedate;
    }

    /**
     * @param int|null $duedate
     */
    public function set_duedate(?int $duedate): void {
        $this->duedate = $duedate;
    }

    /**
     * @return int|null
     */
    public function get_cutoffdate(): ?int {
        return $this->cutoffdate;
    }

    /**
     * @param int|null $cutoffdate
     */
    public function set_cutoffdate(?int $cutoffdate): void {
        $this->cutoffdate = $cutoffdate;
    }

}
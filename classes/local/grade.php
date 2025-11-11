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
 * Represents the model of a students grade for an external assignment
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade {
    /** @var int|null the unique id of this grade */
    private ?int $id;
    /** @var int|null the id of the external assignment this grade belongs to */
    private ?int $externalassignment;
    /** @var int|null the id of the user this grade belongs to */
    private ?int $userid;
    /** @var int the userid of the grader */
    private int $grader;
    /** @var string the URL of the submission in the external system */
    private string $externallink;
    /** @var float the grade from the external system */
    private float $externalgrade = 0.0;
    /** @var string|null the feedback from the external system as HTML-code */
    private ?string $externalfeedback = '';
    /** @var float the grade from manual grading */
    private float $manualgrade = 0.0;
    /** @var string|null the manual feedback as HTML-code */
    private ?string $manualfeedback = '';

    /**
     * default constructor
     * @param \stdClass|null $formdata
     */
    public function __construct(?\stdClass $formdata) {
        global $USER;
        if (isset($formdata)) {
            $this->load_data($formdata);
            $this->set_externalassignment($formdata->externalassignmentid);
        } else {
            $this->set_id(null);
            $this->set_externalassignment(null);
            $this->set_userid(null);
            $this->set_externallink('');
            $this->set_externalgrade(0.0);
            $this->set_externalfeedback('');
            $this->set_manualgrade(0.0);
            $this->set_manualfeedback('');
        }
        $this->set_grader($USER->id);

    }

    /**
     * Loads the values for the attributes
     * @param \stdClass $data the object that contains the data
     * @return void
     */
    private function load_data(\stdClass $data): void {
        if (property_exists($data, 'gradeid')) {
            $this->id = $data->gradeid;
        } elseif (property_exists($data, 'externalassignment')) {
            $this->set_id($data->gradeid);
        } else {
            $this->id = -1;
        }

        $this->set_userid($data->userid);
        $this->set_externallink($data->externallink);
        if (empty($data->externalgrade)) {
            $this->set_externalgrade(0.0);
        } else {
            $this->set_externalgrade($data->externalgrade);
        }
        if (empty($data->manualgrade)) {
            $this->set_manualgrade(0.0);
        } else {
            $this->set_manualgrade($data->manualgrade);
        }
        if (is_array($data->externalfeedback)) {
            $this->set_externalfeedback($data->externalfeedback['text']);
        } else {
            $this->set_externalfeedback($data->externalfeedback);
        }
        if (is_array($data->manualfeedback)) {
            $this->set_manualfeedback($data->manualfeedback['text']);
        } else {
            $this->set_manualfeedback($data->manualfeedback);
        }
    }

    /**
     * loads the grading data from the database
     * @param int $externalassignment  the id of the external assignment
     * @param int $userid
     * @return bool successful
     * @throws \dml_exception
     */
    public function load_db($externalassignment, $userid): bool {
        global $DB;
        $data = $DB->get_record(
            'externalassignment_grades',
            ['externalassignment' => $externalassignment, 'userid' => $userid],
            '*',
            IGNORE_MISSING
        );

        if ($data) {
            $data->gradeid = $data->id;
            $this->load_data($data);
            $this->externalassignment = $data->externalassignment;
            $this->grader = $data->grader;
            return true;
        }
        return false;
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
     * calculates the final grade
     * @return float
     */
    public function get_finalgrade() {
        return $this->externalgrade + $this->manualgrade;
    }
    /**
     * Gets the id
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Sets the id
     * @param int|null $id
     */
    public function set_id(?int $id): void {
        $this->id = $id;
    }

    /**
     * Gets the externalassignment
     * @return int|null
     */
    public function get_externalassignment(): ?int {
        return $this->externalassignment;
    }

    /**
     * Sets the externalassignment
     * @param int|null $externalassignment
     */
    public function set_externalassignment(?int $externalassignment): void {
        $this->externalassignment = $externalassignment;
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

    /**
     * Gets the grader
     * @return int
     */
    public function get_grader(): int {
        return $this->grader;
    }

    /**
     * Sets the grader
     * @param int $grader
     */
    public function set_grader(int $grader): void {
        $this->grader = $grader;
    }

    /**
     * Gets the externallink
     * @return string
     */
    public function get_externallink(): string {
        return empty($this->externallink) ? '' : $this->externallink;
    }

    /**
     * Sets the externallink
     * @param string $externallink
     */
    public function set_externallink(string $externallink): void {

            $this->externallink = $externallink;

    }

    /**
     * Gets the externalgrade
     * @return float
     */
    public function get_externalgrade(): float {
        return empty($this->externalgrade) ? 0.0 : $this->externalgrade;
    }

    /**
     * Sets the externalgrade
     * @param float $externalgrade
     */
    public function set_externalgrade(float $externalgrade): void {
        $this->externalgrade = $externalgrade;
    }

    /**
     * Gets the externalfeedback
     * @return string|null
     */
    public function get_externalfeedback(): ?string {
        return $this->externalfeedback;
    }

    /**
     * Sets the externalfeedback
     * @param string|null $externalfeedback
     */
    public function set_externalfeedback(?string $externalfeedback): void {
        $this->externalfeedback = $externalfeedback;
    }

    /**
     * Gets the manualgrade
     * @return float
     */
    public function get_manualgrade(): float {
        return empty($this->manualgrade) ? 0.0 : $this->manualgrade;
    }

    /**
     * Sets the manualgrade
     * @param float $manualgrade
     */
    public function set_manualgrade(float $manualgrade): void {
        $this->manualgrade = $manualgrade;
    }

    /**
     * Gets the manualfeedback
     * @return string|null
     */
    public function get_manualfeedback(): ?string {
        return $this->manualfeedback;
    }

    /**
     * Sets the manualfeedback
     * @param string|null $manualfeedback
     */
    public function set_manualfeedback(?string $manualfeedback): void {
        $this->manualfeedback = $manualfeedback;
    }

}

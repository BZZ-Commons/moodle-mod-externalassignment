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

class grade {
    /** @var int|null the unique id of this grade */
    private $id;
    /** @var int|null the id of the external assignment this grade belongs to */
    private $externalassignment;
    /** @var int|null the id of the user this grade belongs to */
    private $userid;
    /** @var int the userid of the grader */
    private $grader;
    /** @var string the URL of the submission in the external system */
    private $externallink;
    /** @var float the grade from the external system */
    private $externalgrade;
    /** @var string|null the feedback from the external system as HTML-code */
    private $externalfeedback;
    /** @var float the grade from manual grading */
    private $manualgrade;
    /** @var string|null the manual feedback as HTML-code */
    private $manualfeedback;

    /**
     * default constructor
     * @param \stdClass|null $formdata
     */
    public function __construct(?\stdClass $formdata) {
        global $USER;
        if (isset($formdata)) {
            $this->load_data($formdata);
            $this->set_externalassignment($formdata->externalassignmentid);
        }
        $this->set_grader($USER->id);

    }

    /**
     * Loads the values for the attributes
     * @param \stdClass $data the object that contains the data
     * @return void
     */
    private function load_data(\stdClass $data): void {
        $this->set_id($data->gradeid);
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

    public function get_id(): ?int {
        return $this->id;
    }

    public function set_id(?int $id): void {
        $this->id = $id;
    }

    public function get_externalassignment(): ?int {
        return $this->externalassignment;
    }

    public function set_externalassignment(?int $externalassignment): void {
        $this->externalassignment = $externalassignment;
    }

    public function get_userid(): ?int {
        return $this->userid;
    }

    public function set_userid(?int $userid): void {
        $this->userid = $userid;
    }

    public function get_grader(): int {
        return $this->grader;
    }

    public function set_grader(int $grader): void {
        $this->grader = $grader;
    }

    public function get_externallink(): string {
        return $this->externallink;
    }

    public function set_externallink(string $externallink): void {
        $this->externallink = $externallink;
    }

    public function get_externalgrade(): float {
        return $this->externalgrade;
    }

    public function set_externalgrade(float $externalgrade): void {
        $this->externalgrade = $externalgrade;
    }

    public function get_externalfeedback(): ?string {
        return $this->externalfeedback;
    }

    public function set_externalfeedback(?string $externalfeedback): void {
        $this->externalfeedback = $externalfeedback;
    }

    public function get_manualgrade(): float {
        return $this->manualgrade;
    }

    public function set_manualgrade(float $manualgrade): void {
        $this->manualgrade = $manualgrade;
    }

    public function get_manualfeedback(): ?string {
        return $this->manualfeedback;
    }

    public function set_manualfeedback(?string $manualfeedback): void {
        $this->manualfeedback = $manualfeedback;
    }


}
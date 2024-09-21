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
 * Represents the model of a student with his grade and overrides
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student {
    /** @var assign  external assignement */
    private assign $assign;
    /** @var int|null unique id of the user */
    private ?int $userid;

    /** @var string|null username of the user */
    private ?string $username;

    /** @var string|null firstname of the user */
    private ?string $firstname;

    /** @var string|null lastname of the user */
    private ?string $lastname;

    /** @var string|null email of the user */
    private ?string $email;

    /** @var ?Grade the grade-object for this user */
    private ?Grade $grade;

    /** @var ?Override the override-object for this user */
    private ?Override $override;


    /**
     * student constructor.
     *
     * @param assign $assign
     * @param \stdClass|null $student
     */
    public function __construct(assign $assign, ?\stdClass $student) {
        $this->set_assign($assign);
        $this->set_userid($student->id);
        $this->set_username($student->username);
        $this->set_firstname($student->firstname);
        $this->set_lastname($student->lastname);
        $this->set_email($student->email);
        $this->set_grade(null);
        $this->set_override(null);
    }

    /**
     * casts the object to a stdClass
     * @return \stdClass
     */
    public function to_stdclass(): \stdClass {
        $result = new \stdClass();
        $result->userid = $this->get_userid();
        $result->username = $this->get_username();
        $result->firstname = $this->get_firstname();
        $result->lastname = $this->get_lastname();
        $result->email = $this->get_email();
        $result->status = $this->get_status();
        if (!empty($this->get_grade())) {
            $result->grade = $this->get_grade()->to_stdclass();
        }
        if (!empty($this->get_override())) {
            $result->override = $this->get_override()->to_stdclass();
        }

        return $result;
    }

    public function get_status(): string {
        if (empty($this->get_grade())) {
            return 'not submitted';
        }
        # calculate the maximum and the passing grade
        $maximumgrade = $this->get_assign()->get_externalgrademax() + $this->get_assign()->get_manualgrademax();
        $passinggrade = 0;
        if ($this->get_assign()->get_needspassinggrade() != 0) {
            $passinggrade = $maximumgrade * $this->get_assign()->get_passingpercentage() / 100;
        }

        if ($this->get_grade()->get_finalgrade() >= $passinggrade) {
            return 'passed';
        }

        # check if there is no override for the due date
        if (empty($this->get_override()) or $this->get_override()->get_duedate() == 0) {
            $duedate = $this->get_assign()->get_duedate();
        } else {
            $duedate = $this->get_override()->get_duedate();
        }


        if ($duedate > 0 and time() > $duedate) {
            return 'overdue';
        }
        return 'pending';
    }

    /**
     * Gets the assign
     * @return assign
     */
    public function get_assign(): assign {
        return $this->assign;
    }

    /**
     * Sets the assign
     * @param assign $assign
     */
    public function set_assign(assign $assign): void {
        $this->assign = $assign;
    }

    /**
     * Gets the id
     * @return int|null
     */
    public function get_userid(): ?int {
        return $this->userid;
    }

    /**
     * Sets the id
     * @param int|null $userid
     */
    public function set_userid(?int $userid): void {
        $this->userid = $userid;
    }

    /**
     * Gets the username
     * @return string|null
     */
    public function get_username(): ?string {
        return $this->username;
    }

    /**
     * Sets the username
     * @param string|null $username
     */
    public function set_username(?string $username): void {
        $this->username = $username;
    }

    /**
     * Gets the firstname
     * @return string|null
     */
    public function get_firstname(): ?string {
        return $this->firstname;
    }

    /**
     * Sets the firstname
     * @param string|null $firstname
     */
    public function set_firstname(?string $firstname): void {
        $this->firstname = $firstname;
    }

    /**
     * Gets the lastname
     * @return string|null
     */
    public function get_lastname(): ?string {
        return $this->lastname;
    }

    /**
     * Sets the lastname
     * @param string|null $lastname
     */
    public function set_lastname(?string $lastname): void {
        $this->lastname = $lastname;
    }

    /**
     * Gets the email
     * @return string|null
     */
    public function get_email(): ?string {
        return $this->email;
    }

    /**
     * Sets the email
     * @param string|null $email
     */
    public function set_email(?string $email): void {
        $this->email = $email;
    }

    /**
     * Gets the grade
     * @return Grade|null
     */
    public function get_grade(): ?Grade {
        return $this->grade;
    }

    /**
     * Sets the grade
     * @param Grade|null $grade
     */
    public function set_grade(?Grade $grade): void {
        $this->grade = $grade;
    }

    /**
     * Gets the override
     * @return Override|null
     */
    public function get_override(): ?Override {
        return $this->override;
    }

    /**
     * Sets the override
     * @param Override|null $override
     */
    public function set_override(?Override $override): void {
        $this->override = $override;
    }

}
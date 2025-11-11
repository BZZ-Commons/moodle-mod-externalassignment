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

use core\context;

/**
 * Represents the model of an external assignment
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign {
    /** @var int|null unique id of the external assignment */
    private ?int $id;
    /** @var int|null the id of the course this assignment belongs to */
    private ?int $course;
    /** @var context|null the context of this cousemodule */
    private ?context $context;
    /** @var string the name of the assignment */
    private string $name;
    /** @var string the description of the assignment */
    private string $intro;
    /** @var string the format of the intro */
    private string $introformat;
    /** @var bool if not set the description won't show until the allowsubmissionsformdate */
    private bool $alwaysshowdescription;
    /** @var string the name of the assignment in the external system */
    private string $externalname;
    /** @var string the URL to the assignment in the external system */
    private string $externallink;
    /** @var bool if not set the externallink won't show until the allowsubmissionsformdate */
    private bool $alwaysshowlink;
    /** @var int|null  the time when submissions are allowed */
    private ?int $allowsubmissionsfromdate;
    /** @var int|null the time this assignment is due */
    private ?int $duedate;
    /** @var int|null the time when submissions are no longer possible */
    private ?int $cutoffdate;
    /** @var int|null the date and time this assignment was last modified */
    private ?int $timemodified;
    /** @var float|null the maximum grade from the external system */
    private ?float $externalgrademax;
    /** @var float|null the maximum grade from the manual grading */
    private ?float $manualgrademax;
    /** @var float|null the percentage of the total grade (external + manual) to reach for completing the assignment */
    private ?float $passingpercentage;
    /** @var int 1 = the user must reach the passingpercentage to complete the assignment */
    private int $needspassinggrade;
    /** @var array the student-objects that are enrolled in this course */
    private array $students;

    /**
     * Default constructor with optional formdata
     * @param \stdClass|null $formdata
     * @param context|null $context
     */
    public function __construct(?\stdClass $formdata = null, ?context $context = null) {
        $this->set_context($context);
        if (isset($formdata)) {
            $this->load_data($formdata);
            $this->set_id((int)$formdata->instance);
            $this->set_timemodified(time());
        } else {
            $this->set_id(null);
        }
        $this->set_students([]);
    }

    /**
     * loads the attributes of the assignment from the database
     * @param int $coursemoduleid
     * @param string|null $sort the sort field for the students
     * @param string|null $tdir the direction of the sort
     * @param int|null $userid
     * @return void
     * @throws \dml_exception
     */
    public function load_db(
        int     $coursemoduleid,
        ?string $sort = 'lastname',
        ?string $tdir = 'asc',
        ?int    $userid = null
    ): void {
        global $DB;
        $query = 'SELECT cm.instance, ae.* ' .
            'FROM {course_modules} cm ' .
            'JOIN {externalassignment} ae ON (cm.instance = ae.id) ' .
            'WHERE cm.id=:coursemoduleid';
        $data = $DB->get_record_sql($query, ['coursemoduleid' => $coursemoduleid]);
        if (!empty($data)) {
            $this->set_id($data->id);
            $this->load_data($data);
            $this->set_timemodified($data->timemodified);
            if (!empty($this->get_context())) {
                $this->load_students();
                $this->load_grades($this->get_id(), $userid);
                $this->load_overrides($this->get_id(), $userid);

                $this->sort_students($sort, $tdir);
            }
        }
    }

    /**
     * loads the assignment using the external assignmentname and userid
     * @param string $assignmentname
     * @param int $userid
     * @return void
     * @throws \dml_exception
     */
    public function load_db_external(string $assignmentname, int $userid): void {
        global $DB, $CFG;

        $query =
            'SELECT ae.id, ae.course, ae.externalgrademax, ae.duedate, ae.cutoffdate, ae.externalname,
                    cm.id as coursemoduleid' .
            ' FROM {user_enrolments} ue' .
            ' JOIN {enrol} en ON (ue.enrolid = en.id)' .
            ' JOIN {externalassignment} ae ON (ae.course = en.courseid)' .
            ' JOIN {course_modules} cm ON (cm.instance = ae.id)' .
            ' WHERE ae.externalname=:assignmentname AND ue.userid=:userid';
        $data = $DB->get_record_sql(
            $query,
            [
                'userid' => $userid,
                'assignmentname' => $assignmentname,
            ]
        );
        $context = \context_module::instance($data->coursemoduleid);

        if (!empty($data) &&
            !has_capability('mod/externalassignment:grade', $context, $userid) &&  // no grader/tea
            has_capability('mod/externalassignment:submit', $context, $userid)     // student
        ) {
            require_once($CFG->dirroot . '/mod/externalassignment/classes/local/student.php');
            $this->set_id($data->id);
            $this->set_course($data->course);
            $this->set_externalgrademax($data->externalgrademax);
            $this->set_duedate($data->duedate);
            $this->set_cutoffdate($data->cutoffdate);
            $this->set_externalname($data->externalname);
            $this->load_overrides($data->id, $userid);
        }
    }

    /**
     * Loads the values for the attributes
     * @param \stdClass $data the object that contains the data
     * @return void
     */
    private function load_data(\stdClass $data): void {
        $this->set_course($data->course);
        $this->set_name($data->name);
        $this->set_intro($data->intro);
        $this->set_introformat($data->introformat);
        $this->set_alwaysshowdescription(!empty($data->alwaysshowdescription));
        $this->set_externalname($data->externalname);
        $this->set_externallink($data->externallink);
        $this->set_alwaysshowlink(!empty($data->alwaysshowlink));
        $this->set_allowsubmissionsfromdate($data->allowsubmissionsfromdate);
        $this->set_duedate($data->duedate);
        $this->set_cutoffdate($data->cutoffdate);
        $this->set_externalgrademax($data->externalgrademax);
        $this->set_manualgrademax($data->manualgrademax);
        $this->set_passingpercentage($data->passingpercentage);

        if (isset($data->needspassinggrade)) {
            $this->set_needspassinggrade($data->needspassinggrade);
        } else {
            $this->set_needspassinggrade(0);
        }
    }

    /**
     * loads the students for this assignment
     * @return void
     * @throws \dml_exception
     */
    private function load_students(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/externalassignment/classes/local/student.php');
        // FIXME: Find out why autoloading does not work here.
        $users = get_enrolled_users(
            $this->get_context(),
            'mod/externalassignment:submit',
            '',
            'u.*',
            null,
            0,
            0,
            true
        );
        foreach ($users as $user) {
            $student = new student($this, $user);
            $this->students[$user->id] = $student;
        }
    }

    /**
     * sorts the students by sort field and direction
     * @param String $sort the sort field
     * @param String $tdir the sort direction
     * @return void
     */
    private function sort_students(string $sort, string $tdir): void {
        if ($sort == 'lastname' && $tdir == 'asc') {
            uasort($this->students, function ($a, $b) {
                return strcmp($a->get_lastname(), $b->get_lastname());
            });
        } else if ($sort == 'lastname' && $tdir == 'desc') {
            uasort($this->students, function ($a, $b) {
                return strcmp($b->get_lastname(), $a->get_lastname());
            });
        } else if ($sort == 'firstname' && $tdir == 'asc') {
            uasort($this->students, function ($a, $b) {
                return strcmp($a->get_firstname(), $b->get_firstname());
            });
        } else if ($sort == 'firstname' && $tdir == 'desc') {
            uasort($this->students, function ($a, $b) {
                return strcmp($b->get_firstname(), $a->get_firstname());
            });
        } else if ($sort == 'grade' && $tdir == 'asc') {
            uasort($this->students, function ($a, $b) {
                if ($a->get_grade() === null) {
                    return 1;
                } else if ($b->get_grade() === null) {
                    return - 1;
                } else {
                    return $a->get_grade()->get_externalgrade() + $a->get_grade()->get_manualgrade() <=
                        $b->get_grade()->get_externalgrade() + $b->get_grade()->get_manualgrade();
                }

            });
        } else if ($sort == 'grade' && $tdir == 'desc') {
            uasort($this->students, function ($a, $b) {
                if ($a->get_grade() === null) {
                    return - 1;
                } else if ($b->get_grade() === null) {
                    return 1;
                } else {
                    return $a->get_grade()->get_externalgrade() + $a->get_grade()->get_manualgrade() >=
                        $b->get_grade()->get_externalgrade() + $b->get_grade()->get_manualgrade();
                }

            });
        } else if ($sort == 'status' && $tdir == 'asc') {
            uasort($this->students, function ($a, $b) {
                return strcmp($a->get_status(null), $b->get_status(null));
            });
        } else if ($sort == 'status' && $tdir == 'desc') {
            uasort($this->students, function ($a, $b) {
                return strcmp($b->get_status(null), $a->get_status(null));
            });
        }
    }


    /**
     * returns a student from the array identified by the userid
     * @param int $userid
     * @return void
     */
    public function take_student(int $userid): ?student {
        if ($this->students[$userid] !== null) {
            return $this->students[$userid];
        }
        return null;
    }

    /**
     * counts the students for this assignment
     * @return int
     */
    public function count_students(): int {
        return count($this->students);
    }

    /**
     * loads all the grades for this assignment
     * @param int $coursemodule
     * @param int|null $userid
     * @return void
     */
    private function load_grades(int $coursemodule, ?int $userid): void {
        global $DB;
        $conditions = ['externalassignment' => $coursemodule];
        if (!empty($userid)) {
            $conditions['userid'] = $userid;
        }
        $data = $DB->get_records('externalassignment_grades', $conditions);
        foreach ($data as $record) {
            $grade = new grade($record);
            $this->students[$record->userid]->set_grade($grade);
        }
    }

    /**
     * counts the number of graded students
     * @return int
     */
    public function count_grades(): int {
        $count = 0;
        foreach ($this->students as $student) {
            if ($student->get_grade() !== null) {
                $count ++;
            }
        }
        return $count;
    }

    /**
     * creates a list of all users and grades
     * @return array list of users and grades/feedback
     * @throws \dml_exception
     */
    public function list_grades(): array {
        $students = $this->get_students();
        $gradelist = [];
        foreach ($students as $userid => $user) {
            $grade = new \stdClass();
            $grade->courseid = $this->get_course();
            $grade->coursemoduleid = $this->get_id();
            $grade->userid = $userid;
            $grade->firstname = $user->get_firstname();
            $grade->lastname = $user->get_lastname();
            if (!empty($user->get_grade())) {
                $gradedata = $user->get_grade();
                $grade->externalgrade = number_format($gradedata->get_externalgrade(), 2);
                $grade->manualgrade = number_format($gradedata->get_manualgrade(), 2);
                $grade->gradefinal = number_format($gradedata->get_externalgrade() + $gradedata->get_manualgrade(), 2);
                $grade->status = $user->get_status($grade);
            } else {
                $grade->status = $user->get_status(null);
            }
            $gradelist[] = $grade;
        }

        return $gradelist;
    }

    /**
     * loads all the user overrides for this assignment
     * @param int $coursemodule
     * @param int|null $userid
     * @return void
     */
    private function load_overrides(int $coursemodule, ?int $userid): void {
        global $CFG, $DB;
        $conditions = ['externalassignment' => $coursemodule];
        if (!empty($userid)) {
            $conditions['userid'] = $userid;
        }

        $data = $DB->get_records(
            'externalassignment_overrides',
            $conditions
        );
        require_once($CFG->dirroot . '/mod/externalassignment/classes/local/override.php');
        // FIXME: Find out why autoloading does not work here.
        foreach ($data as $record) {
            $override = new override($record);
            if ($this->students[$record->userid] == null) {
                $this->students[$record->userid] = new student($this, $DB->get_record('user', ['id' => $record->userid]));
            }
            $this->students[$record->userid]->set_override($override);
        }

    }

    /**
     * casts the object to a stdClass
     * @return \stdClass
     */
    public function to_stdclass(): \stdClass {
        $result = new \stdClass();
        foreach ($this as $property => $value) {

            $result->$property = $value;
        }
        return $result;
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
     * Gets the course
     * @return int|null
     */
    public function get_course(): ?int {
        return $this->course;
    }

    /**
     * Sets the course
     * @param int|null $course
     */
    public function set_course(?int $course): void {
        $this->course = $course;
    }

    /**
     * Gets the context
     * @return context|null
     */
    public function get_context(): ?context {
        return $this->context;
    }

    /**
     * Sets the context
     * @param context|null the $context
     */
    public function set_context(?context $context): void {
        $this->context = $context;
    }

    /**
     * Gets the name
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Sets the name
     * @param string $name
     */
    public function set_name(string $name): void {
        $this->name = $name;
    }

    /**
     * Gets the intro
     * @return string
     */
    public function get_intro(): string {
        return $this->intro;
    }

    /**
     * Sets the intro
     * @param string $intro
     */
    public function set_intro(string $intro): void {
        $this->intro = $intro;
    }

    /**
     * Gets the introformat
     * @return string
     */
    public function get_introformat(): string {
        return $this->introformat;
    }

    /**
     * Sets the introformat
     * @param string $introformat
     */
    public function set_introformat(string $introformat): void {
        $this->introformat = $introformat;
    }

    /**
     * Gets the alwaysshowdescription
     * @return bool
     */
    public function is_alwaysshowdescription(): bool {
        return $this->alwaysshowdescription;
    }

    /**
     * Sets the alwaysshowdescription
     * @param bool $alwaysshowdescription
     */
    public function set_alwaysshowdescription(bool $alwaysshowdescription): void {
        $this->alwaysshowdescription = $alwaysshowdescription;
    }

    /**
     * Gets the externalname
     * @return string
     */
    public function get_externalname(): string {
        return $this->externalname;
    }

    /**
     * Sets the externalname
     * @param string $externalname
     */
    public function set_externalname(string $externalname): void {
        $this->externalname = $externalname;
    }

    /**
     * Gets the externallink
     * @return string
     */
    public function get_externallink(): string {
        return $this->externallink;
    }

    /**
     * Sets the externallink
     * @param string $externallink
     */
    public function set_externallink(string $externallink): void {
        $this->externallink = $externallink;
    }

    /**
     * Gets the alwaysshowlink
     * @return bool
     */
    public function is_alwaysshowlink(): bool {
        return $this->alwaysshowlink;
    }

    /**
     * Sets the alwaysshowlink
     * @param bool $alwaysshowlink
     */
    public function set_alwaysshowlink(bool $alwaysshowlink): void {
        $this->alwaysshowlink = $alwaysshowlink;
    }

    /**
     * Gets the allowsubmissionsfromdate
     * @return int|null
     */
    public function get_allowsubmissionsfromdate(): ?int {
        return $this->allowsubmissionsfromdate;
    }

    /**
     * Sets the allowsubmissionsfromdate
     * @param int|null $allowsubmissionsfromdate
     */
    public function set_allowsubmissionsfromdate(?int $allowsubmissionsfromdate): void {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
    }

    /**
     * Gets the duedate
     * @return int|null
     */
    public function get_duedate(): ?int {
        return $this->duedate;
    }

    /**
     * Sets the duedate
     * @param int|null $duedate
     */
    public function set_duedate(?int $duedate): void {
        $this->duedate = $duedate;
    }

    /**
     * Gets the cutoffdate
     * @return int|null
     */
    public function get_cutoffdate(): ?int {
        return $this->cutoffdate;
    }

    /**
     * Sets the cutoffdate
     * @param int|null $cutoffdate
     */
    public function set_cutoffdate(?int $cutoffdate): void {
        $this->cutoffdate = $cutoffdate;
    }

    /**
     * Gets the timemodified
     * @return int|null
     */
    public function get_timemodified(): ?int {
        return $this->timemodified;
    }

    /**
     * Sets the timemodified
     * @param int|null $timemodified
     */
    public function set_timemodified(?int $timemodified): void {
        $this->timemodified = $timemodified;
    }

    /**
     * Gets the externalgrademax
     * @return float|null
     */
    public function get_externalgrademax(): ?float {
        return $this->externalgrademax;
    }

    /**
     * Sets the externalgrademax
     * @param float|null $externalgrademax
     */
    public function set_externalgrademax(?float $externalgrademax): void {
        $this->externalgrademax = $externalgrademax;
    }

    /**
     * Gets the manualgrademax
     * @return float|null
     */
    public function get_manualgrademax(): ?float {
        return $this->manualgrademax;
    }

    /**
     * Sets the manualgrademax
     * @param float|null $manualgrademax
     */
    public function set_manualgrademax(?float $manualgrademax): void {
        $this->manualgrademax = $manualgrademax;
    }

    /**
     * Gets the passingpercentage
     * @return float|null
     */
    public function get_passingpercentage(): ?float {
        return $this->passingpercentage;
    }

    /**
     * Sets the passingpercentage
     * @param float|null $passingpercentage
     */
    public function set_passingpercentage(?float $passingpercentage): void {
        $this->passingpercentage = $passingpercentage;
    }

    /**
     * Gets the needspassinggrade
     * @return int
     */
    public function get_needspassinggrade(): int {
        return $this->needspassinggrade;
    }

    /**
     * Sets the needspassinggrade
     * @param int $needspassinggrade
     */
    public function set_needspassinggrade(int $needspassinggrade): void {
        $this->needspassinggrade = $needspassinggrade;
    }

    /**
     * Gets the students
     * @return array
     */
    public function get_students(): array {
        return $this->students;
    }

    /**
     * sets the students
     * @param array $students
     * @return void
     */
    public function set_students(array $students): void {
        $this->students = $students;
    }

    /**
     * gets a student from the array
     * @param int $userid
     * @return \mod_externalassignment\local\student
     */
    public function get_student(int $userid): student {
        return $this->students[$userid];
    }
}

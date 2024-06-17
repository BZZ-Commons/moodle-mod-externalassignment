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
use mod_externalassignment\form\grader_form;
use mod_externalassignment\form\override_form;

/**
 * Represents the model of an external assignment
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_control {
    /** @var int  the coursemodule-id */
    private $coursemoduleid;

    /** @var int the course-id */
    private $courseid;

    /** @var context the context of the course module for this grade instance
     *               (or just the course if we are creating a new one)
     */
    private context $context;

    /** @var assign $assign the externalassignment instance this grade belongs to */
    private assign $assign;
    /** @var array A key used to identify userlists created by this object. */
    private array $userlist;

    /** @var int The key to identify the user */
    private $userid;

    /**
     * default constructor
     * @param $coursemoduleid
     * @param $context
     * @param int|null $userid
     * @throws \dml_exception
     */
    public function __construct($coursemoduleid, $context, ?int $userid = 0) {
        $this->set_coursemoduleid($coursemoduleid);
        $this->set_courseid($context->get_course_context()->instanceid);
        $this->set_context($context);
        $this->set_assign(new assign(null));
        $this->get_assign()->load_db($coursemoduleid);
        $this->set_userlist($this->read_coursemodule_students());
        $this->set_userid($userid);
    }

    /**
     * counts the students for the assignment
     * @return int
     */
    public function count_coursemodule_students(): int {
        $users = $this->get_userlist();
        return count($users);
    }

    /**
     * counts the number of grades
     * @return int
     * @throws \dml_exception
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
    public function read_coursemodule_students(mixed $filter = null): array {
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
            $exists = true;
            if ($filter != null) {
                $exists = in_array($user->id, $filter);

            }
            if ($exists) {
                $userlist[$user->id] = $user;
            }
        }
        return $userlist;
    }

    /**
     * reads all grades for the current coursemodule
     * @return array list of grades
     * @throws \dml_exception
     */
    private function read_grades(): array {
        global $DB;
        $grades = $DB->get_records_list(
            'externalassignment_grades',
            'externalassignment',
            [$this->get_assign()->get_id()]
        );
        $gradelist = [];
        foreach ($grades as $grade) {
            $gradelist[$grade->userid] = $grade;
        }
        return $gradelist;
    }

    /**
     * creates a list of all users and grades
     * @return array list of users and grades/feedback
     * @throws \dml_exception
     */
    public function list_grades(): array {
        $grades = $this->read_grades();
        $gradelist = [];
        foreach ($this->userlist as $userid => $user) {
            $grade = new \stdClass();
            $grade->courseid = $this->get_courseid();
            $grade->coursemoduleid = $this->get_coursemoduleid();
            $grade->userid = $userid;
            $grade->firstname = $user->firstname;
            $grade->lastname = $user->lastname;
            if (array_key_exists($userid, $grades)) {
                $gradedata = $grades[$userid];
                $grade->externalgrade = $gradedata->externalgrade;
                $grade->manualgrade = $gradedata->manualgrade;
                $grade->gradefinal = $gradedata->externalgrade + $gradedata->manualgrade;
                $grade->status = $this->get_status($grade);
            } else {
                $grade->status = $this->get_status(null);
            }
            $gradelist[] = $grade;
        }

        return $gradelist;
    }

    /**
     * process the feedback form for a student
     * @return void
     * @throws \dml_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function process_feedback(): void {
        global $CFG;
        $users = $this->read_coursemodule_students([$this->userid]);
        $user = reset($users);
        $data = new \stdClass();

        $assignment = new assign(null);
        $assignment->load_db($this->coursemoduleid);
        $data->id = $this->coursemoduleid;
        $data->userid = $this->userid;
        $data->assignmentid = $assignment->get_id();
        $data->courseid = $this->courseid;
        $data->firstname = $user->firstname;
        $data->lastname = $user->lastname;
        $data->externalgrademax = $assignment->get_externalgrademax();
        $data->manualgrademax = $assignment->get_manualgrademax();
        $data->gradeid = -1;
        $data->externalassignment = $assignment->get_id();
        $data->status = get_string('pending', 'externalassignment');

        // Time remaining.
        $timeremaining = $assignment->get_duedate() - time();
        $due = '';
        if ($timeremaining <= 0) {
            $due = get_string('assignmentisdue', 'externalassignment');
        } else {
            $due = get_string('timeremainingcolon', 'externalassignment', format_time($timeremaining));
        }
        $data->timeremainingstr = $due;

        $data->externalgrade = '';
        $data->manualgrade = '';
        $data->externallink = '';
        $data->externalfeedback['text'] = '';
        $data->externalfeedback['format'] = 1;
        $data->manualfeedback['text'] = '<p>Nothing here</p>';
        $data->manualfeedback['format'] = 1;
        $data->gradefinal = 0;

        require_once($CFG->dirroot . '/mod/externalassignment/classes/form/grader_form.php');
        $mform = new grader_form(null, $data);

        // Form processing and displaying is done here.
        if ($mform->is_cancelled()) {
            debugging('Cancelled');  // TODO MDL-1 reset the form.
        } else if ($formdata = $mform->get_data()) {
            global $DB;
            $grade = new grade($formdata);
            if ($grade->get_id() == -1) {
                $grade->set_id($DB->insert_record('externalassignment_grades', $grade->to_stdclass()));
            } else {
                $result = $DB->update_record('externalassignment_grades', $grade->to_stdclass());
            }

            $gradevalues = new \stdClass();
            $gradevalues->userid = $this->get_userid();
            $gradevalues->rawgrade = floatval($grade->get_externalgrade()) + floatval($grade->get_manualgrade());
            externalassignment_grade_item_update($this->get_assign()->to_stdclass(), $gradevalues);

            redirect(
                new \moodle_url('view.php',
                    [
                        'id' => $this->coursemoduleid,
                        'action' => 'grader',
                        'userid' => $this->userid,
                    ]
                )
            );
        } else {
            $grades = $this->read_grades();

            if (array_key_exists($this->get_userid(), $grades)) {
                $gradedata = $grades[$this->get_userid()];
                $data->gradeid = $gradedata->id;
                $data->externalassignment = $gradedata->externalassignment;
                $data->status = $this->get_status($gradedata);
                $data->externalgrade = $gradedata->externalgrade;
                $data->externalfeedback['text'] = $gradedata->externalfeedback;
                $data->externalfeedback['format'] = 1;
                $data->manualgrade = $gradedata->manualgrade;
                $data->manualfeedback['text'] = $gradedata->manualfeedback;
                $data->manualfeedback['format'] = 1;
                $data->gradefinal = $gradedata->externalgrade + $gradedata->manualgrade;

            }
            $mform->set_data($data);
            $mform->display();
        }
    }

    /**
     * process the override form
     * @param array $userids
     * @return void
     * @throws \dml_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function process_override(array $userids): void {
        global $CFG;

        $data = new \stdClass();
        $assignment = new assign(null);
        $assignment->load_db($this->coursemoduleid);
        $data->id = $this->coursemoduleid;
        $data->assignmentid = $assignment->get_id();
        $data->courseid = $this->courseid;
        $data->allowsubmissionsfromdate = $assignment->get_allowsubmissionsfromdate();
        $data->duedate = $assignment->get_duedate();
        $data->cutoffdate = $assignment->get_duedate();
        $data->users = $this->read_coursemodule_students($userids);

        // Form processing and displaying is done here.
        $url = new \moodle_url('/mod/externalassignment/view.php', ['action' => 'override']);
        require_once($CFG->dirroot . '/mod/externalassignment/classes/form/override_form.php');
        $mform = new override_form($url->out(false), $assignment, $data);
        if ($mform->is_cancelled()) {
            debugging('Cancelled');  // FIXME reset the form.
        } else if ($formdata = $mform->get_data()) {
            foreach ($formdata->uid as $userid) {
                require_once($CFG->dirroot . '/mod/externalassignment/classes/local/override.php');
                // FIXME: Find out why autoloadind does not work here.
                $override = new override();
                $override->set_externalassignment($formdata->id);
                $override->set_userid($userid);
                $override->set_allowsubmissionsfromdate($formdata->allowsubmissionsfromdate);
                $override->set_duedate($formdata->duedate);
                $override->set_cutoffdate($formdata->cutoffdate);
                $this->override_update($override);
            }
            $url = new \moodle_url(
                '/mod/externalassignment/view.php',
                [
                    'id' => $formdata->id,
                    'action' => 'grading',
                ]
            );
            redirect($url);

        } else {
            $mform->set_data($data);
            $mform->display();
        }
    }

    /**
     * inserts or updates a user override
     * @param override $override
     * @return void
     * @throws dml_exception
     */
    private function override_update(override $override): void {
        global $DB;
        if ($record = $DB->get_record(
            'externalassignment_overrides',
            [
                'externalassignment' => $override->get_externalassignment(),
                'userid' => $override->get_userid(),
            ]
        )) {
            $override->set_id($record->id);
            $DB->update_record('externalassignment_overrides', $override->to_stdclass());
        } else {
            $DB->insert_record('externalassignment_overrides', $override->to_stdclass());
        }
    }

    /**
     * get the status of the students assignment
     * @param $grade
     * @return string
     */
    private function get_status($grade): string {
        $status = get_string('pending', 'externalassignment');;
        if ($grade && $this->get_assign()->get_needspassinggrade() != 0) {
            $maximumgrade = $this->get_assign()->get_externalgrademax() + $this->get_assign()->get_manualgrademax();
            $totalgrade = $grade->externalgrade + $grade->manualgrade;
            $passinggrade = $maximumgrade * $this->get_assign()->get_passingpercentage() / 100;
            if ($totalgrade >= $passinggrade) {
                $status = get_string('done', 'externalassignment');
            }
        }
        return $status;
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
     * Gets the courseid
     * @return int
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Sets the courseid
     * @param int $courseid
     */
    public function set_courseid(int $courseid): void {
        $this->courseid = $courseid;
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
     * Gets the userlist
     * @return array
     */
    public function get_userlist(): array {
        return $this->userlist;
    }

    /**
     * Sets the userlist
     * @param array $userlist
     */
    public function set_userlist(array $userlist): void {
        $this->userlist = $userlist;
    }

    /**
     * Gets the userid
     * @return string
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

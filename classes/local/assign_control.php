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
use core\context;

/**
 * Controller for the external assignment
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_control {
    /** @var \stdClass the assignment record that contains the global settings for this assign instance */
    private \stdClass $instance;
    /** @var context the context of this cousemodule */
    private context $context;

    /** @var ?cm_info the course module for this assign instance */
    private ?cm_info $coursemodule;

    /** @var \stdClass the course this assign instance belongs to */
    private \stdClass $course;

    /** @var int the course module id */
    private int $coursemoduleid;

    /**
     * Default constructor
     * @param $coursemodulecontext
     * @param $coursemodule cm_info|null  The course module
     * @throws \coding_exception
     */
    public function __construct($coursemodulecontext, $coursemodule) {
        $this->set_context($coursemodulecontext);
        $this->set_coursemodule(cm_info::create($coursemodule));
    }

    /**
     * Add this instance to the database.
     *
     * @param \stdClass $formdata The data submitted from the form
     * @param int $coursemoduleid The course module id
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(\stdClass $formdata, int $coursemoduleid) {
        global $DB;
        $assign = new assign($formdata, $this->get_context());

        $returnid = $DB->insert_record('externalassignment', $assign->to_stdclass());
        $this->set_coursemoduleid($coursemoduleid);
        $this->set_instance($DB->get_record('externalassignment', ['id' => $returnid], '*', MUST_EXIST));
        // Cache the course record.
        $this->set_course($DB->get_record('course', ['id' => $formdata->course], '*', MUST_EXIST));
        $this->grade_item_update();
        $this->calendar_event_update();
        return $returnid;
    }

    /**
     * Update this instance in the database.
     *
     * @param \stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     * @throws \dml_exception
     */
    public function update_instance(\stdClass $formdata, int $coursemoduleid): bool {
        global $DB;
        $assign = new assign($formdata, $this->get_context());
        $this->set_coursemoduleid($coursemoduleid);
        $data = $assign->to_stdclass();
        $result = $DB->update_record('externalassignment', $data);
        $this->set_instance($DB->get_record('externalassignment', ['id' => $assign->get_id()], '*', MUST_EXIST));
        $this->set_course($DB->get_record('course', ['id' => $formdata->course], '*', MUST_EXIST));
        $this->grade_item_update();
        $this->calendar_event_update();
        return $result;
    }

    /**
     * Delete this instance from the database.
     *
     * @param int $id the id of the external assignment
     * @throws \dml_exception
     */
    public function delete_instance(int $id): void {
        global $DB;
        $this->set_coursemoduleid($id);
        $eventid = $DB->get_field('event',
            'id',
            [
                'instance' => $this->get_coursemoduleid(),
                'eventtype' => 'due',
            ]
        );
        if ($eventid != false) {
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }
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

    /**
     * Insert, Update or Delete the calendar event for this assignment
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function calendar_event_update(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $name = $this->get_instance()->name;
        $event = new \stdClass();
        $event->eventtype = 'due';
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->name = $name . ' ' . get_string('isdue', 'externalassignment', $name);
        $event->description = format_module_intro(
            'externalassignment',
            $this->get_instance(),
            $this->get_coursemoduleid(),
            false
        );
        $event->format = FORMAT_HTML;
        $event->courseid = $this->get_course()->id;
        $event->groupid = 0;
        $event->userid = 0;
        $event->modulename = 'externalassignment';
        $event->instance = $this->get_instance()->id;
        $event->timestart = $this->get_instance()->duedate;
        $event->timesort = $this->get_instance()->duedate;
        $event->visible = true;
        $event->timeduration = 0;

        $event->id = $DB->get_field(
            'event',
            'id',
            [
                'modulename' => 'externalassignment',
                'instance' => $this->get_instance()->id,
                'eventtype' => 'due',
            ]
        );

        if ($event->id) {   // Does the event already exists?
            $calendarevent = \calendar_event::load($event->id);
            if ($this->get_instance()->duedate !== null) {
                $calendarevent->update($event, false);
            } else {    // No more due date, so delete the event
                // Calendar event is no longer needed.
                $calendarevent->delete();
            }
        } else {
            \calendar_event::create($event);
        }
    }

    /**
     * Gets the instance
     * @return \stdClass
     */
    public function get_instance(): \stdClass {
        return $this->instance;
    }

    /**
     * Sets the instance
     * @param \stdClass $instance
     */
    public function set_instance(\stdClass $instance): void {
        $this->instance = $instance;
    }

    /**
     * Gets the coursemodule
     * @return cm_info|null
     */
    public function get_coursemodule(): ?cm_info {
        return $this->coursemodule;
    }

    /**
     * Sets the coursemodule
     * @param cm_info|null $coursemodule
     */
    public function set_coursemodule(?cm_info $coursemodule): void {
        $this->coursemodule = $coursemodule;
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
     * Gets the course
     * @return \stdClass
     */
    public function get_course(): \stdClass {
        return $this->course;
    }

    /**
     * Sets the course
     * @param \stdClass $course
     */
    public function set_course(\stdClass $course): void {
        $this->course = $course;
    }
}

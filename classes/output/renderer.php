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

namespace mod_externalassignment\output;

use plugin_renderer_base;

/**
 * Renders the HTML
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Defer to template for view_grading.
     *
     * @param view_grading $page the page to render
     * @return string html for the page
     * @throws \moodle_exception
     */
    public function render_view_grading(view_grading $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('externalassignment/view_grading', $data);
    }

    /**
     * Defer to template for view_grader_navigation.
     * @param view_grader_navigation $page the page to render
     * @return string html for the page
     * @throws \moodle_exception
     */
    public function render_view_grader_navigation(view_grader_navigation $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('externalassignment/view_grader_navigation', $data);
    }

    /**
     * Defer to template for view_link.
     * @param view_link $page the page to render
     * @return string html for the page
     * @throws \moodle_exception
     */
    public function render_view_link(view_link $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('externalassignment/view_link', $data);
    }

    /**
     * Defer to template for view_summary.
     * @param view_summary $page the page to render
     * @return string html for the page
     * @throws \moodle_exception
     */
    public function render_view_summary(view_summary $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('externalassignment/view_summary', $data);
    }

    /**
     * Defer to template for view_student.
     * @param view_student $page the page to render
     * @return string html for the page
     * @throws \moodle_exception
     */
    public function render_view_student(view_student $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('externalassignment/view_student', $data);
    }
}

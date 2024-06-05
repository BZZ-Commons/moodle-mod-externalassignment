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

use cm_info;

/**
 * Controller for the external assignment
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_summary_test extends \base_testcase {
    private $viewsummary;
    public function __construct() {
       // $this->viewsummary = new view_summary(1, new \context_module::instance_by_id(1));
    }
    public function test_constructor() {
        /*
        $context = new \context_module::instance_by_id(1);
        $view_summary = new view_summary(1, $context);
        $this->assertNotNull($view_summary->get_coursemoduleid());
        $this->assertNotNull($view_summary->get_context());
        */
    }

    public function export_for_template_test() {
        // $this->view_summary->export_for_template(new \renderer_base());
    }
}

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

/**
 * Javascript to handle changing users via the user selector in the header.
 *
 * @module     mod_externalassignment/grading_navigation
 * @copyright  2026 Marcel Suter and Kevin Maurizi
 * @author     2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author     2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.1
 */
import $ from 'jquery';
import Autocomplete from 'core/form-autocomplete';
import {get_string as getString} from 'core/str';
import {fetchAllStudents} from './repository';

export const init = () => {
    initStatusFilter();
    loadAllStudents()
        .then(() => {
            document.getElementById('previous-user').addEventListener('click', navigateUser);
            document.getElementById('next-user').addEventListener('click', navigateUser);
            return true;
        })
        .catch((error) => {
            window.log('Oops!' + error);
        });

};

/**
 * Sets up the status filter dropdown to reflect and update the "status" URL parameter
 */
function initStatusFilter() {
    const statusFilter = document.getElementById('status-filter');
    if (!statusFilter) {
        return;
    }
    const urlParams = new URLSearchParams(window.location.search);
    statusFilter.value = urlParams.get('status') ?? '';
    statusFilter.addEventListener('change', () => {
        const newParams = new URLSearchParams(window.location.search);
        if (statusFilter.value === '') {
            newParams.delete('status');
        } else {
            newParams.set('status', statusFilter.value);
        }
        window.location.href = '?' + newParams.toString();
    });
}

/**
 * Loads all students for the current external assignment, respecting the current sort and status filter,
 * and turns the select into the standard Moodle autocomplete
 * @returns {Promise<void>}
 */
const loadAllStudents = async() => {
    const urlParams = new URLSearchParams(window.location.search);
    const coursemoduleid = urlParams.get('id');
    const sort = urlParams.get('sort') ?? 'lastname';
    const tdir = urlParams.get('tdir') ?? 'asc';
    const status = urlParams.get('status') ?? '';
    const response = await fetchAllStudents(coursemoduleid, sort, tdir, status);
    const select = document.getElementById('change-user-select');
    const currentId = urlParams.get('userid');
    let hascurrent = false;
    for (let i = 0; i < response.length; i++) {
        hascurrent = addStudent(response[i], select, currentId) || hascurrent;
    }
    if (!hascurrent) {
        // The current student is not in the (filtered) list: do not preselect somebody else.
        select.selectedIndex = -1;
    }
    const placeholder = await getString('changeuser', 'mod_externalassignment');
    await Autocomplete.enhance('#change-user-select', false, false, placeholder, false, true, placeholder, true);
    $(select).on('change', () => gotoUser(select.value));
};

/**
 * Adds a student to the select
 * @param {object} student the student
 * @param {HTMLSelectElement} select
 * @param {string|null} currentStudentId
 * @returns {boolean} true if the student is the current one
 */
function addStudent(student, select, currentStudentId) {
    const option = document.createElement('option');
    option.text = student.firstname + ' ' + student.lastname + ' (' + student.email + ')';
    option.value = student.userid;
    const iscurrent = student.userid == currentStudentId;
    option.selected = iscurrent;
    select.add(option);
    return iscurrent;
}

/**
 * Shows the grader form of a student
 * @param {string|number} userid
 */
function gotoUser(userid) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('userid', userid);
    window.location.href = '?' + urlParams.toString();
}

/**
 * Navigates to the next or previous user
 * @param {Object} event
 */
function navigateUser(event) {
    event.preventDefault();
    const options = Array.from(document.getElementById('change-user-select').options);
    if (options.length === 0) {
        return;
    }
    const currentId = new URLSearchParams(window.location.search).get('userid');
    let currentItem = options.findIndex((option) => option.value === currentId);
    const previous = event.currentTarget.id === 'previous-user';
    if (currentItem === -1) {
        // The current student is not in the (filtered) list: start at its end or beginning.
        currentItem = previous ? options.length : -1;
    }
    if (previous) {
        currentItem = currentItem <= 0 ? options.length - 1 : currentItem - 1;
    } else {
        currentItem = currentItem >= options.length - 1 ? 0 : currentItem + 1;
    }
    gotoUser(options[currentItem].value);
}

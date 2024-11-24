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
 * Javascript to handle sorting actions for grading.
 *
 * @module     mod_externalassignment/grading_sort
 * @copyright  2024 Marcel Suter <marcel@ghwalin.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.3
 */

/**
 * init function
 */
export const init = () => {
    addLinks();
};

/**
 * Add links to the table headers
 */
function addLinks() {
    let sortField = getSortField();
    let sortOrder = getSortOrder();
    let url = getUrl();
    const sortParams = {
        'sortLastname': 'lastname',
        'sortFirstname': 'firstname',
        'sortStatus': 'status',
        'sortGrade': 'grade'
    };

    const sortLinks = document.getElementsByTagName('a');
    for (let i = 0; i < sortLinks.length; i++) {
        let element = sortLinks[i];
        // Get the sortFields object who's key matches element.id
        let sortParam = sortParams[element.id];
        if (sortParam) {
            element.href = url + '&sort=' + sortParam + '&tdir=asc';
            // If currently sorted by this field in ascending order, then add descending order
            if (sortParam === sortField) {
                if (sortOrder === 'asc') {
                    element.href += '&tdir=desc';
                    element.innerHTML += ' <i class="icon fa fa-sort-desc fa-fw" title="Descending" ' +
                        'role="img" aria-label="Descending"></i>';

                } else {
                    element.innerHTML += ' <i class="icon fa fa-sort-asc fa-fw" title="Ascending" ' +
                        'role="img" aria-label="Ascending"></i>';
                }
                /*
                <i class="icon fa fa-sort-asc fa-fw " title="Ascending" role="img" aria-label="Ascending"></i>
                 */
            }
        }
    }
}

/**
 * Get the sort field from the URL
 * @returns {string} The sort order
 */
function getSortField() {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    let sortOrder = urlParams.get('sort');
    if (sortOrder === null) {
        sortOrder = 'lastname';
    }
    return sortOrder;
}

/**
 * Get the sort order from the URL
 */
function getSortOrder() {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    let tdir = urlParams.get('tdir');
    if (!tdir) {
        tdir = 'asc';
    }
    return tdir;
}

/**
 * Get the URL without sort parameter
 * @returns {string} The URL
 */
function getUrl() {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    urlParams.delete('sort');
    urlParams.delete('tdir');
    return window.location.pathname + '?' + urlParams.toString();
}
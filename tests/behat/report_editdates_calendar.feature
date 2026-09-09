@mod @mod_externalassignment @javascript
Feature: Editing dates through the "Dates" report must update the calendar
  In order to trust the "Dates" report as a shortcut for editing an activity's settings
  As a teacher
  I need changing a due date there to be reflected in the calendar exactly like editing it
  through the activity's own settings form would

  This is the Behat-level regression test for GitHub issue #38 ("Calendar entry with dates
  plugin": "When changing the dates/times using the dates plugin, the calendar entries aren't
  updated"). report_editdates_integration::save_dates() used to write the new dates straight to
  the externalassignment table, bypassing assign_control::update_instance() entirely - so the
  shared "due" calendar event kept showing the *old* due date, and a student's Dashboard
  "Timeline" kept marking the assignment overdue even after the due date had been moved into
  the future.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                    | externalname       | externallink                        | duedate        |
      | externalassignment | C1     | Dates report assignment | m999-editdatescal   | https://www.example.com/assignment  | ##yesterday##  |

  Scenario: Moving a due date into the future via the Dates report clears the calendar's overdue flag
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Dates" "link"
    And I click on "Expand all" "link" in the "region-main" "region"
    When I set the field "Due date" to "##tomorrow##"
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I click on "Filter timeline by date" "button" in the "Timeline" "block"
    And I click on "Overdue" "link" in the "Timeline" "block"
    Then I should not see "Dates report assignment" in the "Timeline" "block"

@mod @mod_externalassignment @javascript
Feature: Granting a student a due-date extension must not still show them as overdue
  In order for extensions to be meaningful
  As a student
  I need my personal Dashboard to reflect the due date I was actually granted, not the
  assignment's original due date

  This is the Behat-level regression test for GitHub issue #37 ("Status: overdue despite
  overwrite": a student with an extension (override) for the due/cutoff dates still saw the
  assignment marked overdue once the *original* due date passed, even though the teacher's
  grading table correctly showed the extension). The root cause was that granting an override
  never touched the calendar: Moodle's Dashboard "Timeline" block computes its own "Overdue"
  filter purely from each activity's calendar event due date, and mod_externalassignment only
  ever created one shared event using the assignment's own due date, regardless of any override.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
      | student2 | Student   | 2        | student2@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                          | externalname        | externallink                        | duedate        |
      | externalassignment | C1     | Extension calendar assignment | m999-extensioncal    | https://www.example.com/assignment  | ##yesterday##  |

  Scenario: A student granted an extension no longer sees the assignment as overdue, unlike a student without one
    Given I am logged in as "teacher1"
    And I am on the "Extension calendar assignment" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "//input[@name='selectbox']" "xpath_element" in the "Student 1" "table_row"
    And I click on "Go" "button"
    And I set the field "Due date" to "##tomorrow##"
    And I press "Save changes"
    Then I should see "Extension granted" in the "Student 1" "table_row"
    And I log out

    When I log in as "student1"
    And I click on "Filter timeline by date" "button" in the "Timeline" "block"
    And I click on "Overdue" "link" in the "Timeline" "block"
    Then I should not see "Extension calendar assignment" in the "Timeline" "block"
    And I log out

    When I log in as "student2"
    And I click on "Filter timeline by date" "button" in the "Timeline" "block"
    And I click on "Overdue" "link" in the "Timeline" "block"
    Then I should see "Extension calendar assignment" in the "Timeline" "block"

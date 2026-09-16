@mod @mod_externalassignment @javascript
Feature: Edit an external assignment to add due/cut off dates and a passing grade
  In order to manage assignments effectively
  As a teacher
  I need to be able to edit an existing external assignment to add specific dates and a
  passing grade

  This covers acceptance_tests.md section 3 ("Edit existing external assignment"), row a.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student10@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                                | externalname          | externallink                        | completion |
      | externalassignment | C1     | Assignment without dates or grading | m999-editcutoffpassing | https://www.example.com/assignment | 0          |

  Scenario: Editing an external assignment to add due/cut off dates and a passing grade
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I am on the "Assignment without dates or grading" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Due date            | ##last day of this month noon## |
      | Cut-off date        | ##last day of this month 11pm## |
      | Add requirements    | 1                                |
      | needspassinggrade   | 1                                |
    And I press "Save and return to course"
    And I click on "Completion" "button"
    Then I should see "Assignment without dates or grading"
    And I should see "Due:"
    And I should see "##last day of this month noon##%A, %d %B %Y, %I:%M##"
    And I should not see "Mark as done"
    And I should see "Completion"
    And I should see "Receive a passing grade"

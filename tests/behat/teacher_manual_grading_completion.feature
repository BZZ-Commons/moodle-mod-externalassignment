@mod @mod_externalassignment
Feature: Manual grading must update the assignment's completion status
  In order to trust the completion tracking of an external assignment
  As a teacher
  I need entering a manual grade to immediately update whether a student has met the
  "needs passing grade" completion condition

  This covers acceptance_tests.md section 4 ("Manual grading"), and is the Behat-level regression
  test for GitHub issue #26 ("Manual grade": "After changing the manual grade, the completion
  isn't updated").

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                | externalname        | externallink                        | externalgrademax | manualgrademax | passingpercentage | needspassinggrade | completion |
      | externalassignment | C1     | Manual grading test | m999-manualgrading   | https://www.example.com/assignment  | 100               | 20              | 60                 | 1                  | 2          |

  @javascript
  Scenario: A manual grade that reaches the passing threshold marks the activity complete
    Given I am logged in as "teacher1"
    And I am on the "Manual grading test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link"
    And I set the field "Grading (max. 20)" to "15"
    And I press "Save changes"
    Then the "Receive a passing grade" completion condition of "Manual grading test" is displayed as "done" for "student1" in the "Course 1" "course"

  @javascript
  Scenario: A manual grade that stays below the passing threshold leaves the activity incomplete
    Given I am logged in as "teacher1"
    And I am on the "Manual grading test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link"
    And I set the field "Grading (max. 20)" to "1"
    And I press "Save changes"
    Then the "Receive a passing grade" completion condition of "Manual grading test" is displayed as "todo" for "student1" in the "Course 1" "course"

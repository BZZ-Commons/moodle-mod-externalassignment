@mod @mod_assign
Feature: Check that the externalassignment grade can be updated correctly
  In order to ensure that the grade is shown correctly in the grading table
  As a teacher
  I need to grade a student and ensure the grade is shown correctly

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student10@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activity" exists:
      | activity | externalassignment          |
      | course   | C1                          |
      | name     | Test assignment name        |
      | intro    | Test assignment description |

  @javascript
  Scenario: Update the grade for an assignment
    When I am on the "Test assignment name" Activity page logged in as teacher1
    Then I follow "Show all"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I wait "5" seconds
    And I set the field "Grading (max. 100)" to "50"
    And I set the field "Grading (max. 10)" to "7"
    And I press "Save changes"
    And I follow "Show all"
    And "Student 1" row "Grade" column of "generaltable" table should contain "57.00"

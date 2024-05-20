@mod @mod_externalassignment
Feature: Check that the assignment grade can not be input in a wrong format.
  In order to ensure that the grade is entered in the right format
  As a teacher
  I need to grade a student and ensure that the grade should be correctly entered

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
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
    And the following "activity" exists:
      | activity | externalassignment          |
      | course   | C1                          |
      | name     | Test assignment name        |
      | intro    | Test assignment description |

  @javascript
  Scenario: Error in the decimal separator , for external grading
    When I am on the "Test assignment name" Activity page logged in as teacher1
    And I follow "Show all"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 100)" to "50,,6"
    And I press "Save changes"
    Then I should see "You must enter a number here"

  @javascript
  Scenario: Error in the decimal separator . for external grading
    When I am on the "Test assignment name" Activity page logged in as teacher1
    And I follow "Show all"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 100)" to "50..6"
    And I press "Save changes"
    Then I should see "You must enter a number here"

  @javascript
  Scenario: Error in the decimal separator , for manual grading
    When I am on the "Test assignment name" Activity page logged in as teacher1
    And I follow "Show all"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 10)" to "43,,2"
    And I press "Save changes"
    Then I should see "- Mandatory"

  @javascript
  Scenario: Error in the decimal separator . for manual grading
    When I am on the "Test assignment name" Activity page logged in as teacher1
    And I follow "Show all"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 10)" to "4..3"
    And I press "Save changes"
    Then I should see "- Mandatory"
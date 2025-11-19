@mod @mod_externalassignment @javascript
Feature: Create external assignment with dates and passing grade
  In order to manage assignments effectively
  As a teacher
  I need to be able to create external assignments with specific dates and passing grades

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

    Scenario: Create an external assignment with dates and passing grade
      Given I am logged in as "teacher1"
      And I am on the "Course 1" course page
      When I turn editing mode on
      And I add the "Assignment" activity to the "General" section with:
        | Assignment name | Assignment with Dates and Passing Grade |
        | External assignment | m999-extassignmentsample |
        | Assignment link     | https://www.example.com/assignment |
        | Description | This is a test assignment. |
#      And I set the field "Name" to ""
#      And I set the field "Intro" to "This is a test assignment with specific dates and passing grade."
#      And I set the field "Allow submissions from" to "2024-07-01 08:00:00"
#      And I set the field "Due date" to "2034-07-10 23:59:59"
#      And I set the field "Cut-off date" to "2034-07-15 23:59:59"
#      And I set the field "Passing grade" to "60"
#      And I select "Add requirements" from the "Activity completion" dropdown
#      And I check the "Students needs a passing grade to complete this assignment" checkbox
      And I press "Save and return to course"
      Then I should see "Assignment with Dates and Passing Grade" on the course page


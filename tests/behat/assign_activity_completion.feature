@mod @mod_externalassignment @core_completion
Feature: View activity completion in the assignment activity
  In order to have visibility of assignment completion requirements
  As a student
  I need to be able to view my assignment completion progress

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Vinnie    | Student1 | student1@example.com |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname        | shortname | enablecompletion | showcompletionconditions |
      | With completion | C1        | 1                | 1                        |
      | No completion   | C2        | 1                | 0                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | student1 | C2     | student        |
      | teacher1 | C2     | editingteacher |
    And the following "activities" exist:
      | activity           | course | name                        | intro        | completion |
      | externalassignment | C1     | Manual completion           | Do something | 1          |
      | externalassignment | C1     | Completion disabled         | Do something | 0          |
      | externalassignment | C1     | Automatic completion        | Do something | 2          |
      | externalassignment | C2     | Manual completion not shown | Do something | 1          |

  @javascript
  Scenario: The manual completion button will be shown on the course page if the Show activity completion conditions is set to Yes
    Given I am on the "With completion" course page logged in as teacher1
    # Teacher view.
    And "Manual completion" should have the "Mark as done" completion condition
    And I log out
    # Student view.
    When I log in as "student1"
    And I am on "With completion" course homepage
    Then the manual completion button for "Manual completion" should exist
    And the manual completion button of "Manual completion" is displayed as "Mark as done"
    And I toggle the manual completion state of "Manual completion"
    And the manual completion button of "Manual completion" is displayed as "Done"

  @javascript
  Scenario: The manual completion button will not be shown on the course page if the Show activity completion conditions is set to No
    Given I am on the "No completion" course page logged in as teacher1
    # Teacher view.
    And "Completion" "button" should not exist in the "Manual completion not shown" "activity"
    And I log out
    # Student view.
    When I log in as "student1"
    And I am on "With completion" course homepage
    Then the manual completion button for "Manual completion not shown" should not exist
    And I am on the "Manual completion not shown" "externalassignment activity" page
    And the manual completion button for "Manual completion not shown" should exist

  @javascript
  Scenario: Use manual completion from the activity page
    Given I am on the "Manual completion" "externalassignment activity" page logged in as teacher1
    # Teacher view.
    And the manual completion button for "Manual completion" should be disabled
    And I log out
    # Student view.
    And I am on the "Manual completion" "externalassignment activity" page logged in as student1
    Then the manual completion button of "Manual completion" is displayed as "Mark as done"
    And I toggle the manual completion state of "Manual completion"
    And the manual completion button of "Manual completion" is displayed as "Done"

  @javascript
  Scenario: View automatic completion as a teacher
    Given I am on the "Automatic completion" "externalassignment activity" page logged in as teacher1
    Then "Automatic completion" should have the "Receive a passing grade" completion condition

  @javascript
  Scenario: View pending automatic completion item as a student
    Given I am on the "Automatic completion" "externalassignment activity" page logged in as student1
    And the "Receive a passing grade" completion condition of "Automatic completion" is displayed as "todo"
    And I log out
    And I am on the "Automatic completion" "externalassignment activity" page logged in as teacher1
    And I follow "Show all"
    And I click on "Grade" "link" in the "Vinnie Student1" "table_row"
    And I wait "10" seconds
    And I set the field "Grading (max. 100)" to "30"
    And I set the field "Grading (max. 10)" to "5"
    And I press "Save changes"
    And I log out
    And I am on the "Automatic completion" "externalassignment activity" page logged in as student1
    Then the "Receive a passing grade" completion condition of "Automatic completion" is displayed as "todo"


  @javascript
  Scenario: View passed automatic completion items as a student
    Given I am on the "Automatic completion" "externalassignment activity" page logged in as teacher1
    And I follow "Show all"
    And I click on "Grade" "link" in the "Vinnie Student1" "table_row"
    And I wait "5" seconds
    And I set the field "Grading (max. 100)" to "70"
    And I set the field "Grading (max. 10)" to "10"
    And I press "Save changes"
    And I log out
    And I am on the "Automatic completion" "externalassignment activity" page logged in as student1
    Then the "Receive a passing grade" completion condition of "Automatic completion" is displayed as "done"
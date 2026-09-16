@mod @mod_externalassignment @javascript
Feature: Creating an external assignment must reject invalid settings
  In order to keep external assignments configured consistently
  As a teacher
  I need to be stopped from saving an external assignment whose dates are ordered
  inconsistently, or whose required fields are missing or not numeric

  This covers acceptance_tests.md section 2 ("Validation errors while creating").

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: The allow-submissions-from date must be before the due date
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name        | Open date after due date  |
      | External assignment    | m999-openafterduedate     |
      | Assignment link        | https://www.example.com/a |
      | Allow submissions from | ##tomorrow noon##         |
      | Due date               | ##tomorrow noon##         |
    And I press "Save and display"
    Then I should see "Due date must be after the allow submissions from date."

  Scenario: The cut-off date must not be earlier than the due date
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name     | Cut-off date before due date |
      | External assignment | m999-cutoffbeforedue         |
      | Assignment link      | https://www.example.com/b   |
      | Due date             | ##tomorrow noon##           |
      | Cut-off date         | ##tomorrow 11am##           |
    And I press "Save and display"
    Then I should see "Cut-off date cannot be earlier than the due date."

  Scenario: The assignment link is required
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name     | Missing assignment link |
      | External assignment | m999-missinglink        |
    And I press "Save and display"
    Then I should see "You must supply a value here."

  Scenario: The assignment name is required
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | External assignment | m999-missingname          |
      | Assignment link     | https://www.example.com/c |
    And I press "Save and display"
    Then I should see "You must supply a value here."

  Scenario: The external grade max is required
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name      | Missing external grade max |
      | External assignment  | m999-missinggrademax       |
      | Assignment link      | https://www.example.com/d  |
      | External grade max.  |                             |
    And I press "Save and display"
    Then I should see "You must supply a value here."

  Scenario: The external grade max must be numeric
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name      | Non-numeric external grade max |
      | External assignment  | m999-nonnumericgrademax        |
      | Assignment link      | https://www.example.com/e      |
      | External grade max.  | abc                             |
    And I press "Save and display"
    Then I should see "You must enter a number here."

  Scenario: The manual grade max must be numeric
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name      | Non-numeric manual grade max |
      | External assignment  | m999-nonnumericmanualgrademax |
      | Assignment link      | https://www.example.com/f    |
      | Manual grade max.    | abc                            |
    And I press "Save and display"
    Then I should see "You must enter a number here."

  Scenario: The assignment link must be a valid URL
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name     | Invalid assignment link |
      | External assignment | m999-invalidlink        |
      | Assignment link     | not-a-url                |
    And I press "Save and display"
    Then I should see "Assignment link must be a valid URL"

  Scenario: The external grade max cannot be negative
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name      | Negative external grade max |
      | External assignment  | m999-negativegrademax       |
      | Assignment link      | https://www.example.com/g   |
      | External grade max.  | -1                            |
    And I press "Save and display"
    Then I should see "External grade max. cannot be negative."

  Scenario: The manual grade max cannot be negative
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name      | Negative manual grade max |
      | External assignment  | m999-negativemanualgrademax |
      | Assignment link      | https://www.example.com/h |
      | Manual grade max.    | -1                          |
    And I press "Save and display"
    Then I should see "Manual grade max. cannot be negative."

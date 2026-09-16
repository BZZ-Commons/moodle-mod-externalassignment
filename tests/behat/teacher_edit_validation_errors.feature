@mod @mod_externalassignment @javascript
Feature: Editing an external assignment must reject invalid settings
  In order to keep external assignments configured consistently
  As a teacher
  I need to be stopped from saving changes that reorder the dates inconsistently, or that
  clear a required field, or that make a grade max non-numeric

  This covers acceptance_tests.md section 4 ("Validation errors while editing"). It exercises
  the same mod_externalassignment_mod_form::validation() rules as
  teacher_create_validation_errors.feature, but on the edit form of an already-existing
  assignment instead of on creation.

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
    And the following "activities" exist:
      | activity           | course | name                 | externalname       | externallink                        | duedate            |
      | externalassignment | C1     | Editable assignment  | m999-editvalidation | https://www.example.com/assignment  | ##tomorrow noon##  |
    And I am logged in as "teacher1"

  Scenario: The allow-submissions-from date must be before the due date
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Allow submissions from | ##tomorrow noon## |
      | Due date               | ##tomorrow noon## |
    And I press "Save and display"
    Then I should see "Due date must be after the allow submissions from date."

  Scenario: The cut-off date must not be earlier than the due date
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Cut-off date | ##tomorrow 11am## |
    And I press "Save and display"
    Then I should see "Cut-off date cannot be earlier than the due date."

  Scenario: The assignment link is required
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Assignment link |  |
    And I press "Save and display"
    Then I should see "You must supply a value here."

  Scenario: The assignment name is required
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Assignment name |  |
    And I press "Save and display"
    Then I should see "You must supply a value here."

  Scenario: The external grade max is required
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | External grade max. |  |
    And I press "Save and display"
    Then I should see "You must supply a value here."

  Scenario: The external grade max must be numeric
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | External grade max. | abc |
    And I press "Save and display"
    Then I should see "You must enter a number here."

  Scenario: The manual grade max must be numeric
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Manual grade max. | abc |
    And I press "Save and display"
    Then I should see "You must enter a number here."

  Scenario: The assignment link must be a valid URL
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Assignment link | not-a-url |
    And I press "Save and display"
    Then I should see "Assignment link must be a valid URL"

  Scenario: The external grade max cannot be negative
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | External grade max. | -1 |
    And I press "Save and display"
    Then I should see "External grade max. cannot be negative."

  Scenario: The manual grade max cannot be negative
    Given I am on the "Editable assignment" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Manual grade max. | -1 |
    And I press "Save and display"
    Then I should see "Manual grade max. cannot be negative."

@mod @mod_externalassignment
Feature: The "needs passing grade" completion rule must never silently disappear
  In order to trust the completion tracking of an external assignment
  As a teacher
  I need the "needs passing grade" rule to stay consistent, both when I first create an
  assignment with automatic completion and when I later edit its other settings

  This covers acceptance_tests.md section 3 ("Edit existing external assignment"), and is the
  Behat-level regression test for GitHub issues #12 ("Illegal completion conditions": it must not
  be possible to have automatic completion enabled with no completion rule selected) and #36
  ("Losing the 'Needs passing grade'": editing an assignment's settings used to clear the
  requirement even though students had already started completing it).

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Enabling automatic completion at creation time enables "needs passing grade" too
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name     | Automatic completion assignment    |
      | External assignment | m999-autocompletion                |
      | Assignment link     | https://www.example.com/assignment |
      | Add requirements    | 1                                   |
    When I am on the "Automatic completion assignment" "externalassignment activity editing" page
    Then the field "needspassinggrade" matches value "1"

  @javascript
  Scenario: Editing an unrelated setting keeps the "needs passing grade" rule enabled
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name     | Passing grade assignment           |
      | External assignment | m999-passinggrade                  |
      | Assignment link     | https://www.example.com/assignment |
      | Due date             | ##tomorrow noon##                 |
      | Add requirements    | 1                                   |
      | needspassinggrade   | 1                                   |
    # Re-open the settings and change something that has nothing to do with completion.
    When I am on the "Passing grade assignment" "externalassignment activity editing" page
    And I set the field "Due date[day]" to "1"
    And I press "Save and display"
    # The completion rule must have survived the save.
    And I am on the "Passing grade assignment" "externalassignment activity editing" page
    Then the field "needspassinggrade" matches value "1"

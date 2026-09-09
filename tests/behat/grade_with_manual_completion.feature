@mod @mod_externalassignment @javascript
Feature: Grading an external assignment must not error when completion is tracked manually
  In order to grade students regardless of how completion is tracked
  As a teacher
  I need saving a grade to work even when a student has manually marked the activity complete
  themselves

  This is the Behat-level regression test for GitHub issue #39 ("Update grades with manual
  completion": with completion set to manual and a student having clicked the manual completion
  button, updating the student's grade made Moodle throw "[debuginfo: Unexpected manual
  completion state for <cmid>: -1]"). The cause was that saving a grade unconditionally asked
  completion_info to recompute the completion state, which is only a valid operation for
  *automatic* completion tracking - manual tracking only ever accepts an explicit
  complete/incomplete value and is driven entirely by the student's own toggle.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                   | externalname     | externallink                        | externalgrademax | manualgrademax | completion |
      | externalassignment | C1     | Manual completion test | m999-manualcompl | https://www.example.com/assignment  | 100               | 20              | 1          |

  Scenario: Saving a grade for a manually-completed external assignment succeeds
    Given I am on the "Manual completion test" "externalassignment activity" page logged in as student1
    And I toggle the manual completion state of "Manual completion test"
    And the manual completion button of "Manual completion test" is displayed as "Done"
    And I log out
    And I am on the "Manual completion test" "externalassignment activity" page logged in as teacher1
    And I click on "Show all" "link"
    And I click on "Grade" "link"
    When I set the field "Grading (max. 20)" to "15"
    And I press "Save changes"
    Then I should not see "Unexpected manual completion state"
    And I should not see "An internal error occurred in the completion system"
    And I am on the "Manual completion test" "externalassignment activity" page logged in as student1
    Then the manual completion button of "Manual completion test" is displayed as "Done"

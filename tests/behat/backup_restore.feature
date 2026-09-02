@mod @mod_externalassignment
Feature: External assignments must survive a course backup and restore
  In order to reuse or migrate a course
  As a teacher
  I need an external assignment's settings to come back correctly after backing up and
  restoring the course

  This is the Behat-level regression test for GitHub issue #14 ("Backup/Restore API not fully
  implemented": duplicating a course, or backing up and restoring it into a new course, produced
  an error for a course containing an external assignment).

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
    And the following "activities" exist:
      | activity           | course | name                    | externalname     | externallink                       | duedate            |
      | externalassignment | C1     | Backup test assignment  | m999-backuptest   | https://www.example.com/assignment | ##tomorrow noon##  |

  @javascript
  Scenario: An external assignment survives a course backup and restore into a new course
    Given I am logged in as "teacher1"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    When I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I am on "Course 2" course homepage with editing mode on
    Then I should see "Backup test assignment"
    When I follow "Backup test assignment"
    Then I should see "https://www.example.com/assignment"

  @javascript
  Scenario: Duplicating an activity that contains an external assignment does not error
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I duplicate "Backup test assignment" activity
    Then I should see "Backup test assignment (copy)"

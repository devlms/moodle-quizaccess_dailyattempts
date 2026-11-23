@mod @mod_quiz @quizaccess @quizaccess_dailyattempts
Feature: Limit quiz attempts per 24 hours
  In order to have students spread their practice out
  As a teacher
  I need to limit how many attempts a student can start in any 24 hours

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | The sky is blue |
    And the following "activities" exist:
      | activity | name     | course |
      | quiz     | Practice | C1     |
    And quiz "Practice" contains the following questions:
      | question | page |
      | TF1      | 1    |

  Scenario: A teacher sets the limit and a student who used it must wait
    When I am on the "Practice" "quiz activity editing" page logged in as teacher1
    And I set the field "Attempts per 24 hours" to "1"
    And I press "Save and display"
    Then I should see "Attempts you can start in any 24 hours: 1"
    And user "student1" has attempted "Practice" with responses:
      | slot | response |
      | 1    | True     |
    And I am on the "Practice" "quiz activity" page logged in as student1
    And I should see "You have used all your attempts for the last 24 hours (1 per 24 hours)"
    And "Re-attempt quiz" "button" should not exist

  Scenario: Under the limit the student can attempt again
    Given I am on the "Practice" "quiz activity editing" page logged in as teacher1
    And I set the field "Attempts per 24 hours" to "2"
    And I press "Save and display"
    And user "student1" has attempted "Practice" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Practice" "quiz activity" page logged in as student1
    Then "Re-attempt quiz" "button" should exist
    And I should not see "You have used all your attempts"

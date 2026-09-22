@local @local_wproofreader
Feature: Proofreading is available by area and by role
  In order to decide where proofreading helps and where it gets in the way
  As an administrator
  I need the availability matrix to gate WProofreader per area and per role

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name       | course | idnumber |
      | forum    | Test forum | C1     | forum1   |
      | quiz     | Test quiz  | C1     | quiz1    |

  Scenario: A new site starts with quiz attempts left alone
    Given I am on the "Test quiz" "quiz activity" page logged in as "teacher1"
    Then WProofreader should not be active
    When I am on "Course 1" course homepage
    Then WProofreader should be active

  Scenario: An empty matrix leaves proofreading on everywhere it can attach
    Given the WProofreader availability matrix is cleared
    And I am on the "Test quiz" "quiz activity" page logged in as "student1"
    Then WProofreader should be active
    When I am on the "Test forum" "forum activity" page
    Then WProofreader should be active

  Scenario: Students write quiz answers unaided while teachers keep proofreading
    Given the WProofreader availability matrix is cleared
    And WProofreader is withheld from the "student" role in the "quiz" area
    And I am on the "Test quiz" "quiz activity" page logged in as "student1"
    Then WProofreader should not be active
    When I am on the "Test forum" "forum activity" page
    Then WProofreader should be active
    And I log out
    And I am on the "Test quiz" "quiz activity" page logged in as "teacher1"
    Then WProofreader should be active

  Scenario: Withholding a role in one area leaves the other areas alone
    Given the WProofreader availability matrix is cleared
    And WProofreader is withheld from the "student" role in the "courses" area
    And I am on the "Test forum" "forum activity" page logged in as "student1"
    Then WProofreader should not be active
    When I am on the "Test quiz" "quiz activity" page
    Then WProofreader should be active

  Scenario: A course role only counts inside that course
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C2     | editingteacher |
    And the WProofreader availability matrix is cleared
    And WProofreader is withheld from the "student" role in the "courses" area
    And I am on the "Course 1" "course" page logged in as "student1"
    Then WProofreader should not be active
    When I am on "Course 2" course homepage
    Then WProofreader should be active

  Scenario: Withholding the authenticated user role reaches everyone but admins
    Given the WProofreader availability matrix is cleared
    And WProofreader is withheld from the "user" role in the "courses" area
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then WProofreader should not be active
    And I log out
    And I am on the "Course 1" "course" page logged in as "student1"
    Then WProofreader should not be active
    And I log out
    And I am on the "Course 1" "course" page logged in as "admin"
    Then WProofreader should be active

  Scenario: Switching a whole area off reaches admins too
    Given the WProofreader availability matrix is cleared
    And the WProofreader "courses" area is switched off
    And I am on the "Course 1" "course" page logged in as "admin"
    Then WProofreader should not be active
    When I am on the "Test quiz" "quiz activity" page
    Then WProofreader should be active

  Scenario: Prohibiting the capability withholds proofreading from a role
    Given the WProofreader availability matrix is cleared
    And the following "permission overrides" exist:
      | capability                 | permission | role    | contextlevel | reference |
      | local/wproofreader:use     | Prohibit   | student | Course       | C1        |
    And I am on the "Course 1" "course" page logged in as "student1"
    Then WProofreader should not be active
    And I log out
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then WProofreader should be active

<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace quizaccess_dailyattempts;

use mod_quiz\access_manager;
use mod_quiz\quiz_settings;
use quizaccess_dailyattempts;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/dailyattempts/rule.php');

/**
 * Tests for the attempts per day rule.
 *
 * @package    quizaccess_dailyattempts
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_dailyattempts
 */
final class rule_test extends \advanced_testcase {
    /** @var int a fixed "now" for the tests */
    private const NOW = 1795000000;

    /**
     * A quiz with the given settings.
     *
     * @param array $settings
     * @return \stdClass quiz record
     */
    private function quiz(array $settings = []): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->create_module('quiz', $settings + [
            'course' => $course->id,
            'dailyattempts_maxattempts' => 2,
        ]);
    }

    /**
     * Record an attempt started at a given time.
     *
     * @param \stdClass $quiz
     * @param int $userid
     * @param int $timestart
     * @param int $preview
     * @return \stdClass the attempt
     */
    private function attempt(\stdClass $quiz, int $userid, int $timestart, int $preview = 0): \stdClass {
        global $DB;
        static $unique = 1000;
        $attempt = (object)[
            'quiz' => $quiz->id, 'userid' => $userid, 'attempt' => $unique, 'uniqueid' => $unique++,
            'layout' => '1,0', 'currentpage' => 0, 'preview' => $preview, 'state' => 'finished',
            'timestart' => $timestart, 'timefinish' => $timestart + 600, 'timemodified' => $timestart + 600,
            'timemodifiedoffline' => 0, 'sumgrades' => 1,
        ];
        $attempt->id = $DB->insert_record('quiz_attempts', $attempt);
        return $attempt;
    }

    /**
     * The rule for a quiz, as Moodle builds it.
     *
     * @param \stdClass $quiz
     * @param int $now
     * @return quizaccess_dailyattempts|null
     */
    private function rule(\stdClass $quiz, int $now = self::NOW): ?quizaccess_dailyattempts {
        return quizaccess_dailyattempts::make(quiz_settings::create($quiz->id), $now, false);
    }

    public function test_setting_is_saved_and_loaded(): void {
        global $DB;
        $this->resetAfterTest();
        $quiz = $this->quiz(['dailyattempts_maxattempts' => 3]);
        $this->assertEquals(3, access_manager::load_settings($quiz->id)['dailyattempts_maxattempts']);

        $quiz->dailyattempts_maxattempts = 0;
        quizaccess_dailyattempts::save_settings($quiz);
        $this->assertFalse($DB->record_exists('quizaccess_dailyattempts', ['quizid' => $quiz->id]));
    }

    public function test_no_rule_without_a_limit(): void {
        $this->resetAfterTest();
        $this->assertNull($this->rule($this->quiz(['dailyattempts_maxattempts' => 0])));
        $this->assertNotNull($this->rule($this->quiz()));
    }

    public function test_description(): void {
        $this->resetAfterTest();
        $this->assertSame('Attempts you can start in any 24 hours: 2', $this->rule($this->quiz())->description());
    }

    public function test_under_the_limit_a_new_attempt_is_allowed(): void {
        $this->resetAfterTest();
        $quiz = $this->quiz();
        $user = $this->getDataGenerator()->create_user();
        $last = $this->attempt($quiz, $user->id, self::NOW - 3600);
        $this->assertFalse($this->rule($quiz)->prevent_new_attempt(1, $last));
        $this->assertFalse($this->rule($quiz)->prevent_new_attempt(0, null));
    }

    public function test_at_the_limit_the_student_waits_for_the_oldest_attempt_to_leave_the_window(): void {
        $this->resetAfterTest();
        $quiz = $this->quiz();
        $user = $this->getDataGenerator()->create_user();
        $this->attempt($quiz, $user->id, self::NOW - 5 * DAYSECS);
        $this->attempt($quiz, $user->id, self::NOW - 20 * HOURSECS);
        $last = $this->attempt($quiz, $user->id, self::NOW - HOURSECS);

        $message = $this->rule($quiz)->prevent_new_attempt(3, $last);
        $this->assertIsString($message);
        $this->assertStringContainsString(userdate(self::NOW + 4 * HOURSECS), $message);
        $this->assertFalse($this->rule($quiz, self::NOW + 4 * HOURSECS + 1)->prevent_new_attempt(3, $last));
    }

    public function test_previews_other_quizzes_and_other_students_do_not_count(): void {
        $this->resetAfterTest();
        $quiz = $this->quiz();
        $other = $this->quiz();
        $user = $this->getDataGenerator()->create_user();
        $classmate = $this->getDataGenerator()->create_user();
        $this->attempt($quiz, $user->id, self::NOW - 100, 1);
        $this->attempt($other, $user->id, self::NOW - 100);
        $this->attempt($quiz, $classmate->id, self::NOW - 100);
        $last = $this->attempt($quiz, $user->id, self::NOW - 50);
        $this->assertFalse($this->rule($quiz)->prevent_new_attempt(1, $last));
    }

    public function test_quiz_closing_before_the_next_window(): void {
        $this->resetAfterTest();
        $quiz = $this->quiz(['timeclose' => self::NOW + HOURSECS]);
        $user = $this->getDataGenerator()->create_user();
        $this->attempt($quiz, $user->id, self::NOW - 2 * HOURSECS);
        $last = $this->attempt($quiz, $user->id, self::NOW - HOURSECS);
        $rule = $this->rule($quiz);
        $this->assertSame(get_string('closesbefore', 'quizaccess_dailyattempts', 2), $rule->prevent_new_attempt(2, $last));
        $this->assertTrue($rule->is_finished(2, $last));
    }

    public function test_leaves_the_message_to_other_rules_when_no_attempts_are_left_anyway(): void {
        $this->resetAfterTest();
        $quiz = $this->quiz(['attempts' => 2]);
        $user = $this->getDataGenerator()->create_user();
        $this->attempt($quiz, $user->id, self::NOW - 200);
        $last = $this->attempt($quiz, $user->id, self::NOW - 100);
        $rule = $this->rule($quiz);
        $this->assertFalse($rule->prevent_new_attempt(2, $last));
        $this->assertFalse($rule->is_finished(1, $last));
    }

    public function test_deleting_the_quiz_deletes_the_setting(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $quiz = $this->quiz();
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        quiz_delete_instance($quiz->id);
        $this->assertFalse($DB->record_exists('quizaccess_dailyattempts', ['quizid' => $quiz->id]));
    }
}

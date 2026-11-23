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

/**
 * Attempts per day: a student can start at most N attempts in any 24 hours.
 *
 * @package    quizaccess_dailyattempts
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * The attempts per day rule.
 */
class quizaccess_dailyattempts extends access_rule_base {
    /** @var int length of the window, in seconds */
    public const WINDOW = DAYSECS;

    /** @var int most attempts a teacher can choose */
    public const MAX = 10;

    /** @var string table holding the setting */
    private const TABLE = 'quizaccess_dailyattempts';

    /**
     * The rule, when the quiz has a limit.
     *
     * @param quiz_settings $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return self|null
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->dailyattempts_maxattempts)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * The limit for this quiz.
     *
     * @return int
     */
    private function limit(): int {
        return (int)$this->quiz->dailyattempts_maxattempts;
    }

    /**
     * When the student can start their next attempt, or 0 when they can now.
     *
     * Counts the real attempts (not previews) the student started in the last 24 hours, abandoned ones too.
     *
     * @param int $userid
     * @return int
     */
    private function next_start_time(int $userid): int {
        global $DB;
        $starts = $DB->get_fieldset_select(
            'quiz_attempts',
            'timestart',
            'quiz = :quiz AND userid = :userid AND preview = 0 AND timestart > :since ORDER BY timestart DESC',
            ['quiz' => $this->quiz->id, 'userid' => $userid, 'since' => $this->timenow - self::WINDOW]
        );
        if (count($starts) < $this->limit()) {
            return 0;
        }
        // The window frees up when the oldest of the last N attempts is 24 hours old.
        return (int)$starts[$this->limit() - 1] + self::WINDOW;
    }

    /**
     * Whether no attempt is left anyway (attempts used up, or quiz closed), so other rules explain it.
     *
     * @param int $numprevattempts
     * @return bool
     */
    private function nothing_left_anyway(int $numprevattempts): bool {
        return ($this->quiz->attempts > 0 && $numprevattempts >= $this->quiz->attempts)
            || ($this->quiz->timeclose != 0 && $this->timenow > $this->quiz->timeclose);
    }

    /**
     * Stop a new attempt while the student has used their attempts for the last 24 hours.
     *
     * @param int $numprevattempts
     * @param stdClass|false|null $lastattempt
     * @return string|false
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        if (!$numprevattempts || empty($lastattempt->userid) || $this->nothing_left_anyway($numprevattempts)) {
            return false;
        }
        $next = $this->next_start_time((int)$lastattempt->userid);
        if ($next <= $this->timenow) {
            return false;
        }
        if ($this->quiz->timeclose != 0 && $next > $this->quiz->timeclose) {
            return get_string('closesbefore', 'quizaccess_dailyattempts', $this->limit());
        }
        return get_string('waituntil', 'quizaccess_dailyattempts', ['max' => $this->limit(), 'time' => userdate($next)]);
    }

    /**
     * Finished when the student must wait past the quiz closing time.
     *
     * @param int $numprevattempts
     * @param stdClass|false|null $lastattempt
     * @return bool
     */
    public function is_finished($numprevattempts, $lastattempt) {
        if (
            !$numprevattempts || empty($lastattempt->userid) || $this->nothing_left_anyway($numprevattempts)
                || $this->quiz->timeclose == 0
        ) {
            return false;
        }
        return $this->next_start_time((int)$lastattempt->userid) > $this->quiz->timeclose;
    }

    /**
     * Shown on the quiz page.
     *
     * @return string
     */
    public function description() {
        return get_string('description', 'quizaccess_dailyattempts', $this->limit());
    }

    /**
     * The setting, in the "Extra restrictions on attempts" section.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $options = [0 => get_string('unlimited')] + array_combine(range(1, self::MAX), range(1, self::MAX));
        $mform->addElement(
            'select',
            'dailyattempts_maxattempts',
            get_string('maxattempts', 'quizaccess_dailyattempts'),
            $options
        );
        $mform->addHelpButton('dailyattempts_maxattempts', 'maxattempts', 'quizaccess_dailyattempts');
        $mform->setDefault('dailyattempts_maxattempts', 0);
    }

    /**
     * Save the setting; no row when there is no limit.
     *
     * @param stdClass $quiz
     */
    public static function save_settings($quiz) {
        global $DB;
        $max = max(0, min(self::MAX, (int)($quiz->dailyattempts_maxattempts ?? 0)));
        $existing = $DB->get_record(self::TABLE, ['quizid' => $quiz->id]);
        if (!$max) {
            $DB->delete_records(self::TABLE, ['quizid' => $quiz->id]);
        } else if ($existing) {
            $DB->set_field(self::TABLE, 'maxattempts', $max, ['id' => $existing->id]);
        } else {
            $DB->insert_record(self::TABLE, (object)['quizid' => $quiz->id, 'maxattempts' => $max]);
        }
    }

    /**
     * Delete the setting with the quiz.
     *
     * @param stdClass $quiz
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records(self::TABLE, ['quizid' => $quiz->id]);
    }

    /**
     * Load the setting with the quiz.
     *
     * @param int $quizid
     * @return array
     */
    public static function get_settings_sql($quizid) {
        return [
            'dailyattempts.maxattempts AS dailyattempts_maxattempts',
            'LEFT JOIN {quizaccess_dailyattempts} dailyattempts ON dailyattempts.quizid = quiz.id',
            [],
        ];
    }
}

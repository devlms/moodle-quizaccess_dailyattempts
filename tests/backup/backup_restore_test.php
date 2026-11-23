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

namespace quizaccess_dailyattempts\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/phpunit/classes/restore_date_testcase.php');

/**
 * Backup and restore of the attempts per day setting.
 *
 * @package    quizaccess_dailyattempts
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_quizaccess_dailyattempts_subplugin
 * @covers     \restore_quizaccess_dailyattempts_subplugin
 */
final class backup_restore_test extends \restore_date_testcase {
    public function test_setting_comes_back_with_the_course(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'dailyattempts_maxattempts' => 3]);

        $newcourseid = $this->backup_and_restore($course);

        $newquiz = $DB->get_record('quiz', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertEquals(3, $DB->get_field('quizaccess_dailyattempts', 'maxattempts', ['quizid' => $newquiz->id]));
    }
}

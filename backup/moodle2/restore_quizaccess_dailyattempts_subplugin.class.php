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
 * Restore of the attempts per day setting.
 *
 * @package    quizaccess_dailyattempts
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

/**
 * Restores the setting with the quiz.
 */
class restore_quizaccess_dailyattempts_subplugin extends restore_mod_quiz_access_subplugin {
    /**
     * Paths to restore.
     *
     * @return restore_path_element[]
     */
    protected function define_quiz_subplugin_structure() {
        return [new restore_path_element('quizaccess_dailyattempts', $this->get_pathfor('/quizaccess_dailyattempts'))];
    }

    /**
     * Restore the setting.
     *
     * @param array $data
     */
    public function process_quizaccess_dailyattempts($data) {
        global $DB;
        $data = (object)$data;
        $data->quizid = $this->get_new_parentid('quiz');
        unset($data->id);
        $DB->insert_record('quizaccess_dailyattempts', $data);
    }
}

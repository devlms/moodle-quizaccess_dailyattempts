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
 * Strings for the attempts per day rule.
 *
 * @package    quizaccess_dailyattempts
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['closesbefore'] = 'You have used all your attempts for the last 24 hours ({$a} per 24 hours), and the quiz closes before you can start another.';
$string['description'] = 'Attempts you can start in any 24 hours: {$a}';
$string['maxattempts'] = 'Attempts per 24 hours';
$string['maxattempts_help'] = 'How many attempts a student can start in any 24 hours. When they reach it, they see when they can start the next one. Abandoned attempts count; teacher previews do not. Use it for practice quizzes, so students spread their practice out instead of retrying until a lucky score.';
$string['pluginname'] = 'Attempts per day quiz access rule';
$string['privacy:metadata'] = 'The attempts per day rule stores no personal data. It counts the attempts the quiz already records.';
$string['waituntil'] = 'You have used all your attempts for the last 24 hours ({$a->max} per 24 hours). You can start another attempt after {$a->time}.';

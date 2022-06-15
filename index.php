<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display a user grade report for all courses
 *
 * @package    report
 * @subpackage feedbackdashboard
 * @copyright  2019 onwards Solent University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once($CFG->dirroot .'/report/feedbackdashboard/lib.php');

if (isguestuser()) {
    throw new moodle_exception('viewfeedbackerror', 'report_feedbackdashboard');
}

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url('/report/feedbackdashboard/index.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_feedbackdashboard'));

$PAGE->set_heading(fullname($USER) . ' - ' . get_string('pluginname', 'report_feedbackdashboard'));

// Trigger an grade report viewed event.
$event = \report_feedbackdashboard\event\feedbackdashboard_report_viewed::create(array(
            'context' => context_user::instance($USER->id),
            'relateduserid' => $USER->id,
            'other' => array(
                  'userid' => $USER->id
              )
          ));
$event->trigger();

echo $OUTPUT->header();

$courses = enrol_get_my_courses('enddate', 'enddate DESC');

if (count($courses) == 0) {
    echo get_string('nodashboard', 'report_feedbackdashboard');
    echo $OUTPUT->footer();
    exit();
}

$studentcourses = array();
$tutorcourses = array();
$validcourses = false;

foreach ($courses as $course) {
    $category = core_course_category::get($course->category, IGNORE_MISSING);
    $context = context_course::instance($course->id);
    
    // Shows all modules to students.
    if (has_capability('mod/assign:submit', $context) && strpos($category->idnumber, 'modules_') !== false) {
        $studentcourses[$course->id] = $course;
        $validcourses = true;
    }

    // Shows only current modules to tutors.
    if (has_capability('mod/assign:grade', $context) && strpos($category->idnumber, 'modules_current') !== false) {
        $tutorcourses[$course->id] = $course;
        $validcourses = true;
    }		
}

if (!$validcourses) {
    echo get_string('nodashboard', 'report_feedbackdashboard');
} else {
    echo '<button id="print-btn" class="btn btn-primary" onClick="window.print()">' . get_string('print', 'report_feedbackdashboard') . '</button><br>';
    echo report_feedbackdashboard_get_student_dashboard($studentcourses, $tutorcourses);
    echo report_feedbackdashboard_get_tutor_dashboard($tutorcourses, $studentcourses);
}

echo $OUTPUT->footer();

<?php

namespace block_zoomlti;

global $CFG, $DB, $OUTPUT, $PAGE;

use function src\transformer\utils\get_user;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/zoom/lib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');

define("ZOOMLTI_ROLE_STUDENT", 5);

require_login();

$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

$context = \context_system::instance();

$PAGE->set_url($CFG->wwwroot . '/blocks/zoomlti/update_grade.php');
$PAGE->set_context($context);
$PAGE->set_title("評点更新");
$PAGE->set_heading("評点更新");

$zoom = $DB->get_record('zoom', ['id' => $instanceid], '*', MUST_EXIST);

echo $OUTPUT->header();

$sql = "select * from {zoom} z join {zoom_meeting_details} d on z.id = d.zoomid where z.id = :instanceid";
$instance = $DB->get_record_sql($sql, ["instanceid" => $instanceid]);

//block_zoomlti_polls
$poll_answers = $DB->get_records('block_zoomlti_polls', ['zoomid' => $instanceid, "question_sequence" => 0]);
$service = new zoomlti_dao();

$users = enrol_get_course_users_roles($courseid);
$polls = $service->get_polls($instance->meeting_id);
foreach ($polls->questions as $questions) {
    $user = \core_user::get_user_by_email($questions->email);
    if (!$user) {
        continue;
    }
    $questions->question_details = array_reverse($questions->question_details);
    foreach ($questions->question_details as $seq => $d) {
        $score_passed = $DB->get_record("block_zoomlti_score_passed", ["zoomid" => $instanceid, "question_sequence" => $seq]);

        $item = [];
        $item['itemname'] = $d->question;
        $item['categoryid'] = 1;
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = 100;
        $item['grademin'] = 0;
        $item['gradepass'] = $score_passed->score_passed;

        $scores = $DB->get_records("block_zoomlti_polls", ["zoomid" => $instanceid, "question_sequence" => $seq]);
        $score_m = [];
        foreach ($scores as $score) {
            $score_m[$score->answer] = $score->score;
        }

        $user_answers = explode(";", $d->answer);
        $u_score = 0;
        foreach ($user_answers as $ua) {
            $u_score += $score_m[$ua];
        }
        $grade = grade_get_grades($zoom->course, 'mod', 'zoom', $zoom->id, $user->id);
        $grade->userid = $user->id;
        $grade->rawgrade = $u_score;

        grade_update('mod/zoom', $courseid, 'mod', 'zoom', $instanceid, $seq, $grade, $item);
    }
}

echo $OUTPUT->footer();

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

$sql = "select * from {zoom} z join {zoom_meeting_details} d on z.id = d.zoomid where z.id = :instanceid";
$instance = $DB->get_record_sql($sql, ["instanceid" => $instanceid]);

$sql = "select * from {zoom} z join {zoom_meeting_details} d on d.zoomid = z.id join {zoom_meeting_participants} p on p.detailsid = d.id where d.zoomid = :instanceid";
$participants = $DB->get_records_sql($sql, ["instanceid" => $instanceid]);


$service = new zoomlti_dao();
$polls = $service->get_polls($instance->meeting_id);

//var_dump($polls);

foreach ($participants as $participant) {
    foreach ($polls->questions as $questions) {
        $user = \core_user::get_user($participant->userid);
        if (!$user) {
            continue;
        }

//        $questions->question_details = array_reverse($questions->question_details);
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

            $user = \core_user::get_user($user->id);
            $course = $DB->get_record("course", ["id" => $courseid]);

            $event = event\zoomlti_meeting_polled::create([
                'userid' => $user->id,
                'objectid' => $instanceid,
                'context' => $context,
                'other' => [
                    'topic' => $d->question,
                    'source' => "moodle",
                    'courseid' => $courseid,
                    'username' => !$user ? null : $user->username,
                    'moodleuserid' => !$user ? null : $user->id,
                    'question_title' => $d->question,
                    'question_answer' => $d->answer,
                    'meeting_id' => $instance->meeting_id,
                    'grade' => $grade
                ]
            ]);

            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot("zoom", $instance);
            $event->trigger();
        }
    }
}

foreach ($participants as $participant) {
    $user = \core_user::get_user($participant->userid);
    if (!$user) {
        continue;
    }

    $item = [];
    $item['itemname'] = "出席($instance->name)";
    $item['gradetype'] = GRADE_TYPE_VALUE;

    $grade = grade_get_grades($zoom->course, 'mod', 'zoom', $participant->zoomid, $participant->userid);
    $grade->userid = $participant->userid;
    $grade->rawgrade = 10;
    $grade->feedback = "出席";
    $grade->feedbackformat = FORMAT_PLAIN;

    grade_update('mod/zoom', $courseid, 'mod', 'zoom', $participant->zoomid, $participant->zoomid, $grade, $item);
}

redirect(new \moodle_url('index.php', ["courseid" => $courseid]), "成績の集計が完了しました。");
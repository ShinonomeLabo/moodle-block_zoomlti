<?php

namespace block_zoomlti;

global $CFG, $DB, $OUTPUT, $PAGE;

use function src\transformer\utils\get_user;

require_once(__DIR__ . '/../../config.php');

require_login();

$instanceid = required_param('instanceid', PARAM_INT);

$instance = $DB->get_record("zoom", ["id" => $instanceid]);

$sql = "select p.id participantsid, z.course, d.id detailsid, d.meeting_id, d.end_time, d.topic topic, p.join_time, p.leave_time, p.userid userid, d.zoomid zoomid from {zoom_meeting_details} d join {zoom_meeting_participants} p on d.id = p.detailsid join {zoom} z on z.id = zoomid where zoomid = :zoomid";

$zoom_meeting_participants = $DB->get_records_sql($sql, ["zoomid" => $instanceid]);

$cm = get_coursemodule_from_instance("zoom", $instanceid);
$context = \context_module::instance($cm->id);

foreach ($zoom_meeting_participants as $participant) {
    $userid = !$participant->userid ? 0 : $participant->userid;

    $block_zoomlti_logged = $DB->record_exists("block_zoomlti_logged", ["zoom_meeting_participantid" => $participant->participantsid]);
    if ($block_zoomlti_logged) {
        continue;
    }

    $user = \core_user::get_user($participant->userid);

    $course = $DB->get_record("course", ["id" => $participant->course]);

    $event = event\zoomlti_meeting_finished::create([
        'userid' => $userid,
        'objectid' => $instanceid,
        'context' => $context,
        'other' => [
            'source' => "moodle",
            'courseid' => $participant->course,
            'username' => !$user ? null : $user->username,
            'moodleuserid' => !$user ? null : $user->id,
            'meeting_id' => $participant->meeting_id,
            'join_time' => $participant->join_time,
            'leave_time' => $participant->leave_time,
            'topic' => $participant->topic,
        ]
    ]);

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot("zoom", $instance);
    $event->trigger();

    $data = new \stdClass();
    $data->userid = $userid;
    $data->zoomid = $participant->zoomid;
    $data->zoom_meeting_participantid = $participant->participantsid;
    $data->timecreated = time();

    $DB->insert_record("block_zoomlti_logged", $data);
}

redirect(new \moodle_url('index.php', ["courseid" => $cm->course]), "ログのエクスポートが完了しました。");
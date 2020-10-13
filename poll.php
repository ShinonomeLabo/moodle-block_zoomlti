<?php

namespace block_zoomlti;

global $CFG, $DB, $OUTPUT, $PAGE;

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

$context = \context_system::instance();

$PAGE->set_url($CFG->wwwroot . '/blocks/zoomlti/poll.php');
$PAGE->set_context($context);
$PAGE->set_title("投票一覧");
$PAGE->set_heading("投票一覧");

$sql = "select * from {zoom} z join {zoom_meeting_details} d on z.id = d.zoomid where z.id = :instanceid";
$instance = $DB->get_record_sql($sql, ["instanceid" => $instanceid]);
if (!$instance) {
    redirect(new \moodle_url('index.php', ["courseid" => $courseid]), "このZoomミーティングはMoodle上で出席者情報が処理されていません。cronによる集計処理の実行完了をお待ちください。", \core\output\notification::NOTIFY_ERROR);
}
$service = new zoomlti_dao();
$polls = $service->get_polls($instance->meeting_id);
if (empty($polls->questions)) {
    redirect(new \moodle_url('index.php', ["courseid" => $courseid]), "このZoomミーティングでは投票機能が利用されていませんでした。", \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();

//$modules = get_coursemodules_in_course('zoom', $courseid);
$table = new \html_table();
foreach ($polls->questions as $questions) {
    $table->head = ["回答者", "質問", "回答"];
    foreach ($questions->question_details as $details) {
        $answers = explode(";", $details->answer);
        $table->data[] = [
            $questions->name,
            $details->question,
            $details->answer,
        ];
    }
    echo \html_writer::table($table);
}

echo $OUTPUT->footer();

<?php

namespace block_zoomlti;

global $CFG, $DB, $OUTPUT, $PAGE;

require_once(__DIR__ . '/../../config.php');

require_login();

confirm_sesskey();

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

if (array_key_exists("score_answers", $_POST)) {
    // update answer score
    $score_answers = $_POST["score_answers"];
    foreach ($score_answers as $question_sequence => $answers) {
        if ($DB->record_exists("block_zoomlti_polls", ["zoomid" => $instanceid])) {
            $DB->delete_records("block_zoomlti_polls", ["zoomid" => $instanceid, "question_sequence" => $question_sequence]);
        }
        foreach ($answers as $question_answer => $score) {
            $data = new \stdClass();
            $data->zoomid = $instanceid;
            $data->question_sequence = $question_sequence;
            $data->question = explode("_", $question_answer)[0];
            $data->answer = explode("_", $question_answer)[1];
            $data->score = $score;
            $DB->insert_record("block_zoomlti_polls", $data);
        }
    }
    // update passed score
    $score_passeds = $_POST["score_passed"];
    foreach ($score_passeds as $seq => $score_passed) {
        if ($DB->record_exists("block_zoomlti_score_passed", ["zoomid" => $instanceid, "question_sequence" => $seq])) {
            $DB->delete_records("block_zoomlti_score_passed", ["zoomid" => $instanceid, "question_sequence" => $seq]);
        }
        $data = new \stdClass();
        $data->zoomid = $instanceid;
        $data->question_sequence = $seq;
        $data->score_passed = $score_passed;
        $DB->insert_record("block_zoomlti_score_passed", $data);
    }

    echo \html_writer::div("合格点を保存しました", "alert alert-success");
}

$questions_m = $service->get_poll_questions($instance->meeting_id);

echo \html_writer::tag("h2", "ミーティング中に実施した投票");

echo \html_writer::start_tag("form", ["method" => "post", "action" => "poll.php?courseid=" . $courseid . "&instanceid=" . $instanceid]);

$scores = $DB->get_records("block_zoomlti_polls", ["zoomid" => $instanceid]);
$score_table = [];
foreach ($scores as $score) {
    $score_table[] = $score->score;
}

$score_passed = $DB->get_records("block_zoomlti_score_passed", ["zoomid" => $instanceid]);
$score_passed_table = [];
foreach ($score_passed as $passed) {
    $score_passed_table[] = $passed->score_passed;
}

$a_db_id = 0;
foreach ($questions_m->polls as $p_id => $p) {
    $table = new \html_table();
    $table->head = ["質問文", "種別", "回答"];

    if(!array_key_exists($p_id, $score_passed_table)){
        $score_passed_table[$p_id] = 0;
    }

    foreach ($p->questions as $q_id => $question) {
        $data = [
            $question->name,
            $question->type
        ];

        $table_answer = new \html_table();
        foreach ($question->answers as $a_id => $answer) {
            if(!array_key_exists($a_db_id, $score_table)){
                $score_table[$a_db_id] = 0;
            }
            $table_answer->data[] = [
                $answer,
                \html_writer::empty_tag("input", ["type" => "text", "name" => "score_answers[" . $p_id . "][" . $question->name . "_" . $answer . "]", "value" => $score_table[$a_db_id]])
            ];
            $a_db_id++;
        }
        $table_answer->data[] = [
            "合格点",
            \html_writer::empty_tag("input", ["type" => "text", "name" => "score_passed[$p_id]", "value" => $score_passed_table[$p_id]])
        ];
        $data[] = \html_writer::table($table_answer);

        $table->data[] = $data;
    }
    echo \html_writer::table($table);
}

echo \html_writer::empty_tag("input", ["type" => "hidden", "name" => "sesskey", "value" => sesskey()]);

echo \html_writer::start_div("col-12 clearfix");

echo \html_writer::start_div("float-left");
echo \html_writer::link(new \moodle_url("index.php", ["courseid" => $courseid]), "トップ画面へ戻る", ["class" => "btn btn-primary"]);
echo \html_writer::end_div();

echo \html_writer::start_div("float-right");
echo \html_writer::empty_tag("input", ["type" => "submit", "value" => "保存する", "class" => "btn btn-success"]);
echo \html_writer::end_div();

echo \html_writer::end_div();

echo \html_writer::end_tag("form");
echo \html_writer::empty_tag("hr");

echo \html_writer::tag("h2", "投票に対する回答一覧");

$table = new \html_table();
$table->head = ["質問", "回答者", "回答者のメールアドレス", "回答"];
foreach ($polls->questions as $questions) {
    foreach ($questions->question_details as $details) {
        $table->data[] = [
            $details->question,
            $questions->name,
            object_property_exists($questions, "email") == false ? "(メールアドレスなし)" : $questions->email,
            $details->answer,
        ];
    }
}
echo \html_writer::table($table);

echo $OUTPUT->footer();
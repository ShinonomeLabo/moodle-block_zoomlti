<?php

namespace block_zoomlti;

global $CFG, $DB, $OUTPUT, $PAGE;

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);

$context = \context_system::instance();

$PAGE->set_url($CFG->wwwroot . '/blocks/zoomlti/index.php');
$PAGE->set_context($context);
$PAGE->set_title("コース上のモジュール一覧");
$PAGE->set_heading("コース上のモジュール一覧");

echo $OUTPUT->header();

$modules = get_coursemodules_in_course('zoom', $courseid);

$table = new \html_table();
$table->head = ["ID", "モジュール名", "ログをエクスポート", "投票結果を集計", "評点を更新"];
foreach($modules as $module){
    $table->data[] = [
        $module->id,
        $module->name,
        \html_writer::link(new \moodle_url("logging_to.php", ["instanceid" => $module->instance]), "履歴をエクスポートする", ["class" => "btn btn-primary"]),
        \html_writer::link(new \moodle_url("poll.php", ["instanceid" => $module->instance, "courseid" => $courseid]), "投票結果を集計する", ["class" => "btn btn-primary"]),
        \html_writer::link(new \moodle_url("update_grade.php", ["instanceid" => $module->instance, "courseid" => $courseid]), "評点を更新する", ["class" => "btn btn-primary"]),
    ];
}

echo \html_writer::table($table);

echo $OUTPUT->footer();

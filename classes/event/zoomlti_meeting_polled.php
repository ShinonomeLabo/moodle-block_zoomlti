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
 * Defines the view event.
 *
 * @package    block_zoomlti
 * @copyright  2020 Shinonome Laboratory
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_zoomlti\event;
use core\event\base;

defined('MOODLE_INTERNAL') || die();

class zoomlti_meeting_polled extends base {

    protected function init() {
        $this->data['objecttable'] = 'zoomlti';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['userid'] = $this->other['moodleuserid'];
        $this->data['courseid'] = $this->other['courseid'];
        $this->data['grade'] = $this->other['grade'];
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public static function get_name() {
        return "Zoomミーティングで投票に回答";
    }

    public function get_description() {
        $topic = $this->other['topic'];
        $title = $this->other['question_title'];
        $answer = $this->other['question_answer'];

        return "ユーザー($this->userid)がZoomミーティング($topic)上の投票に回答しました。(問題 : $title, 回答 : $answer)";
    }

    protected function validate_data() {
        parent::validate_data();
    }
}
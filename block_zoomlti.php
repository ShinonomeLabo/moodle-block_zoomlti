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
 * Block zoomlti is defined here.
 *
 * @package     block_zoomlti
 * @copyright   2020 Shinonome Laboraotry <otayori@nagumo.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * zoomlti block.
 *
 * @package    block_zoomlti
 * @copyright  2020 Shinonome Laboraotry <otayori@nagumo.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_zoomlti extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_zoomlti');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $CFG;

        $this->content = new stdClass();
        $this->content->items = [];
        $this->content->icons = [];
        $this->content->footer = '';

        $courseid = $this->page->course->id;

        $html = html_writer::link(
            new moodle_url($CFG->wwwroot . '/blocks/zoomlti/index.php', ["courseid" => $courseid]),
            '出席情報をエクスポート',
            ['class' => 'btn btn-primary', 'target' => '_blank']
        );
        return $this->content = (object)['text' => $html];
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_zoomlti');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    function has_config() {
        return true;
    }

    public function applicable_formats()
    {
        return [
            'all' => false,
            'site' => false,
            'site-index' => false,
            'course' => true,
            'my' => false
        ];
    }
}

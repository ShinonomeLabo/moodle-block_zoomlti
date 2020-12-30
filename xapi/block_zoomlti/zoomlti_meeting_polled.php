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

namespace src\transformer\events\block_zoomlti;

defined('MOODLE_INTERNAL') || die();

use src\transformer\utils as utils;

function zoomlti_meeting_polled(array $config, \stdClass $event)
{
    $repo = $config['repo'];
    $user = $repo->read_record_by_id('user', $event->userid);
    $course = $repo->read_record_by_id('course', $event->courseid);
    $lang = utils\get_course_lang($course);

    $event_other = unserialize($event->other);

    return [[
        'actor' => utils\get_user($config, $user),
        'verb' => [
            'id' => 'http://id.tincanapi.com/verb/viewed',
            'display' => [
                $lang => 'polled'
            ],
        ],
        'object' => utils\get_activity\course_module(
            $config,
            $course,
            $event->contextinstanceid,
            'http://adlnet.gov/expapi/activities/link'
        ),
        'result' => [
            'response' => "Polled",
            'completion' => true,
            'extensions' => [
                'http://learninglocker.net/xapi/cmi/zoomlti/moodleuserid' => $event_other['moodleuserid'],
                'http://learninglocker.net/xapi/cmi/zoomlti/meeting_id' => $event_other['meeting_id'],
                'http://learninglocker.net/xapi/cmi/zoomlti/topic' => $event_other['topic'],
                'http://learninglocker.net/xapi/cmi/zoomlti/question_title' => $event_other['question_title'],
                'http://learninglocker.net/xapi/cmi/zoomlti/question_answer' => $event_other['question_answer'],
            ],
        ],

        'timestamp' => utils\get_event_timestamp($event),
        'context' => [
            'platform' => $config['source_name'],
            'language' => $lang,
            'extensions' => utils\extensions\base($config, $event, $course),
            'contextActivities' => [
                'grouping' => [
                    utils\get_activity\site($config),
                    utils\get_activity\course($config, $course),
                ],
                'category' => [
                    utils\get_activity\source($config),
                ]
            ]
        ]
    ]];
}
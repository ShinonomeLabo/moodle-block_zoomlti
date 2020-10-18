<?php

namespace block_zoomlti;

global $CFG;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../course/lib.php';
require_once($CFG->dirroot.'/mod/zoom/classes/webservice.php');

class zoomlti_dao extends \mod_zoom_webservice
{
    public function get_polls($meeting_id) {
        $url = "past_meetings/$meeting_id/polls";

        try {
            $response = $this->_make_call($url);
        } catch (\moodle_exception $error) {
            throw $error;
        }
        return $response;
    }

    public function get_poll_questions($meeting_id) {
        $url = "meetings/$meeting_id/polls";

        try {
            $response = $this->_make_call($url);
        } catch (\moodle_exception $error) {
            throw $error;
        }
        return $response;
    }

    public function get_poll_details($meeting_id, $poll_id) {
        $url = "past_meetings/$meeting_id/polls/$poll_id";

        try {
            $response = $this->_make_call($url);
        } catch (\moodle_exception $error) {
            throw $error;
        }
        return $response;
    }

    public function get_participants($meeting_uuid) {
        $url = "past_meetings/$meeting_uuid/participants/";

        try {
            $response = $this->_make_call($url);
        } catch (\moodle_exception $error) {
            throw $error;
        }
        return $response;
    }
}
<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_quiz\access_manager;
use mod_quiz\output\list_of_attempts;
use mod_quiz\output\renderer;
use mod_quiz\output\view_page;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use Psr\Http\Message\RequestInterface;
use moodle_url;
use context_system;
use core\moodle_exception;
use DOMDocument;
use mod_glossary\local\concept_cache;



class local_iacdp_softskills_external extends external_api
{

    public static function get_competencies_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function get_competencies()
    {
        global $DB;
        global $CFG;

        self::cors();
        $sql = "SELECT t.id, t.rawname 
        FROM {tag} t 
        JOIN {tag_instance} ti ON t.id = ti.tagid 
        WHERE ti.itemtype = 'badge'";
        $tags = $DB->get_records_sql($sql);

        $result = array();

        // category of badges
        foreach ($tags as $tag) {
            $categoryObj = new stdClass();
            $categoryObj->id = $tag->id;
            $categoryObj->name = $tag->rawname;
            $categoryObj->badges = array();

            // badges associated to the current tag
            $sql = "SELECT b.* 
            FROM {badge} b 
            JOIN {tag_instance} ti ON ti.itemid = b.id 
            WHERE ti.itemtype = 'badge' 
            AND ti.tagid = :tagid";
            $badgesInTag = $DB->get_records_sql($sql, array('tagid' => $tag->id));

            foreach ($badgesInTag as $badge) {
                $badgeObj = new stdClass();
                $badgeObj->id = $badge->id;
                $badgeObj->name = $badge->name;
                $badgeObj->description = $badge->descriptionhtml;
                $badgeObj->message = $badge->message;
                $badgeObj->messagesubject = $badge->messagesubject;
                $badgeObj->badgeurl = "$CFG->wwwroot/pluginfile.php/1/badges/badgeimage/" . $badge->id . "/f1";
                $badgeObj->path = '/sk-levels';
                $badgeObj->category = $categoryObj->name;

                if ($categoryObj->name == 'Organisationnel') {
                    $badgeObj->accentColor = 'green';
                } else if ($categoryObj->name == 'Communicationnel') {
                    $badgeObj->accentColor = 'blue';
                } else if ($categoryObj->name == 'Réflexif parcours') {
                    $badgeObj->accentColor = 'green';
                } else if ($categoryObj->name == 'Réflexif-Action') {
                    $badgeObj->accentColor = 'purple';
                }

                $categoryObj->badges[] = $badgeObj;
            }

            $result[] = $categoryObj;
        }

        return json_encode($result);
    }

    public static function get_competencies_returns()
    {
        return new external_value(PARAM_RAW, 'Competencies data in JSON format');
    }


    private static function cors()
    {

        $origins = array();
        $origins[] = 'http://localhost:8100';
        $origins[] = 'ionic://localhost';
        $origins[] = 'http://localhost';
        $origins[] = 'https://localhost';

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            if (in_array($_SERVER['HTTP_ORIGIN'], $origins)) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] . "");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');    // cache for 1 day
            }
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
    }
}

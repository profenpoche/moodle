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
                $badgeObj->progress = false;
                $badgeObj->todo = true;
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



    public static function get_categories_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function get_categories()
    {
        global $DB;
        global $CFG;

        self::cors();

        // Récupérer toutes les catégories
        $categories = $DB->get_records('course_categories', [], 'sortorder ASC');
        $result = array();

        foreach ($categories as $category) {
            $categoryObj = new stdClass();
            $categoryObj->id = $category->id;
            $categoryObj->name = $category->name;
            $categoryObj->description = $category->description;
            $categoryObj->courses = array();

            // Récupérer tous les cours de cette catégorie
            $courses = $DB->get_records('course', ['category' => $category->id]);

            foreach ($courses as $course) {
                $courseObj = new stdClass();
                $courseObj->id = $course->id;
                $courseObj->fullname = $course->fullname;
                $courseObj->shortname = $course->shortname;
                $courseObj->summary = $course->summary;
                $courseObj->sections = array();

                $sections = $DB->get_records('course_sections', ['course' => $course->id], 'section ASC');

                foreach ($sections as $section) {
                    $sectionObj = new stdClass();
                    $sectionObj->id = $section->id;
                    $sectionObj->name = $section->name;
                    $sectionObj->summary = $section->summary;
                    $sectionObj->sequence = $section->sequence;
                    $sectionObj->category = $categoryObj->name;

                    $courseObj->sections[] = $sectionObj;
                }

                $categoryObj->courses[] = $courseObj;
            }

            $result[] = $categoryObj;
        }

        return json_encode($result);
    }



    // public static function get_categories()
    // {
    //     global $DB;
    //     global $CFG;

    //     self::cors();

    //     // Récupérer toutes les catégories
    //     $categories = $DB->get_records('course_categories', [], 'sortorder ASC');
    //     $result = array();

    //     foreach ($categories as $category) {
    //         $categoryObj = new stdClass();
    //         $categoryObj->id = $category->id;
    //         $categoryObj->name = $category->name;
    //         $categoryObj->description = $category->description;
    //         $categoryObj->courses = array();

    //         // Récupérer tous les cours de cette catégorie
    //         $courses = $DB->get_records('course', ['category' => $category->id]);

    //         foreach ($courses as $course) {
    //             $courseObj = new stdClass();
    //             $courseObj->id = $course->id;
    //             $courseObj->fullname = $course->fullname;
    //             $courseObj->shortname = $course->shortname;
    //             $courseObj->summary = $course->summary;
    //             $courseObj->sections = array();

    //             // Récupérer toutes les sections du cours
    //             $sections = $DB->get_records('course_sections', ['course' => $course->id], 'section ASC');

    //             foreach ($sections as $section) {
    //                 $sectionObj = new stdClass();
    //                 $sectionObj->id = $section->id;
    //                 $sectionObj->name = $section->name;
    //                 $sectionObj->summary = $section->summary;
    //                 $sectionObj->sequence = $section->sequence;
    //                 $sectionObj->modules = array();

    //                 // Récupérer les modules de la section
    //                 if (!empty($section->sequence)) {
    //                     $moduleids = explode(',', $section->sequence);
    //                     foreach ($moduleids as $moduleid) {
    //                         $module = $DB->get_record('course_modules', array('id' => $moduleid));
    //                         if ($module) {
    //                             $moduleinfo = $DB->get_record($DB->get_field('modules', 'name', array('id' => $module->module)), array('id' => $module->instance));
    //                             if ($moduleinfo) {
    //                                 $moduleObj = new stdClass();
    //                                 $moduleObj->id = $module->id;
    //                                 $moduleObj->type = $DB->get_field('modules', 'name', array('id' => $module->module));
    //                                 $moduleObj->name = $moduleinfo->name;
    //                                 $moduleObj->intro = $moduleinfo->intro;
    //                                 $moduleObj->visible = $module->visible;

    //                                 $sectionObj->modules[] = $moduleObj;
    //                             }
    //                         }
    //                     }
    //                 }

    //                 $courseObj->sections[] = $sectionObj;
    //             }

    //             $categoryObj->courses[] = $courseObj;
    //         }

    //         $result[] = $categoryObj;
    //     }

    //     return json_encode($result);
    // }

    public static function get_categories_returns()
    {
        return new external_value(PARAM_RAW, 'Categories data in JSON format');
    }


    public static function get_competencies_framework_parameters()
    {
        return new external_function_parameters([]);
    }


    public static function get_competencies_framework()
    {
        global $DB;
        global $CFG;

        self::cors();

        $frameworks = $DB->get_records('competency_framework');
        $result = new stdClass();

        foreach ($frameworks as $framework) {
            $frameworkObj = new stdClass();
            $frameworkObj->id = $framework->id;
            $frameworkObj->shortname = $framework->shortname;
            $frameworkObj->idnumber = $framework->idnumber;
            $frameworkObj->description = $framework->description;
            $frameworkObj->descriptionformat = $framework->descriptionformat;
            $frameworkObj->visible = $framework->visible;
            $frameworkObj->competencies = array();

            $competencies = $DB->get_records('competency', array('competencyframeworkid' => $framework->id, 'parentid' => 0));

            foreach ($competencies as $competency) {
                $competencyObj = new stdClass();
                $competencyObj->id = $competency->id;
                $competencyObj->shortname = $competency->shortname;
                $competencyObj->idnumber = $competency->idnumber;
                $competencyObj->description = $competency->description;
                $competencyObj->descriptionformat = $competency->descriptionformat;
                $competencyObj->sortorder = $competency->sortorder;
                $competencyObj->parentid = $competency->parentid;
                $competencyObj->path = $competency->path;
                $competencyObj->todo = true;
                $competencyObj->accentColor = 'green';

                if (strpos(strtolower($competencyObj->shortname), "organisationnel") !== false) {
                    $competencyObj->accentColor = 'green';
                } else if (strpos(strtolower($competencyObj->shortname), "communicationnel") !== false) {
                    $competencyObj->accentColor = 'blue';
                } else if (strpos(strtolower($competencyObj->shortname), "reflexif-action") !== false) {
                    $competencyObj->accentColor = 'purple';
                } else if (strpos(strtolower($competencyObj->shortname), "reflexif-parcours") !== false) {
                    $competencyObj->accentColor = 'yellow';
                }
                $competencyObj->todo = true;
                $competencyObj->progress = false;
                $competencyObj->subcompetencies = array();

                // Récupérer les sous-compétences
                $subcompetencies = $DB->get_records('competency', array('parentid' => $competency->id));

                foreach ($subcompetencies as $subcompetency) {
                    $subcompetencyObj = new stdClass();
                    $subcompetencyObj->id = $subcompetency->id;
                    $subcompetencyObj->shortname = $subcompetency->shortname;
                    $subcompetencyObj->idnumber = $subcompetency->idnumber;
                    $subcompetencyObj->description = $subcompetency->description;
                    $subcompetencyObj->descriptionformat = $subcompetency->descriptionformat;
                    $subcompetencyObj->sortorder = $subcompetency->sortorder;
                    $subcompetencyObj->parentid = $subcompetency->parentid;
                    $subcompetencyObj->path = $subcompetency->path;
                    $subcompetencyObj->progress = false;
                    $subcompetencyObj->category = $competencyObj->shortname;
                    $subcompetencyObj->todo = true;


                    $competencyObj->subcompetencies[] = $subcompetencyObj;
                }

                $frameworkObj->competencies[] = $competencyObj;
            }

            $result = $frameworkObj;
        }

        return json_encode($result);
    }

    public static function get_competencies_framework_returns()
    {
        return new external_value(PARAM_RAW, 'Competencies Framework data in JSON format');
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

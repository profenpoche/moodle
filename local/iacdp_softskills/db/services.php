<?php
// This file defines external services for the plugin.
// It must be placed in the root folder of the plugin.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_iacdp_softskills_get_competencies' => [
        'classname'   => 'local_iacdp_softskills_external',
        'methodname'  => 'get_competencies',
        'classpath'   => 'local/iacdp_softskills/externallib.php',
        'description' => 'Get a list of competencies for a course.',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view',
    ],
];

$services = [
    'local_iacdp_softskills' => [
        'functions' => ['local_iacdp_softskills_get_competencies'],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'iacdp_softskills',
    ],
];

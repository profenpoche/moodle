<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see

/**
 * Adds admin settings for the plugin.
 *
 * @package local_helloworld
 * @category admin
 * @copyright 2020 Your Name <email@example.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_sonar_analyzer', get_string('pluginname', 'local_sonar_analyzer'));

    $settings->add(new admin_setting_heading('local_sonar_analyzer/test', '', 'TESTTEST'));

    $settings->add(new admin_setting_configtext(
        'local_sonar_analyzer/sonarqube_url',
        get_string('sonarqube_url', 'local_sonar_analyzer'),
        get_string('sonarqube_url_desc', 'local_sonar_analyzer'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_sonar_analyzer/sonarqube_token',
        get_string('sonarqube_token', 'local_sonar_analyzer'),
        get_string('sonarqube_token_desc', 'local_sonar_analyzer'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_sonar_analyzer/sonarqube_token_global',
        get_string('sonarqube_token_global', 'local_sonar_analyzer'),
        get_string('sonarqube_token_global_desc', 'local_sonar_analyzer'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_sonar_analyzer/sonarqube_project_key',
        get_string('sonarqube_project_key', 'local_sonar_analyzer'),
        get_string('sonarqube_project_key_desc', 'local_sonar_analyzer'),
        '',
        paramtype: PARAM_URL
    ));


    $ADMIN->add('localplugins', $settings);
}

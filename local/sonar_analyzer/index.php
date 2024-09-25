<?php

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_login();

require_once($CFG->dirroot . '/local/sonar_analyzer/form/upload.php');
require_once($CFG->dirroot . '/local/sonar_analyzer/lib.php');
$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/sonarqube_analyzer/index.php'));
$PAGE->set_context($context);
$PAGE->set_title('SonarQube Analyzer');
$PAGE->set_heading('Uploader et analyser un fichier');


$mform = new \local_sonar_analyzer\form\upload();

if ($fromform = $mform->get_data()) {
    $fs = get_file_storage();
    $draftitemid = file_get_submitted_draft_itemid('codefile');
    file_save_draft_area_files($draftitemid, $context->id, 'local_sonar_analyzer', 'codefile', 0);

    // Recover uploaded file
    $files = $fs->get_area_files($context->id, 'local_sonar_analyzer', 'codefile', 0, 'itemid, filepath, filename', false);
    if ($files) {
        $filePathes = [];
        foreach ($files as $file) {
            $filepath = $file->copy_content_to_temp();
            $filename = $file->get_filename();
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $newfileTempWithExt = $filepath . '.' . $extension;
            rename($filepath, $newfileTempWithExt);
            chmod($newfileTempWithExt, 0666);
            $filePathes[] = $newfileTempWithExt;
        }
        $files = join(",", $filePathes);
        // analyze code
        $sonarAnalyzer = (new SonarAnalyzer($files, $fromform))->execute();
    }
} else {
    // Form displayed for the first time or invalid form
    echo $OUTPUT->header();
    $mform->set_data(null);
    $mform->display();
    echo $OUTPUT->footer();
}

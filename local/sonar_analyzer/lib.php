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
 * Callbacks.
 *
 * @package    core_h5p
 * @copyright  2019 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class SonarAnalyzer
{
    private $filePath;
    private $config;
    public $formData;

    public function __construct($filePath, $formData)
    {
        $this->filePath = $filePath;
        $this->config = (object)[
            'url' => get_config("local_sonar_analyzer", "sonarqube_url"),
            'token' => get_config("local_sonar_analyzer", "sonarqube_token"),
            'globalToken' => get_config("local_sonar_analyzer", "sonarqube_token_global"),
            'projectKey' => get_config("local_sonar_analyzer", "sonarqube_project_key"),
        ];
        $this->formData = $formData;
    }
    // GETTERS

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getConfig()
    {
        return $this->config;
    }

    // METHODS
    public function execute()
    {

        $this->analyzeCode();
    }


    public function analyzeCode()
    {

        if (empty($this->config->url) || empty($this->config->token)) {
            return false;
        }

        // echo $this->filePath;
        $sonnarScannerLocation = "/opt/sonar-scanner/bin/sonar-scanner";
        //  $command = "/opt/sonar-scanner/bin/sonar-scanner   -Dsonar.projectKey=Verdinum   -Dsonar.sources=.   -Dsonar.host.url=http://188.165.255.153:9000   -Dsonar.login=squ_2272b4885731970e18acb36f91e90591f422fa56"
        // $command = "$sonnarScannerLocation -Dsonar.projectKey={$config->projectKey} -Dsonar.sources=$filePath -Dsonar.host.url=http://188.165.255.153:9000 -Dsonar.login={$config->token}";
        $command = "$sonnarScannerLocation -Dsonar.projectKey={$this->config->projectKey} -Dsonar.sources={$this->filePath} -Dsonar.host.url=http://188.165.255.153:9000 -Dsonar.login={$this->config->token}  -Dsonar.projectBaseDir=/var/www/moodledata/temp/files/";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $this->waitForAnalysisCompletion();
            $this->downloadReport();
        } else {
            echo "Erreur lors de l'analyse";
        }
    }

    public function waitForAnalysisCompletion()
    {
        $isProcessing = true;
        while ($isProcessing) {
            $isInProcessing = $this->isActuallyProcessing();
            if ($isInProcessing) {
                usleep(100000);
            } else {
                $isProcessing = false;
            }
        }
    }

    public function isActuallyProcessing()
    {
        $url = "http://188.165.255.153:9000/api/ce/component?component={$this->config->projectKey}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode("{$this->config->token}:")
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return count($data['queue']) > 0;
    }

    public function downloadReport()
    {
        $paramsReport = [];
        $paramsReport['enableDocx'] = isset($this->formData->enableDocx) ? 'true' : 'false';
        $paramsReport['enableMd'] = isset($this->formData->enableMd) ? 'true' : 'false';
        $paramsReport['enableXlsx'] = isset($this->formData->enableXlsx) ? 'true' : 'false';
        $paramsReport['enableCsv'] = isset($this->formData->enableCsv) ? 'true' : 'false';
        $downloadReport = "http://188.165.255.153:9000/api/cnesreport/report?" . http_build_query($paramsReport) . "&key={$this->config->projectKey}&language=fr_FR&author=Administrator&token={$this->config->token}&generation=Generate";
        $downloadReport = str_replace("amp;", "&", $downloadReport);
        header("Location: $downloadReport");
    }
}

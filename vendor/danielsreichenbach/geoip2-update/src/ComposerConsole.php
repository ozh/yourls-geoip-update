<?php
/*
 * This file is part of danielsreichenbach\GeoIP2Update.
 *
 * (c) Andrey Tronov
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace danielsreichenbach\GeoIP2Update;

/**
 * These libraries are included in the Composer assembly
 * and do not need to be included as a dependency when updating databases through Composer.
 */

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class ComposerClient
 * @package danielsreichenbach\GeoIP2Update
 */
class ComposerConsole extends Client
{
    protected $_client = 2;
    /**
     * {@inheritdoc }
     */
    protected function download($remoteEditionData)
    {
        $editionId = $remoteEditionData['edition_id'];
        $progressBar = new ProgressBar((new ConsoleOutput()), 100);
        $progressBar->setFormat("  - Downloading <fg=green>$editionId</>: [%bar%] %percent:3s%%");
        $progressBar->setRedrawFrequency(1);
        $progressBar->start();
        $progressBarFinish = false;

        $ch = curl_init(trim($this->_baseUrlApi, '/') . '/' . 'geoip/databases/' . $editionId . '/download' . '?' . http_build_query(array(
            'date' => date_create($remoteEditionData['date'])->format('Ymd'),
            'suffix' => 'tar.gz'
        )));
        $fh = fopen($this->getArchiveFile($remoteEditionData), 'wb');
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FILE => $fh,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => sprintf("%s:%s", $this->account_id, $this->license_key),
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0, $uploaded2 = 0) use ($progressBar, &$progressBarFinish, $remoteEditionData) {
                if ($download_size && !$progressBarFinish)
                    if ($downloaded < $download_size)
                        $progressBar->setProgress(round(($downloaded / $download_size) * 100, 0, PHP_ROUND_HALF_DOWN));
                    else {
                        $progressBar->finish();
                        $progressBarFinish = true;
                        echo PHP_EOL;
                    }
            }
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);
        if ($response === false || $httpCode !== 200) {
            if (is_file($this->getArchiveFile($remoteEditionData)))
                unlink($this->getArchiveFile($remoteEditionData));
            $this->_errorUpdateEditions[$remoteEditionData['edition_id']] = "$editionId: download error. Remote server response code \"$httpCode\".";
            echo PHP_EOL;
        }
    }
}

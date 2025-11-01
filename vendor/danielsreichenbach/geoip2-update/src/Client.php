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
 * Class Client
 * @package danielsreichenbach\GeoIP2Update
 */
class Client
{
    const ARCHIVE_GZ = 'tar.gz';
    const ARCHIVE_ZIP = 'zip';

    /**
     * @var string Your account’s unique id on www.maxmind.com
     * @link https://support.maxmind.com/account-faq/license-keys/where-do-i-find-my-license-key/
     */
    public $account_id;

    /**
     * @var string Your account’s actual license key on www.maxmind.com
     * @link https://support.maxmind.com/account-faq/license-keys/where-do-i-find-my-license-key/
     */
    public $license_key;

    /**
     * @var string[] Database editions list to update.
     * @link https://www.maxmind.com/en/accounts/current/geoip/downloads/
     */
    public $editions = array(
        'GeoLite2-ASN',
        'GeoLite2-City',
        'GeoLite2-Country',
    );

    /**
     * @var string Destination directory. Directory where your copies of databases are stored. Your old databases will be updated there.
     */
    public $dir;

    protected $_editionVersions = array();
    protected $_baseUrlApi = 'https://download.maxmind.com';
    protected $_updated = array();
    protected $_errors = array();
    protected $_errorUpdateEditions = array();
    protected $_lastModifiedStorageFileName = 'VERSION.txt';
    protected $_client = 1;
    protected $_client_version = '2.3.1';

    public function __construct(array $params)
    {
        $this->setConfParams($params);
        $thisClass = new \ReflectionClass($this);
        foreach ($params as $key => $value)
            if ($thisClass->hasProperty($key) && $thisClass->getProperty($key)->isPublic())
                $this->$key = $value;
            else
                $this->_errors[] = "The \"{$key}\" parameter does not exist. Just remove it from the options. See https://www.geodbase-update.com";

        $this->editions = array_unique((array)$this->editions);
    }

    /**
     * Update info.
     * @return array
     */
    public function updated()
    {
        return $this->_updated;
    }

    /**
     * Update errors.
     * @return array
     */
    public function errors()
    {
        return array_merge($this->_errors, array_values($this->_errorUpdateEditions));
    }

    /**
     * Database update launcher.
     * @throws \Exception
     */
    public function run()
    {
        if (!$this->validate())
            return;

        foreach ($this->editions as $editionId)
            $this->updateEdition($editionId);
    }

    protected function setConfParams(&$params)
    {
        if (array_key_exists('geoipConfFile', $params)) {
            if (is_file($params['geoipConfFile']) && is_readable($params['geoipConfFile'])) {
                $confParams = array();
                foreach (file($params['geoipConfFile']) as $line) {
                    $confString = trim($line);
                    if (preg_match('/^\s*(?P<name>LicenseKey|EditionIDs|AccountID)\s+(?P<value>([\w-]+\s*)+)$/', $confString, $matches)) {
                        $confParams[$matches['name']] = $matches['name'] === 'EditionIDs'
                            ? array_values(array_filter(explode(' ', $matches['value']), function ($val) {
                                return trim($val);
                            }))
                            : trim($matches['value']);
                    }
                }
                $this->account_id = !empty($confParams['AccountID']) ? $confParams['AccountID'] : $this->account_id;
                $this->license_key = !empty($confParams['LicenseKey']) ? $confParams['LicenseKey'] : $this->license_key;
                $this->editions = !empty($confParams['EditionIDs']) ? $confParams['EditionIDs'] : $this->editions;
            } else {
                $this->_errors[] = 'The geoipConfFile parameter was specified, but the file itself is missing or unreadable.';
            }
            unset($params['geoipConfFile']);
        }
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        if (!empty($this->_errors))
            return false;

        switch (true) {
            case empty($this->dir):
                $this->_errors[] = 'Destination directory not specified.';
                break;
            case !is_dir($this->dir):
                $this->_errors[] = "The destination directory \"{$this->dir}\" does not exist.";
                break;
            case !is_writable($this->dir):
                $this->_errors[] = "The destination directory \"{$this->dir}\" is not writable.";
        }

        if (empty($this->account_id))
            $this->_errors[] = 'You must specify your Maxmind "account_id".';

        if (empty($this->license_key))
            $this->_errors[] = 'You must specify your Maxmind "license_key".';

        if (empty($this->editions))
            $this->_errors[] = "No GeoIP revision names are specified for the update.";

        if (!empty($this->_errors))
            return false;

        return true;
    }

    /**
     * @param string $editionId
     * @throws \Exception
     */
    protected function updateEdition($editionId)
    {
        $remoteEditionData = $this->getRemoteEditionData($editionId);

        if (!empty($this->_errorUpdateEditions[$editionId]))
            return;

        $remoteActualVersion = date_create($remoteEditionData['date']);

        $localEditionData = is_file($this->getEditionDirectory($editionId) . DIRECTORY_SEPARATOR . $this->_lastModifiedStorageFileName) ?
            file_get_contents($this->getEditionDirectory($editionId) . DIRECTORY_SEPARATOR . $this->_lastModifiedStorageFileName) : '';

        $currentVersion = date_create_from_format('Y-m-d', $localEditionData) ?: 0;

        $this->_editionVersions[$editionId] = array(!empty($currentVersion) ? $currentVersion->format('Ymd') : 0, $remoteActualVersion->format('Ymd'));

        if (empty($currentVersion) || $currentVersion < $remoteActualVersion) {

            $this->download($remoteEditionData);
            if (!empty($this->_errorUpdateEditions[$editionId]))
                return;

            $this->extract($remoteEditionData);
            if (!empty($this->_errorUpdateEditions[$editionId]))
                return;

            $this->_updated[] = "$editionId has been updated.";
        } else
            $this->_updated[] = "$editionId does not need to be updated.";
    }

    /**
     * @param $editionId
     * @return string
     */
    protected function getEditionDirectory($editionId)
    {
        return $this->dir . DIRECTORY_SEPARATOR . $editionId;
    }

    /**
     * @param string $editionId
     * @return array
     */
    protected function getRemoteEditionData($editionId)
    {
        $ch = curl_init(trim($this->_baseUrlApi, '/') . '/' . 'geoip/updates/metadata' . '?' . http_build_query([
            'edition_id' => $editionId,
        ]));
        curl_setopt_array($ch, array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => sprintf("%s:%s", $this->account_id, $this->license_key),
        ));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (empty($httpCode)) {
            $this->_errorUpdateEditions[$editionId] = "The remote server is not available.";
            return array();
        }

        $resultArray = json_decode($result, true);

        if ($httpCode !== 200) {
            $this->_errorUpdateEditions[$editionId] = $resultArray['error'] ?: $resultArray['error'];
            return array();
        }

        return $resultArray['databases'][0];
    }

    /**
     * @param array $remoteEditionData
     */
    protected function download($remoteEditionData)
    {
        $ch = curl_init(trim($this->_baseUrlApi, '/') . '/' . 'geoip/databases/' . $remoteEditionData['edition_id'] . '/download' . '?' . http_build_query(array(
            'date' => date_create($remoteEditionData['date'])->format('Ymd'),
            'suffix' => 'tar.gz'
        )));
        $fh = fopen($this->getArchiveFile($remoteEditionData), 'wb');
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
            CURLOPT_FILE => $fh,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => sprintf("%s:%s", $this->account_id, $this->license_key),
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);
        if ($response === false || $httpCode !== 200) {
            if (is_file($this->getArchiveFile($remoteEditionData)))
                unlink($this->getArchiveFile($remoteEditionData));
            $this->_errorUpdateEditions[$remoteEditionData['id']] = "Download error: ($httpCode)" . curl_error($ch);
        }
    }

    protected function getArchiveFile($remoteEditionData)
    {
        return $this->dir . DIRECTORY_SEPARATOR . $remoteEditionData['edition_id'] . '.' . 'tar.gz';
    }

    /**
     * @param array $remoteEditionData
     */
    protected function extract($remoteEditionData)
    {
        $phar = new \PharData($this->getArchiveFile($remoteEditionData));
        $phar->extractTo($this->dir, null, true);

        unlink($this->getArchiveFile($remoteEditionData));

        if (!is_dir($this->getEditionDirectory($remoteEditionData['edition_id'])))
            mkdir($this->getEditionDirectory($remoteEditionData['edition_id']));

        $directories = new \DirectoryIterator($this->dir);
        foreach ($directories as $directory)
            /* @var \DirectoryIterator $directory */
            if ($directory->isDir() && preg_match('/^' . $remoteEditionData['edition_id'] . '[_\d]+$/i', $directory->getBasename())) {
                $newEditionDirectory = new \DirectoryIterator($directory->getPathname());
                foreach ($newEditionDirectory as $item)
                    if ($item->isFile())
                        rename($item->getPathname(), $this->getEditionDirectory($remoteEditionData['edition_id']) . DIRECTORY_SEPARATOR . $item->getBasename());
                file_put_contents($this->getEditionDirectory($remoteEditionData['edition_id']) . DIRECTORY_SEPARATOR . $this->_lastModifiedStorageFileName, $remoteEditionData['date']);
                $this->deleteDirectory($directory->getPathname());
                break;
            }
    }

    /**
     * @param string $directoryPath
     */
    protected function deleteDirectory($directoryPath)
    {
        if (is_dir($directoryPath)) {
            $directory = new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS);
            $children = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($children as $child)
                /* @var \RecursiveDirectoryIterator $child */
                $child->isDir() ? rmdir($child) : unlink($child);
            rmdir($directoryPath);
        }
    }
}

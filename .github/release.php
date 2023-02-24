<?php

use splitbrain\phpcli\Colors;

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../');
require_once(DOKU_INC . 'vendor/autoload.php');
require_once DOKU_INC . 'inc/load.php';

class Release extends splitbrain\phpcli\CLI
{
    // base URL to fetch raw files from the stable branch
    protected $BASERAW = 'https://raw.githubusercontent.com/splitbrain/dokuwiki/stable/';

    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('This tool is used to gather and check data for building a release');

        $options->registerCommand('new', 'Get environment for creating a new release');
        $options->registerOption('type', 'The type of release to build', null, 'stable|rc', 'new');
        $options->registerOption('date', 'The date to use for the version. Defaults to today', null, 'YYYY-MM-DD', 'new');
        $options->registerOption('name', 'The codename to use for the version. Defaults to the last used one', null, 'codename', 'new');

        $options->registerCommand('current', 'Get environment of the current release');
    }

    protected function main(\splitbrain\phpcli\Options $options)
    {
        switch ($options->getCmd()) {
            case 'new':
                $this->prepareNewEnvironment($options);
                break;
            case 'current':
                $this->prepareCurrentEnvironment($options);
                break;
            default:
                echo $options->help();
        }
    }

    /**
     * Prepare environment for the current branch
     */
    protected function prepareCurrentEnvironment(\splitbrain\phpcli\Options $options)
    {
        $current = $this->getLocalVersion();

        // output to be piped into GITHUB_ENV
        foreach ($current as $k => $v) {
            echo "current_$k=$v\n";
        }
    }

    /**
     * Prepare environment for creating a new release
     */
    protected function prepareNewEnvironment(\splitbrain\phpcli\Options $options)
    {
        $current = $this->getUpstreamVersion();

        // continue if we want to create a new release
        $next = [
            'type' => $options->getOpt('type'),
            'date' => $options->getOpt('date'),
            'codename' => $options->getOpt('name'),
        ];
        if (!$next['type']) $next['type'] = 'stable';
        if (!$next['date']) $next['date'] = date('Y-m-d');
        if (!$next['codename']) $next['codename'] = $current['codename'];
        $next['codename'] = ucwords(strtolower($next['codename']));

        if (!in_array($next['type'], ['stable', 'rc'])) {
            throw new \splitbrain\phpcli\Exception('Invalid release type, use release or rc');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $next['date'])) {
            throw new \splitbrain\phpcli\Exception('Invalid date format, use YYYY-MM-DD');
        }

        if ($current['date'] > $next['date']) {
            throw new \splitbrain\phpcli\Exception('Date must be equal or later than the last release');
        }

        if ($current['type'] === 'stable' && $current['codename'] == $next['codename']) {
            throw new \splitbrain\phpcli\Exception('Codename must be different from the last release');
        }

        $next['update'] = intval($current['update']) + 1;
        $next['version'] = $next['date'] . ($next['type'] === 'rc' ? 'rc' : '');
        $next['raw'] = ($next['type'] === 'rc' ? 'rc' : '') . $next['date'] . ' "' . $next['codename'] . '"';

        // output to be piped into GITHUB_ENV
        foreach ($current as $k => $v) {
            echo "current_$k=$v\n";
        }
        foreach ($next as $k => $v) {
            echo "next_$k=$v\n";
        }
    }

    /**
     * Get current version info from local VERSION file
     *
     * @return string[]
     */
    protected function getLocalVersion()
    {
        return \dokuwiki\Info::parseVersionString(trim(file_get_contents('VERSION')));
    }

    /**
     * Get current version info from stable branch
     *
     * @return string[]
     * @throws Exception
     */
    protected function getUpstreamVersion()
    {
        // basic version info
        $versioninfo = \dokuwiki\Info::parseVersionString(trim(file_get_contents($this->BASERAW . 'VERSION')));

        // update version grepped from the doku.php file
        $doku = file_get_contents($this->BASERAW . 'doku.php');
        if (!preg_match('/\$updateVersion = "(\d+(\.\d+)?)";/', $doku, $m)) {
            throw new \Exception('Could not find $updateVersion in doku.php');
        }
        $versioninfo['update'] = floatval($m[1]);

        return $versioninfo;
    }


}

(new Release())->run();

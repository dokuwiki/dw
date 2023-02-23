<?php

use splitbrain\phpcli\Colors;

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../');
require_once(DOKU_INC . 'vendor/autoload.php');
require_once DOKU_INC . 'inc/load.php';

class Release extends splitbrain\phpcli\CLI
{
    // base URL to fetch raw files from the stable branch
    protected $BASERAW = 'https://raw.githubusercontent.com/splitbrain/dokuwiki/stable/';

    // overwrite icons with github workflow commands
    protected $loglevel = [
        'debug' => ['::debug:: ', Colors::C_RESET, STDOUT],
        'info' => ['::notice:: ', Colors::C_CYAN, STDOUT],
        'notice' => ['::notice:: ', Colors::C_CYAN, STDOUT],
        'success' => ['', Colors::C_GREEN, STDOUT],
        'warning' => ['::warning:: ', Colors::C_BROWN, STDERR],
        'error' => ['::error:: ', Colors::C_RED, STDERR],
        'critical' => ['::error:: ', Colors::C_LIGHTRED, STDERR],
        'alert' => ['::error:: ', Colors::C_LIGHTRED, STDERR],
        'emergency' => ['::error:: ', Colors::C_LIGHTRED, STDERR],
    ];

    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('This tool is used to gather and check data for building a release');

        $options->registerOption('type', 'The type of release to build', null, 'stable|rc');
        $options->registerOption('date', 'The date to use for the version. Defaults to today', null, 'YYYY-MM-DD');
        $options->registerOption('name', 'The codename to use for the version. Defaults to the last used one', null, 'codename');
    }

    protected function main(\splitbrain\phpcli\Options $options)
    {
        $current = $this->getCurrentVersion();

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
     * Get current version info from stable branch
     *
     * @return string[]
     * @throws Exception
     */
    protected function getCurrentVersion()
    {
        // basic version info
        $versioninfo = \dokuwiki\Info::parseVersionString(file_get_contents($this->BASERAW . 'VERSION'));

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

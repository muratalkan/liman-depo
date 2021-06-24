<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;

class PackageController
{
    public static function verifyInstallation()
    {
        $packageArr = ["'apt-mirror'", "'reprepro'", "'apache2'"];

        $check = (bool) Command::runSudo(
            "dpkg -s ".implode(' ', $packageArr)." 2>/dev/null 1>/dev/null && echo 1 || echo 0"
        );

        return $check;
    }

    function install()
    {
        return respond(
            view('components.task', [
                'onFail' => 'onTaskFail',
                'onSuccess' => 'onTaskSuccess',
                'tasks' => [
                    0 => [
                        'name' => 'InstallPackage',
                        'attributes' => []
                    ]
                ]
            ]),
            200
        );
    }
}


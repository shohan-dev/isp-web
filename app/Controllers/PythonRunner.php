<?php

namespace App\Controllers;

class PythonRunner extends BaseController
{
    public function run()
    {
        $pythonPath = "C:\\Program Files\\Python313\\python.exe";
        $scriptDir = ROOTPATH . 'app\\Views\\olt_brands';
        $scriptName = 'avies_olt.py';
        // $scriptName = 'bdcom_olt.py';
        // $scriptName = 'corelink_olt.py';
        // $scriptName = 'bdcom_olt.py';
        $argument = "status";
        // $argument = "rx:ONU02/11";

        // Navigate to the folder first, then run Python. 
        // This fixes issues where Python can't find its own 'token.txt'
        $command = "cd /d \"$scriptDir\" && \"$pythonPath\" $scriptName $argument 2>&1";

        $output = shell_exec($command);

        log_message('info', "Python Debug Output: " . $output);
        return $output;
    }
}

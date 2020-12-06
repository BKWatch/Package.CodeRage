<?php

/**
 * Defines the class CodeRage\Util\Os
 *
 * File:        CodeRage/Util/System.php
 * Date:        Thu Sep 17 02:41:06 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;

/**
 * Container for static methods providing access to operating system services
 */
final class Os {

    /**
     * Returns 'windows' or 'posix', depending on the current operating system.
     *
     * @return string
     */
    public static function type()
    {
        static $os;
        if (!$os)
            $os = strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0 ?
                'windows' :
                'posix';
        return $os;
    }


    /**
     * Version of built-in system() function that throws on error and returns
     * the complete output of the executed command.
     *
     * @param string $command The command to execute
     * @param int $exitStatus Receives the exist status, if sepcified
     * @return string The command output
     * @throws Exception if the exit status is non-zero and $exitStatus is
     *   not supplied
     */
    public static function run($command, &$exitStatus = null)
    {
        $args = func_get_args();
        $throwOnError = count($args) == 1;
        ob_start();
        $status = null;
        passthru($command, $status);
        $output = ob_get_contents();
        ob_end_clean();
        if ($status != 0 && $throwOnError) {
            $message = "Failed executing \"$command\"";
            $output = strlen($output) <= 1000 ?
                $output :
                substr($output, 0, 1000) . ' ...';
            $message .= " (exit status $status) (output '$output')";
            throw new
                Error([
                    'status' => 'UNEXPECTED_BEHAVIOR',
                    'details' => $message
                ]);
        } else {
            $exitStatus = $status;
        }
        return $output;
    }

}

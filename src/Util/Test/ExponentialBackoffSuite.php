<?php

/**
 * Defines the class CodeRage\Util\Test\ExponentialBackoffSuite
 *
 * File:        CodeRage/Util/Test/ExponentialBackoffSuite.php
 * Date:        Tue May 15 00:18:28 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\ExponentialBackoff;


/**
 * Test suite for the class CodeRage\Util\ExponentialBackoff
 */
class ExponentialBackoffSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Util\Test\ExponentialBackoffSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "ExponentialBackoff Test Suite",
            "Tests the class CodeRage\Util\ExponentialBackoff"
        );
    }

    public function testInvalidAttempts1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        new ExponentialBackoff(['attempts' => 5.1]);
    }

    public function testInvalidAttempts2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        new ExponentialBackoff(['attempts' => 0]);
    }

    public function testInvalidSleep()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        new ExponentialBackoff(['sleep' => 0]);
    }

    public function testInvalidSleep2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        new ExponentialBackoff(['sleep' => 0.0]);
    }

    public function testInvalidMultiplier1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        new ExponentialBackoff(['multiplier' => 5]);
    }

    public function testInvalidMultiplier2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        new ExponentialBackoff(['multiplier' => 0.9]);
    }

    public function testStatusList1()
    {
        $this->checkStatusList([]);
    }

    public function testStatusList2()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s]);
    }

    public function testStatusList3()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s, $s]);
    }

    public function testStatusList4()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s, $s, $s]);
    }

    public function testStatusList5()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s, $s, $s, $s]);
    }

    public function testStatusList6()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s, $s, $s, $s, $s]);
    }

    public function testStatusList7()
    {
        $s = 'RETRY';
        $this->checkStatusList(['UNAUTHORIZED']);
    }

    public function testStatusList8()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s, 'UNAUTHORIZED']);
    }

    public function testStatusList9()
    {
        $s = 'RETRY';
        $this->checkStatusList([$s, $s, 'UNAUTHORIZED']);
    }

    public function testExecutionTime()
    {
        $options = ['sleep' => 0.05, 'multiplier' => 2.0, 'attempts' => 5];
        $attempts = $options['attempts'];
        for ($i = 2, $n = $attempts; $i <= $n; ++$i) {
            $sleep = $options['sleep'];
            $expected = 0;
            for ($j = 1; $j < $i; ++$j) {
                $expected += $sleep;
                $sleep *= $options['multiplier'];
            }
            $actual = $this->timeExecution($options, $i);
            $min = $expected / 2;
            $max = $expected * 2;
            if ($actual < $min || $actual > $max)
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Incorrect execution time with $i " .
                            "attempts: expected value between $min and $max " .
                            "seconds; found $actual"
                    ]);
        }
    }

    private function checkStatusList($codes, $options = [])
    {
        // Construct algorithm
        $attempts =
            $options['attempts'] ?? ExponentialBackoff::DEFAULT_ATTEMPTS;
        Args::checkKey($options, 'sleep', 'float', [
            'default' => 0.0001
        ]);
        Args::checkKey($options, 'multiplier', 'float', [
            'default' => 1.0
        ]);
        $backoff = new ExponentialBackoff($options);

        // Define arguments to execute()
        $operation =
            function() use($codes)
            {
                static $count = 0;
                $code = $codes[$count++] ?? null;
                if ($code !== null)
                    throw new Error(['status' => $code]);
                return $count;
            };
        $handler = $this->standardErrorHandler();

        // Execute algorithm
        $result = $error = null;
        try {
            $result =
                $backoff->execute($operation, $handler, 'executing operation');
        } catch (Throwable $e) {
            $error = Error::wrap($e);
        }

        // Validate result
        $firstFatal =
            Array_::find($codes, function($c) { return $c != 'RETRY'; }, [
                'returnKey' => true
            ]);
        $expectSuccess = count($codes) < $attempts && $firstFatal === null;
        if ($expectSuccess && $error !== null) {
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "Expected success after " . (count($codes) + 1) . " " .
                        "attempts; failed with status '" . $error->status() .
                        "'"
                ]);
        } elseif (!$expectSuccess && $error === null) {
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "Expected failure after " . ($firstFatal + 1) . " " .
                        "attempts; succeeded after $result attempt(s)"
                ]);
        }
        if ($result !== null && count($codes) + 1 != $result)
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "Expected success after " . (count($codes) + 1) . " " .
                        "attempts; succeeded after $result attempt(s)"
                ]);
    }

    private function timeExecution($options, $attempts)
    {
        $backoff = new ExponentialBackoff($options);
        $operation =
            function() use($attempts)
            {
                static $i = 0;
                if (++$i < $attempts)
                    throw new Exception;
            };
        $errorHandler = function($error) { return true; };
        $log = new Log;
        $start = microtime(true);
        $backoff->execute($operation, $errorHandler, 'executing operation', $log);
        return microtime(true) - $start;
    }

    private function standardErrorHandler()
    {
        return
            function($error)
            {
                return $error instanceof Error &&
                       $error->status() == 'RETRY';
            };
    }
}

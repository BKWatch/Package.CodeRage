<?php


function checkOptions($options)
{
    try {
        new CodeRage\Log\Provider\Syslog($options);
        echo "OK\n";
    } catch (CodeRage\Error $e) {
        echo "Failed checking options '" . json_encode($options) . ': ' . $e->details() . "\n";
    }
}

function checkBadOptions($options)
{
    try {
        new CodeRage\Log\Provider\Syslog($options);
        echo "Failed checking bad options '" . json_encode($options) . "\n";
    } catch (CodeRage\Error $e) {
        echo "OK\n";

        // Fall trhough
    }
}

checkBadOptions(['unsupported' => 9]);
checkOptions(['ident' => 'api.bk.watch']);
checkBadOptions([['ident' => new DateTime]]);
checkOptions(['options' => 'LOG_ODELAY, LOG_PID']);
checkBadOptions([['options' => 'LOG_WHATEVER']]);
checkBadOptions([['options' => 'LOG_ODELAY,LOG_WHATEVER']]);
checkBadOptions([['options' => 'LOG_ODELAY,LOG_ODELAY']]);
checkOptions(['facility' => 'LOG_USER']);
checkBadOptions([['facility' => 'LOG_WHATEVER']]);
checkOptions(['exclude' => 'timestamp,data']);
checkBadOptions(['exclude' => -1000]);
checkBadOptions(['exclude' => '   ']);
checkBadOptions(['exclude' => 'fonzy']);
checkBadOptions(['exclude' => 'timestamp,fonzy']);
checkBadOptions(['exclude' => 'timestamp,level,timestamp']);
checkOptions(['custom' => 'application: MetalMG, version: 5.6.3']);
checkOptions(['custom' => 'application: "Hello World", version: 5.6.3']);
checkBadOptions(['custom' => new \DateTime]);
checkBadOptions(['custom' => '"']);
checkBadOptions(['custom' => 'hello']);
checkBadOptions(['custom' => 'tags:hello']);
checkBadOptions(['custom' => 'url: dave']);
checkBadOptions(['custom' => 'firstName:Dave,lastName:Ozwald,firstName:Dave']);

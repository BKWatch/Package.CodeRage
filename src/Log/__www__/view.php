<?php

/**
 * Displays log messages
 *
 * File:        CodeRage/Log/__www__/view.php
 * Date:        Sun May 17 04:48:18 UTC 2015
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

require __DIR__ . '/../../../vendor/autoload.php';

const MATCH_URL = '/\b((https?|ftp):\/\/[A-Z0-9+&@#\/%?=~_|!:,.;-]*[-A-Z0-9+&@#\/%=~_|])/im';

if (!isset($_GET['session'])) {
    echo 'Missing session ID';
    exit;
}
if (!preg_match('/^([a-zA-Z0-9]+)$/', $_GET['session'])) {
    echo 'Invalid session ID: ' . htmlspecialchars($_GET['session']);
    exit;
}

$db = new \CodeRage\Db;
$sql =
    "SELECT FROM_UNIXTIME(e.CreationDate) AS Timestamp, e.level, e.message
     FROM LogEntry e
     JOIN LogSession s
       ON e.sessionid = s.RecordID
     WHERE s.id = %s";
$rows = $db->fetchAll($sql, $_GET['session']);
if (empty($rows)) {
    echo 'No such session: ' . htmlspecialchars($_GET['session']);
    exit;
}

?>
<html>
<head>
<style type='text/css'>

body {
  font-family: sans-serif;
}

pre {
    padding: 4px;
    margin: 0;
}

td, td.critical, td.error, td.warning {
    margin: 4px;
}

td.timestamp {
    padding-right: 12px;
    vertical-align: top;
}

td.info, td.verbose, td.debug { }

td.warning {
    background-color: #fcefd2;
    border: 1px solid black;
}

td.error {
    background-color: #fccfcf;
    border: 1px solid black;
}

td.critical {
    background-color: #fccfcf;
    border: 2px solid black;
    font-weight: bold;
}

</style>
</head>
<body>
<table>
<thead style='text-align:left;'>
<tr>
    <th>Time</th>
    <th>Message</th>
</tr>
</thead>
<tbody>

<?php

foreach ($rows as $row) {
    list($timestamp, $level, $message) = $row;
    $level = (int)$level; ;
    $class = strtolower(\CodeRage\Log::translateLevel($level));
    $message = htmlspecialchars(trim($message), ENT_NOQUOTES);
    $message =
        preg_replace_callback(
            MATCH_URL,
            function($m)
            {
                return "<a href='{$m[1]}'>{$m[1]}</a>";
            },
            $message
        );
?>

<tr>
    <td class='timestamp'><pre><?= $timestamp ?></pre></td>
    <td class='<?= $class ?>'><pre><?= $message ?></pre></td>
</tr>

<?php } ?>
</tbody>
</table>
</body>
</html>

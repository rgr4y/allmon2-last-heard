<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

$keyed = [];

// Allstar Hubs & also ignored below
$hubs = [
    1200,
    1300,
    2560,
    27084,
    2545,
    2353
];

// Intentionally ignored stations because they're IRLP
$ignore = array_merge($hubs, [
    1001
]);

$buffer = '';
$stream = fopen(getAllMonUri(), "r");

while (!feof($stream)) {
    $buffer .= stream_get_contents($stream, 1024);

    if (preg_match("/event: nodes\ndata: (.*)\n\n/m", $buffer, $matches)) {
        // echo $matches[1]."\n\n######################################\n\n";
        parseStreamData($matches[1]);
        $buffer = '';
    }
}

echo "DIE!!!!\n";
exit;

/**
 * @param $json
 */
function parseStreamData($json)
{
    global $keyed;

    $obj = json_decode($json);

    foreach ($obj as $node => $v) {
        foreach ($v->remote_nodes as $rn) {
            $node = intval($rn->node);
            if (isIgnored($node)) continue;
            
            // Capture previous keyed values
            $keyedNow = $rn->keyed === "yes";
            $keyedBefore = isset($keyed[$node]);
            
            // Set current key state. In PHP null !== isset
            $keyed[$node] = $keyedNow ?: null;

            if ($keyedBefore !== $keyedNow) {
                // Permanently set to Allstar for now
                // $nodePrefix = isIrlp($node) ? 'stn' : 'rpt';
                $nodePrefix = 'rpt';
                $keyedLabel = $keyedNow ? "KEY" : "UNKEY";
                $time = Carbon::now();
                $timeFormatted = $time->format("M d h:i:s");
                // echo "{$node},{$keyedLabel},{$time}\n";
                echo "{$timeFormatted} $nodePrefix{$node} {$keyedLabel} tx\n";
            }
        }
    }
}

/**
 * @param $node
 * @return bool
 */
function isIgnored($node){
    global $ignore;
    return in_array($node, $ignore);
}

/**
 * @return string
 */
function getAllMonUri()
{
    global $hubs;
    $hubsStr = implode(",", $hubs);
    // return "https://allmon.winsystem.org/server.php?nodes=2353";
    // return __DIR__ . '/test.stream';
    return "https://allmon.winsystem.org/server.php?nodes=" . $hubsStr;
}
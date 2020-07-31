<?php

$cmd = $_GET['cmd'] ?? null;
$node = intval($_GET['node'] ?? null);
$type = intval($_GET['type'] ?? null);

if ($cmd === "log" || $cmd == "logText") {
    $data = file_get_contents("http://www3.winsystem.org/monitor/ajax-logtail.php");
    
    // Just output the raw data
    if ($cmd === "log") {
        echo $data;
        return;
    }
} else if ($cmd === "node") {
    if (empty($node)) return;
    
    if ($type === 1) {
        $info = fetchNodeInfoAllstar($node);
    } else if ($type === 2) { 
        $info = fetchNodeInfoIRLP($node);
    }
    
    echo json_encode($info, JSON_UNESCAPED_SLASHES);
}

return;

function fetchNodeInfoAllstar($node) {
    $db = fetchAllStarDb();
    
    $db = explode("\n", $db);
    foreach ($db as $row) {
        $row = explode("|", $row);
        if (intval($row[0]) !== $node) continue;
        
        return [
            'node' => $node,
            'callsign' => $row[1],
            'desc' => $row[2],
            'location' => $row[3]
        ];
    }
    
    return [
        'node' => $node
    ];
}

function fetchNodeInfoIRLP($node) {
    $db = fetchIRLPDb();
    $db = explode("\n", $db);
    
    foreach ($db as $row) {
        $line = str_getcsv($row, "\t");
        
        if ($line[0] != $node) continue;
        
        return [
            'node' => $node,
            'callsign' => $line[1],
            'desc' => $line[15],
            'location' => "{$line[2]}, {$line[3]}"
        ];
    }
    
    return [
        'node' => $node,
        'callsign' => $node,
        'desc' => '',
        'location' => ''
    ];
}

function fetchAllStarDb() {
    $dbFile = __DIR__.'/storage/allstar.txt';
    if (!file_exists($dbFile)) {
        $db = file_get_contents("http://allmondb.allstarlink.org");
        file_put_contents($dbFile, $db);
        return $db;
    }
    
    return file_get_contents($dbFile);
}

function fetchIRLPDb() {
    $dbFile = __DIR__.'/storage/irlp.txt';
    
    if (!file_exists($dbFile)) {
        $db = file_get_contents("http://status.irlp.net/nohtmlstatus.txt.zip");
        file_put_contents("${dbFile}.zip", $db);
        shell_exec("/usr/bin/unzip -p ${dbFile}.zip > ${dbFile}");
    }
    
    return file_get_contents($dbFile);
}


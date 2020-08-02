<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

/**
 * Init class and let the constructor take care of the rest 
 */
new Stream();
echo "DEAD!\n";

class Stream
{
    /**
     * Allstar Hubs & also ignored below
     * @var array 
     */
    protected $hubs = [
        1200,
        1300,
        2560,
        2353,
        // Offline 07-31-2020
        // 27084,
        2545,
    ];

    /**
     * Intentionally ignored stations because they're IRLP
     * @var array 
     */
    protected $ignore = [
        // 1001
    ];

    /**
     * Actively keyed up stations
     * @var array 
     */
    protected $keyed = [];

    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * @var false|resource 
     */
    protected $stream;

    /**
     * @var string 
     */
    protected $streamOutput = __DIR__.'/storage/stream.txt';
    
    /**
     * Streamed constructor.
     */
    public function __construct()
    {
        /**
         * Merge together hubs and IRLP so we don't show them in the keyups
         *
         * Since we're reading a continuous stream from allmon, there's no need for sleep --
         * loop and read the stream until it dies.
         *  
         */
        $this->ignore = array_merge($this->hubs, $this->ignore);
        $this->streamLoop();
    }

    /**
     * 
     */
    protected function openStream() {
        $this->stream = fopen($this->getAllMonUri(), "r");
    }
    
    /**
     * @return string
     */
    public function getAllMonUri()
    {
        $hubsStr = implode(",", $this->hubs);
        // return "https://allmon.winsystem.org/server.php?nodes=2353";
        // return __DIR__ . '/test.stream';
        return "https://allmon.winsystem.org/server.php?nodes=" . $hubsStr;
        return "http://kk9rob/allmon2/server.php?nodes=52003";
    }

    /**
     * Main event loop
     */
    public function streamLoop() {
        $buffer = '';
        
        $this->openStream();

        if (!$this->stream) {
            $this->appendToStreamOutput($this->timeFormatted() . " rpt0000 KEYED [via 0000] [WINSystem Allmon Connection Failed]");
            sleep(120);
            $this->streamLoop();
        }
        
        while (!feof($this->stream)) {
            $buffer .= stream_get_line($this->stream, 2048, "\n\n");

            if (false !== strpos($buffer, '}}')) {
                $buffer .= "\n\n";
                
                if (preg_match("/event: (.*?)\ndata: (.*)\n\n/m", $buffer, $matches)) {
                    $buffer = '';
                    if ($matches[1] !== "nodes") continue;
                    $this->parseStreamData($matches[2]);
                }
            }
        }
    }

    /**
     * @param $node
     * @return bool
     */
    protected function isIgnored($node)
    {
        return in_array($node, $this->ignore);
    }
    
    /**
     * @param $json
     */
    protected function parseStreamData($json)
    {
        // Debugging -- sometimes allmon doesn't send the proper keyup
        // file_put_contents(__DIR__.'/storage/event_nodes.txt', "$json,\n", FILE_APPEND);
        $obj = json_decode($json);

        foreach ($obj as $node => $v) {
            foreach ($v->remote_nodes as $remoteNode) {
                $via  = intval($node);
                $node = $remoteNode->node;
                if ($this->isIgnored($node)) continue;

                // Capture previous keyed values
                $keyedNow = $remoteNode->keyed === "yes";
                $keyedBefore = isset($this->keyed[$node]);

                // Set current key state. In PHP null !== isset
                $this->keyed[$node] = $keyedNow ?: null;

                if ($keyedBefore !== $keyedNow) {
                    if (filesize($this->streamOutput) >= 8192) {
                        echo filesize($this->streamOutput) . "\n";
                        $so = file_get_contents($this->streamOutput);
                        $so = substr($so, 4096);
                        file_put_contents($this->streamOutput, $so);
                    }
                    
                    // Permanently set to Allstar for now
                    $nodePrefix = 'rpt';
                    $keyedLabel = $keyedNow ? "KEY" : "UNKEY";

                    $timeFormatted = $this->timeFormatted();
                    $toWrite = "{$timeFormatted} $nodePrefix{$node} {$keyedLabel} [via {$via}] [{$remoteNode->info}]";
                    $this->appendToStreamOutput($toWrite);
                    echo $toWrite;
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function timeFormatted() {
        $time = Carbon::now();
        return $time->format("M d h:i:s");
    }

    /**
     * @param $toWrite
     */
    protected function appendToStreamOutput($toWrite)
    {
        file_put_contents($this->streamOutput, $toWrite."\n", FILE_APPEND);
    }
}

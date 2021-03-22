# allmon2-last-heard

Shows which Ham Radio nodes are keyed up at one time by consuming data from Allmon/Supermon's server.php

Just plop in a directory that has a PHP web server pointing at it!

## Installation

* Edit streamServer.php and add your hubs to $hubs (line 20, separated by comma)
* Change user= and group= in stream-server.service to fit your environment 
* Copy stream-server.service to /etc/systemd/system
* Make sure /srv/http/allmon2/astdb.txt or /var/www/allmon2/astdb.txt exists or is a symlink to a working astdb.txt
* Make sure hideNodeURL=no in allmon.ini.php for the node stanza that's being monitored
* Make sure storage/ directory is writable by the PHP user (usually www-data)

```
./composer.phar install
systemctl enable stream-server
systemctl start stream-server
```

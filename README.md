# PHP Shell  [![Latest Version](https://img.shields.io/github/release/azurre/php-shell.svg?style=flat-square)](https://github.com/azurre/php-shell/releases)
This small and easy to use PHP shell make it able to run commands as non blocking asynchronous jobs 

## Installation
Install composer in your project:
```
curl -s https://getcomposer.org/installer | php
```

Require the package with composer:

```
composer require azurre/php-shell
```

## Usage

### Simple example

```php
$loader = require_once __DIR__ . '/vendor/autoload.php';

use Azurre\Component\System\Shell;

$cmd = 'ls -lwa /tmp';
echo Shell::create()->run($cmd);
```

Output
```
total 180
drwxrwxrwt 11 root     root     4096 Jul  4 23:21 .
drwxr-xr-x 22 root     root     4096 Jun 10 16:23 ..
drwxrwxrwt  2 root     root     4096 Jun 10 16:23 .ICE-unix
drwxrwxrwt  2 root     root     4096 Jun 10 16:23 .X11-unix
drwxrwxrwt  2 root     root     4096 Jun 10 16:23 .XIM-unix
drwxrwxrwt  2 root     root     4096 Jun 10 16:23 .font-unix
-rw-------  1 www-data www-data    0 Jul  1 21:21 239315d1a4f341c8b52.14168329mUCCHL
-rw-r--r--  1 www-data www-data 6468 Jul  1 21:18 416205d1a4e5fe8e166.51692428EFpYWQ
-rw-r--r--  1 www-data www-data 6469 Jul  1 21:21 443675d1a4f3ce6efd0.08860101uETZWS
-rw-r--r--  1 www-data www-data 6416 Jul  1 21:26 494485d1a503c71cfd8.3817705715MVk4
-rw-r--r--  1 www-data www-data 6468 Jul  1 21:19 519335d1a4eba5a2ef8.14398347CWkGkb
-rw-------  1 www-data www-data    0 Jul  1 21:27 575735d1a508060a080.18275551cZg15T
```

### Background tasks

```PHP
$shell = new Shell;
$shell->runAsync('ping -c 5 127.0.0.1');

echo "Start background task\n";
while($shell->isProcessRunning()) {
    echo 'Working...' .PHP_EOL;
    sleep(1);
}
echo $shell;
```

Output
```
Start background task
Working...
Working...
Working...
Working...
Working...
PING 127.0.0.1 (127.0.0.1) 56(84) bytes of data.
64 bytes from 127.0.0.1: icmp_seq=1 ttl=64 time=0.020 ms
64 bytes from 127.0.0.1: icmp_seq=2 ttl=64 time=0.022 ms
64 bytes from 127.0.0.1: icmp_seq=3 ttl=64 time=0.015 ms
64 bytes from 127.0.0.1: icmp_seq=4 ttl=64 time=0.023 ms
64 bytes from 127.0.0.1: icmp_seq=5 ttl=64 time=0.019 ms

--- 127.0.0.1 ping statistics ---
5 packets transmitted, 5 received, 0% packet loss, time 4099ms
rtt min/avg/max/mdev = 0.015/0.019/0.023/0.006 ms
```

### Error handling

```PHP
$shell = new Shell;
$shell->run('ls /A/1/2/3/4')->waitForProcess();
if ($shell->getExitCode() !== Shell::NO_ERROR) {
    echo "\nExit code: {$shell->getExitCode()}\n";
    echo "Error: {$shell->getStdError()}\n";
} else {
    echo $shell->getStdOut();
}
```

Output
```
Exit code: 2
Error: ls: cannot access '/A/1/2/3/4': No such file or directory

```

## License
[MIT](https://choosealicense.com/licenses/mit/)

# A convenient component of PHP CLI wrapper

# Simple usage

```php
use Azurre\Component\Shell\Wrapper as Shell;

$cmd = 'ls -lwa /tmp';
echo "Shell::sCmd: " . Shell::sCmd($cmd) . PHP_EOL;
echo "Shell::cmd: " . PHP_EOL;
print_r( Shell::cmd($cmd) );
```

# Background tasks

```PHP
$Shell = Shell::init()
        ->setOutput('/tmp/shell.log')
        ->setErrorOutput('/tmp/shell.log')
        ->setAsync()
        ->exec('ping -c 10 127.0.0.1');

echo "Start background task\n";
while($Shell->isProcessRunning()) {
    echo 'Working...' .PHP_EOL;
    sleep(1);
}
```

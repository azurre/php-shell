# A convenient component of PHP CLI wrapper

# Usage

```php
$cmd = 'ls -lwa /tmp';
echo "<pre>\n";
echo "Exec: " . exec($cmd) . PHP_EOL;
echo "System: " . system($cmd) . PHP_EOL;
echo "Shell::sCmd: " . Shell::sCmd($cmd) . PHP_EOL;
echo "Shell::cmd: " . PHP_EOL;
print_r( Shell::cmd($cmd) );
```

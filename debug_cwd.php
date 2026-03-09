<?php
echo "Root CWD: " . getcwd() . "\n";
echo "Root uploads path: " . realpath('uploads') . "\n";

echo "\n--- API DIR ---\n";
chdir('api');
echo "API CWD: " . getcwd() . "\n";
echo "API ../uploads realpath: " . realpath('../uploads') . "\n";
?>

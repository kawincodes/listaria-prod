<?php
$files = glob("uploads/699f*");
file_put_contents("cable_files.txt", implode("\n", $files));
echo "Found " . count($files) . " files. See cable_files.txt\n";
?>

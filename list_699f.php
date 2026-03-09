<?php
$files = glob("uploads/699f*");
echo "Matching files:\n";
foreach ($files as $f) {
    echo "$f\n";
}
?>

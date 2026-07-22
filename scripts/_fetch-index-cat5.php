<?php
$html = file_get_contents('http://localhost/iforge/index.php');
if (preg_match('/<section[^>]*id="cat_5".*?<\/section>/s', $html, $m)) {
    echo $m[0];
} else {
    echo "cat_5 not found in output\n";
}

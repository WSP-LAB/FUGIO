<?php
ini_set("highlight.comment", "#C0C0C0");
ini_set("highlight.string", "#000000");

$file_path = $_GET['file_path'];
echo "<h1>$file_path</h1>";
$text = highlight_file($file_path);
?>

<?php

require_once "./src/Amelie.php";

if (!is_dir("archives"))
    mkdir("archives");

$now = "./archives/".date("Y-m-d-H-i-s");
if (!is_dir($now))
    mkdir($now);

const FILENAME = "LPPTOT696";
if (!is_file(FILENAME))
    throw new Exception("[".FILENAME."] file does not exists");

$archived = $now.DIRECTORY_SEPARATOR.FILENAME;
copy("./".FILENAME, $archived);


$parser = new Amelie($archived, true);
$parser->parse();
$parser->build("$archived.sql");
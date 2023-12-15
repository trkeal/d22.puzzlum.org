<?php

include_once (__DIR__.'/../d22-private/php/error manager.php');
include_once (__DIR__.'/../d22-private/php/installer.php');

$csvImporter = new Csv2Json();
$csvImporter->csvFullImport('Book 000');

echo $csvImporter->clean_url('<!DOCTYPE html><html lang="en-us"><head><title>Keal\'s '.$_SERVER['HTTP_HOST'].'</title><link rel="icon" href="/Sprites/icon/crescent-onion-dome.png" type="image/png"><link rel="shortcut icon" href="/Sprites/icon/crescent-onion-dome.png" type="image/png"><link rel="stylesheet" href="/css/game.css"></head><body>'.$csvImporter->pagebuffer.'</body></html>');

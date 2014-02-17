<?php
$text = 'Привет, мир!';
$apiKey = '';

require __DIR__.'/speech.php';
$speech = new Speech($text);
$speech->setApiKey($apiKey)->generate();


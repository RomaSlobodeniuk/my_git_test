<?php

require_once './helpers/functions.php';
//$uri = ltrim($_SERVER["SCRIPT_NAME"]);
//$array = explode('/', $uri);
//$page = !empty($array[1]) ? $array[1] : 'index';

$page = !empty($_GET['page']) ? $_GET['page'] : 'index';
if ($page === 'logout') {
    logout();
}

$fileName = "./source/$page.json";
$sourceData = getSourceData($fileName);

$header = getHeader($sourceData, $page);
$mainContent = getMainContent($sourceData, $page);
$footer = getFooter($sourceData);

echo $header, $mainContent, $footer;
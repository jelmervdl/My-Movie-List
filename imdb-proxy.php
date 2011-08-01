<?php

if (!isset($_GET['url']))
  die('Missing url parameter');

$handle = fopen($_GET['url'], 'rb');

if ($handle)
{
	header('Content-Type: image/jpeg');
	fpassthru($handle);
}
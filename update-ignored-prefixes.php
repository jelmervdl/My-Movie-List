<?php

error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors', true);

require_once('_includes/main.php');

function generate_prefix_remove_sql($format, $prefixes)
{
	$sql = '';
	
	foreach ($prefixes as $prefix)
	{
		// WHEN LEFT(NEW.title, 4) = 'The ' THEN
		//   SET NEW.sort_title = SUBSTRING(NEW.title FROM 5);
		$sql .= sprintf($format,
				strlen($prefix) + 1, $prefix . ' ', strlen($prefix) + 2);
	}
	
	return $sql;
}

$ignored_prefix_sql_updates = array(
	// Create a trigger to automagically create the sort title
	"DROP TRIGGER IF EXISTS `" . DB_PREFIX ."_sort_title`",
	"CREATE TRIGGER `" . DB_PREFIX ."_sort_title` BEFORE INSERT ON `" . DB_PREFIX . "movies`
		FOR EACH ROW
		BEGIN
			CASE
			" . generate_prefix_remove_sql("
				WHEN LEFT(NEW.title, %d) = '%s' THEN
					SET NEW.sort_title = SUBSTRING(NEW.title FROM %d);
			", $ignored_prefixes) . "
				ELSE
					SET NEW.sort_title = NEW.title;
			END CASE;
		END",
	
	// Create the sort_title value for all existing movies
	"UPDATE `" . DB_PREFIX . "movies`
		SET sort_title = CASE
			" . generate_prefix_remove_sql("
			WHEN LEFT(title, %d) = '%s' THEN
				SUBSTRING(title FROM %d)
			", $ignored_prefixes) . "
			ELSE
				title
		END");

$conn = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Could not connect: ' . mysql_error());
mysql_select_db(DB_NAME, $conn) or die('Could not select database');

foreach ($ignored_prefix_sql_updates as $stmt)
{
	echo '<pre>' . $stmt . '</pre>';
	echo mysql_query($stmt, $conn) ? 'Ok' : 'Error';
	echo '<hr>';
}
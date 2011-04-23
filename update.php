<?php

error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors', true);

date_default_timezone_set('Europe/Amsterdam');

require_once('_includes/main.php');

class Update
{
	public function __construct($from_version, $to_version, array $statements)
	{
		$this->from = $from_version;
		$this->to = $to_version;
		$this->statements = $statements;
	}
	
	public function apply($conn, &$error = null)
	{
		foreach ($this->statements as $statement)
		{
			if (!mysql_query($statement, $conn))
			{
				$error = mysql_error($conn) . "\n\nQuery:\n$statement";
				return false;
			}
		}
		
		return true;
	}
}

function backup_database($db, $conn)
{
	if (!is_writable('backups'))
    die('Please make sure the backups dir is writable, or if you are really really daring or made a backup yourself, <a href="update.php?skip-backups">continue without making a backup</a>.');

  require_once '_includes/backup.class.php';

  $backup_creator = new MDB_Backup($conn);
  $backup_creator->dump(sprintf('backups/%s-%s-%s-%s.sql',
    DB_NAME, date('Y-m-d_his'), $db->getVersion(), uniqid()));
}

function update_database($db, $conn, array $updates)
{
	while (true)
	{
		$current_version = $db->getVersion();
		
		foreach ($updates as $update)
		{
			// Check if this update is of any use now
			if ($update->from != $current_version)
				continue;
			
			// When successfully applied…
			if ($update->apply($conn, $error))
			{
				$db->setVersion($update->to);
				continue 2; // …continue while loop
			}
			// Otherwise bail out.
			else
			{
				die(sprintf('Error while updating from %f to %f:<br><pre>%s</pre>',
					$update->from, $update->to, $error));
			}
		}
		
		// When no update was applied, stop trying. We are probably up to date
		// or stuck with an unupgradable version.
		break;
	}
}

$updates = array();

$updates[] = new Update(null, 1.1, array(
	"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cast` (
    `movie_id` int(7) NOT NULL,
    `name_id` int(7) NOT NULL,
    `order` int(3) NOT NULL,
    PRIMARY KEY  (`movie_id`,`name_id`),
    KEY `name_id` (`name_id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
	
	"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "crew` (
    `movie_id` int(7) NOT NULL,
    `name_id` int(7) NOT NULL,
    `order` int(3) NOT NULL,
    PRIMARY KEY  (`movie_id`,`name_id`),
    KEY `name_id` (`name_id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8",

	"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "imdbtop250` (
    `id` int(7) NOT NULL,
    `order` int(3) NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `id` (`id`),
    UNIQUE KEY `order` (`order`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8",

	"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "movies` (
    `id` int(7) NOT NULL,
    `title` varchar(255) NOT NULL,
    `title_english` varchar(255) NOT NULL,
    `language` varchar(2) NOT NULL,
    `genre` set('Action','Adventure','Animation','Biography','Comedy','Crime',
			'Documentary','Drama','Family','History','Horror','Music','Musical',
			'Mystery','Sci-Fi','Short','Sport','Talk Show','Thriller','War',
			'Western') NOT NULL,
    `year` int(4) NOT NULL,
    `runtime` int(3) NOT NULL,
    `rating` tinyint(2) NOT NULL,
    `date_added` datetime NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8",

	"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "names` (
    `id` int(7) NOT NULL,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8",

	"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "options` (
    `name` varchar(255) NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY  (`name`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8"));

$updates[] = new Update(1.1, 1.2, array(
	"ALTER TABLE `" . DB_PREFIX . "movies` CHANGE `genre` `genre`
    SET('Action','Adventure','Animation','Biography','Comedy','Crime',
    'Documentary','Drama','Family','Fantasy','Film-Noir','Game-Show','History',
    'Horror','Music','Musical','Mystery','News','Reality-TV','Romance','Sci-Fi',
    'Sport','Talk-Show','Thriller','War','Western') NOT NULL DEFAULT ''"));

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

$updates[] = new Update(1.2, 1.3, array_merge(array(
	// Add sort-title column and index
	"ALTER TABLE `" . DB_PREFIX . "movies`
		ADD `sort_title` VARCHAR(255) NULL DEFAULT NULL AFTER `title`,
		ADD INDEX `sort_index` (`sort_title`)"),
	$ignored_prefix_sql_updates
));

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

$conn = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Could not connect: ' . mysql_error());
mysql_select_db(DB_NAME, $conn) or die('Could not select database');

$db = new MDB($conn);

if (!isset($_GET['skip-backups']))
	backup_database($db, $conn);

update_database($db, $conn, $updates);

if (!error_get_last())
	header('Location: ./?message=update-success');

?>
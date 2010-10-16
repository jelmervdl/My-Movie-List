<?php

error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors', true);

date_default_timezone_set('Europe/Amsterdam');

require_once('_includes/main.php');

$conn = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Could not connect: ' . mysql_error());
mysql_select_db(DB_NAME, $conn) or die('Could not select database');

$db = new MDB($conn);

if (!isset($_GET['skip-backups']))
{
  if (!is_writable('backups'))
    die('Please make sure the backups dir is writable, or if you are really really daring or made a backup yourself, <a href="update.php?skip-backups">continue without making a backup</a>.');

  require_once '_includes/backup.class.php';

  $backup_creator = new MDB_Backup($conn);
  $backup_creator->dump(sprintf('backups/%s-%s-%s-%s.sql',
    DB_NAME, date('Y-m-d_his'), $db->getVersion(), uniqid()));
}

if (!$db->getVersion())
{
  mysql_query("CREATE TABLE `" . DB_PREFIX . "cast` (
    `movie_id` int(7) NOT NULL,
    `name_id` int(7) NOT NULL,
    `order` int(3) NOT NULL,
    PRIMARY KEY  (`movie_id`,`name_id`),
    KEY `name_id` (`name_id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", $conn);


  mysql_query("CREATE TABLE `" . DB_PREFIX . "crew` (
    `movie_id` int(7) NOT NULL,
    `name_id` int(7) NOT NULL,
    `order` int(3) NOT NULL,
    PRIMARY KEY  (`movie_id`,`name_id`),
    KEY `name_id` (`name_id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", $conn);


  mysql_query("CREATE TABLE `" . DB_PREFIX . "imdbtop250` (
    `id` int(7) NOT NULL,
    `order` int(3) NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `id` (`id`),
    UNIQUE KEY `order` (`order`),
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", $conn);


  mysql_query("CREATE TABLE `" . DB_PREFIX . "movies` (
    `id` int(7) NOT NULL,
    `title` varchar(255) NOT NULL,
    `title_english` varchar(255) NOT NULL,
    `language` varchar(2) NOT NULL,
    `genre` set('Action','Adventure','Animation','Biography','Comedy','Crime','Documentary','Drama','Family','History','Horror','Music','Musical','Mystery','Sci-Fi','Short','Sport','Talk Show','Thriller','War','Western') NOT NULL,
    `year` int(4) NOT NULL,
    `runtime` int(3) NOT NULL,
    `rating` tinyint(2) NOT NULL,
    `date_added` datetime NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", $conn);


  mysql_query("CREATE TABLE `" . DB_PREFIX . "names` (
    `id` int(7) NOT NULL,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", $conn);


  $db->setVersion(1.0);
}


if ($db->getVersion() < 1.1)
{
  mysql_query("CREATE TABLE `" . DB_PREFIX . "options` (
    `name` varchar(255) NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY  (`name`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8", $conn);
  
  $db->setVersion(1.1);
}


if ($db->getVersion() < 1.2)
{
  
  mysql_query("ALTER TABLE `" . DB_PREFIX . "movies` CHANGE `genre` `genre`
    SET('Action','Adventure','Animation','Biography','Comedy','Crime',
    'Documentary','Drama','Family','Fantasy','Film-Noir','Game-Show','History',
    'Horror','Music','Musical','Mystery','News','Reality-TV','Romance','Sci-Fi',
    'Sport','Talk-Show','Thriller','War','Western') NOT NULL DEFAULT ''", $conn);
  
  $db->setVersion(1.2);
}


header('Location: ./?message=update-success');

?>
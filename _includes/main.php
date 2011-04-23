<?php

require_once('settings.inc.php');
require_once('database.class.php');
require_once('template.class.php');

define('STEP', (isset($_REQUEST['step']) && is_numeric($_REQUEST['step'])) ? $_REQUEST['step'] : 1);
define('TASK', isset($_REQUEST['task']) ? $_REQUEST['task'] : 'default');


session_start();
define('LOGGEDIN', isset($_SESSION['LOGGEDIN']));


define('DOCUMENT_ROOT', dirname(__FILE__) . '/../');



function escape($arg) {
  if (is_numeric($arg))
    return $arg;
  else
    return "'" . mysql_real_escape_string($arg) . "'";
}



$modernLanguages = array(
  'aa' => 'Afar',
  'ab' => 'Abkhazian',
  'ae' => 'Avestan',
  'af' => 'Afrikaans',
  'am' => 'Amharic',  
  'an' => 'Aragonese',
  'ar' => 'Arabic',
  'as' => 'Assamese',
  'av' => 'Avaric',
  'ba' => 'Bashkir',
  'be' => 'Belarusian',
  'bg' => 'Bulgarian',
  'bh' => 'Bihari',
  'bi' => 'Bislama',
  'bm' => 'Bambara',
  'bn' => 'Bengali',
  'bo' => 'Tibetan',
  'br' => 'Breton',
  'bs' => 'Bosnian',
  'ca' => 'Catalan',
  'ce' => 'Chechen',
  'ch' => 'Chamorro',
  'co' => 'Corsican',
  'cs' => 'Czech',
  'cu' => 'Church Slavic',
  'cv' => 'Chuvash',
  'cy' => 'Welsh',
  'da' => 'Danish',
  'de' => 'German',
  'dv' => 'Divehi',
  'dz' => 'Dzongkha',
  'ee' => 'Ewe',
  'el' => 'Modern Greek',
  'en' => 'English',
  'eo' => 'Esperanto',
  'es' => 'Spanish',
  'eu' => 'Basque',
  'fi' => 'Finnish',
  'fj' => 'Fijian',
  'fo' => 'Faroese',
  'fr' => 'French',
  'fy' => 'Western Frisian',
  'ga' => 'Irish',
  'gd' => 'Gaelic',
  'gl' => 'Galician',
  'gu' => 'Gujarati',
  'gv' => 'Manx',
  'ha' => 'Hausa',
  'he' => 'Hebrew',
  'hi' => 'Hindi',
  'ho' => 'Hiri Motu',
  'hr' => 'Croatian',
  'ht' => 'Haitian',
  'hu' => 'Hungarian',
  'hy' => 'Armenian',
  'hz' => 'Herero',
  'ia' => 'Interlingua',
  'id' => 'Indonesian',
  'ie' => 'Interlingue',
  'ig' => 'Igbo',
  'ii' => 'Sichuan Yi',
  'io' => 'Ido',
  'is' => 'Icelandic',
  'it' => 'Italian',
  'ja' => 'Japanese',
  'jv' => 'Javanese',
  'ka' => 'Georgian',
  'ki' => 'Kikuyu',
  'kj' => 'Kwanyama',
  'kk' => 'Kazakh',
  'kl' => 'Kalaallisut',
  'km' => 'Central Khmer',
  'kn' => 'Kannada',
  'ko' => 'Korean',
  'ks' => 'Kashmiri',
  'kw' => 'Cornish',
  'ky' => 'Kirghiz',
  'la' => 'Latin',
  'lb' => 'Luxembourgish',
  'lg' => 'Ganda',
  'li' => 'Limburgish',
  'ln' => 'Lingala',
  'lo' => 'Lao',
  'lt' => 'Lithuanian',
  'lu' => 'Luba-Katanga',
  'lv' => 'Latvian',
  'mh' => 'Marshallese',
  'mi' => 'Māori',
  'mk' => 'Macedonian',
  'ml' => 'Malayalam',
  'mr' => 'Marathi',
  'mt' => 'Maltese',
  'my' => 'Burmese',
  'na' => 'Nauru',
  'nb' => 'Norwegian Bokmål',
  'nd' => 'North Ndebele',
  'ne' => 'Nepali',
  'ng' => 'Ndonga',
  'nl' => 'Dutch',
  'nn' => 'Norwegian Nynorsk',
  'nr' => 'South Ndebele',
  'nv' => 'Navajo',
  'ny' => 'Chichewa',
  'oc' => 'Occitan',
  'or' => 'Oriya',
  'os' => 'Ossetian',
  'pa' => 'Panjabi',
  'pi' => 'Pāli',
  'pl' => 'Polish',
  'pt' => 'Portuguese',
  'rm' => 'Romansh',
  'rn' => 'Rundi',
  'ro' => 'Romanian',
  'ru' => 'Russian',
  'rw' => 'Kinyarwanda',
  'sa' => 'Sanskrit',
  'sd' => 'Sindhi',
  'se' => 'Northern Sami',
  'sg' => 'Sango',
  'si' => 'Sinhala',
  'sk' => 'Slovak',
  'sl' => 'Slovene',
  'sm' => 'Samoan',
  'sn' => 'Shona',
  'so' => 'Somali',
  'sr' => 'Serbian',
  'ss' => 'Swati',
  'st' => 'Southern Sotho',
  'su' => 'Sundanese',
  'sv' => 'Swedish',
  'ta' => 'Tamil',
  'te' => 'Telugu',
  'tg' => 'Tajik',
  'th' => 'Thai',
  'ti' => 'Tigrinya',
  'tk' => 'Turkmen',
  'tl' => 'Tagalog',
  'tn' => 'Tswana',
  'to' => 'Tonga',
  'tr' => 'Turkish',
  'ts' => 'Tsonga',
  'tt' => 'Tatar',
  'tw' => 'Twi',
  'ty' => 'Tahitian',
  'ug' => 'Uighur',
  'uk' => 'Ukrainian',
  'ur' => 'Urdu',
  've' => 'Venda',
  'vi' => 'Vietnamese',
  'vo' => 'Volapük',
  'wa' => 'Walloon',
  'wo' => 'Wolof',
  'xh' => 'Xhosa',
  'yo' => 'Yoruba',
  'zu' => 'Zulu',
  'zh' => 'Chinese'
);

$modernLanguagesFlipped = array_flip($modernLanguages);

// Fallback for when the user hasn't configured this in their settings.inc.php
if (empty($ignoredPrefixes))
	$ignoredPrefixes = array(
		'A', 'The',
		'De', 'Het', 'Een',
		'Le', 'La',
		'Der', 'Die', 'Das');


?>
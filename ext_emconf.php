<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ods_ajaxmailsubscription".
 *
 * Auto generated 07-03-2013 22:13
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Ajax mail subscription',
	'description' => 'Adds a plugin for subscription to direct mail newsletters',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.4.2',
	'dependencies' => 'tt_address',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Robert Heel',
	'author_email' => 'typo3@bobosch.de',
	'author_company' => 'http://www.1drop.de/',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.6.0-6.2.99',
			'tt_address' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'direct_mail' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:22:{s:9:"ChangeLog";s:4:"5cbc";s:20:"class.ext_update.php";s:4:"e569";s:12:"ext_icon.gif";s:4:"1234";s:17:"ext_localconf.php";s:4:"049a";s:14:"ext_tables.php";s:4:"c786";s:14:"ext_tables.sql";s:4:"1370";s:28:"ext_typoscript_constants.txt";s:4:"f6f7";s:24:"ext_typoscript_setup.txt";s:4:"bf2e";s:13:"locallang.xml";s:4:"4a9a";s:16:"locallang_db.xml";s:4:"f4ef";s:10:"README.txt";s:4:"ee2d";s:14:"doc/manual.sxw";s:4:"a7f3";s:14:"pi1/ce_wiz.gif";s:4:"970f";s:44:"pi1/class.tx_odsajaxmailsubscription_pi1.php";s:4:"8573";s:52:"pi1/class.tx_odsajaxmailsubscription_pi1_wizicon.php";s:4:"f93d";s:16:"pi1/flexform.xml";s:4:"90c7";s:17:"pi1/locallang.xml";s:4:"18cc";s:33:"pi1/ods_ajaxmailsubscription.html";s:4:"583d";s:38:"pi1/ods_ajaxmailsubscription_mail.html";s:4:"975d";s:15:"res/loading.gif";s:4:"faa7";s:31:"res/ods_ajaxmailsubscription.js";s:4:"d18c";s:30:"res/jquery/jquery-1.4.4.min.js";s:4:"73a9";}',
	'suggests' => array(
	),
);

?>
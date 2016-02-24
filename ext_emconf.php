<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ods_ajaxmailsubscription".
 *
 * Auto generated 10-01-2016 19:43
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Ajax mail subscription',
	'description' => 'Adds a plugin for subscription to direct mail newsletters',
	'category' => 'plugin',
	'version' => '1.5.0',
	'state' => 'stable',
	'uploadfolder' => true,
	'createDirs' => '',
	'clearcacheonload' => false,
	'author' => 'Robert Heel',
	'author_email' => 'typo3@bobosch.de',
	'author_company' => 'http://www.1drop.de/',
	'constraints' => 
	array (
		'depends' => 
		array (
			'typo3' => '4.6.0-6.2.99',
			'tt_address' => '',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
			'direct_mail' => '',
		),
	),
);


<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

$tempColumns = array (
	'gender' => array (
		'label'  => 'LLL:EXT:tt_address/locallang_tca.xml:tt_address.gender',
		'config' => array (
			'type' => 'radio',
			'default' => 'm',
			'items' => array(
				array('LLL:EXT:tt_address/locallang_tca.xml:tt_address.gender.m', 'm'),
				array('LLL:EXT:tt_address/locallang_tca.xml:tt_address.gender.f', 'f')
			)
		)
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'fe_users',
	$tempColumns
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'fe_users',
	'gender',
	'',
	'after:title'
);
?>
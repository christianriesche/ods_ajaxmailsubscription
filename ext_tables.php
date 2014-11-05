<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$tempColumns = Array (
	'gender' => array (
		'label'  => 'LLL:EXT:tt_address/locallang_tca.xml:tt_address.gender',
		'config' => array (
			'type'    => 'radio',
			'default' => 'm',
			'items'   => array(
				array('LLL:EXT:tt_address/locallang_tca.xml:tt_address.gender.m', 'm'),
				array('LLL:EXT:tt_address/locallang_tca.xml:tt_address.gender.f', 'f')
			)
		)
	),
);
t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","gender;;;;1-1-1");

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
t3lib_extMgm::addPlugin(array('LLL:EXT:ods_ajaxmailsubscription/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] ='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY . '/pi1/flexform.xml');

if (TYPO3_MODE=="BE") $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_odsajaxmailsubscription_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_odsajaxmailsubscription_pi1_wizicon.php';
?>
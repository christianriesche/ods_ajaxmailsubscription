<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:ods_ajaxmailsubscription/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] ='pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY . '/pi1/flexform.xml');

if (TYPO3_MODE=="BE") $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_odsajaxmailsubscription_pi1_wizicon"] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'pi1/class.tx_odsajaxmailsubscription_pi1_wizicon.php';
?>
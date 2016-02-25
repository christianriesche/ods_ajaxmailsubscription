<?php
/***************************************************************
 *  Copyright notice
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class ext_update {
	protected $messageArray = array();

	public function access() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0');
	}

	/**
	 * Main update function called by the extension manager.
	 *
	 * @return string
	 */
	public function main() {
		$this->processUpdates();
		return $this->generateOutput();
	}

	/**
	 * Generates output by using flash messages
	 *
	 * @return string
	 */
	protected function generateOutput() {
		$output = '';
		foreach ($this->messageArray as $messageItem) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\CMS\Core\Messaging\FlashMessage',
				$messageItem[2],
				$messageItem[1],
				$messageItem[0]
			);
			$output .= $flashMessage->render();
		}

		return $output;
	}
	
	/**
	 * The actual update function. Add your update task in here.
	 *
	 * @return void
	 */
	protected function processUpdates() {
		$this->addDatabaseField('fe_users', 'tx_odsajaxmailsubscription_rid', "varchar(8) DEFAULT '' NOT NULL");
		$this->addDatabaseField('tt_address', 'tx_odsajaxmailsubscription_rid', "varchar(8) DEFAULT '' NOT NULL");
		$this->moveFieldFFtoTable('sDEF','page_records','tt_content','pages');
	}
	
	/**
	 * Add a field to database table
	 *
	 * @param  string $table
	 * @param  string $field
	 * @param  string $options
	 * @return int
	 */
	protected function addDatabaseField($table, $field, $options) {
		$title = 'Modify table "' . $table . '": Add field ' . $field;
		$message = '';
		$status = NULL;

		$currentTableFields = $GLOBALS['TYPO3_DB']->admin_get_fields($table);

		if ($currentTableFields[$field]) {
			$message = 'Field ' . $table . ':' . $field . ' already exists.';
			$status = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
		} else {
			$sql = 'ALTER TABLE ' . $table . ' ADD ' . $field . ' ' . $options;
			if ($GLOBALS['TYPO3_DB']->admin_query($sql) === FALSE) {
				$message = ' SQL ERROR: ' .  $GLOBALS['TYPO3_DB']->sql_error();
				$status = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
			} else {
				$message = 'OK!';
				$status = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
			}
		}

		$this->messageArray[] = array($status, $title, $message);
		return $status;
	}
	
	protected function moveFieldFFtoTable($FFsheet,$FFfield,$DBtable,$DBfield) {
		$title = 'Move data from flexform sheet "' . $FFsheet . '", field "' . $FFfield . '" to table "' . $DBtable . '", field "' . $DBfield . '"';
		$status = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
	
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tt_content',
			'CType=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('list', 'tt_content') . ' AND list_type=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('ods_ajaxmailsubscription_pi1', 'tt_content') . ' AND deleted=0'
		);

		if ($res) {
			$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools');
		
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$xmlArr = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);
				$oldVal = $this->pi_getFFvalue($xmlArr, $FFfield, $FFsheet);
				if ($oldVal) {
					$message = 'Moved value "' . $oldVal . '" on content id "' . $row['uid'] . '"' . "\n";
					unset($xmlArr['data'][$FFsheet]['lDEF'][$FFfield]);

					$newff = $flexObj->flexArray2Xml($xmlArr, 1);

					$fields_values = array('pi_flexform' => $newff);
					if($DBtable=='tt_content') $fields_values[$DBfield]=$oldVal;
					$UPDATEres = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tt_content',
						'uid=' . $row['uid'],
						$fields_values
					);
					if (!$UPDATEres) {
						$status = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
					}
					
				}
			}
			if(empty($message)) $message='No data to move.';
			$this->messageArray[] = array($status, $title, $message);
		}
	}

	protected function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF') {
		$sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
		if (is_array($sheetArray)) {
			return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
		}
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	protected function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value) {
		$tempArr = $sheetArray;
		foreach ($fieldNameArr as $k => $v) {
			if (is_int($v)) {
				if (is_array($tempArr)) {
					$c = 0;
					foreach ($tempArr as $values) {
						if ($c == $v) {
							$tempArr = $values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}
}
?>
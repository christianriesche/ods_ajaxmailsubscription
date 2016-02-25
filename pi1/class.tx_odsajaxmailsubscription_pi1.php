<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Robert Heel <typo3@bobosch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Plugin 'Ajax mail subscription' for the 'ods_ajaxmailsubscription' extension.
 *
 * @author	Robert Heel <typo3@bobosch.de>
 * @package	TYPO3
 * @subpackage	tx_odsajaxmailsubscription
 */
class tx_odsajaxmailsubscription_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
	var $prefixId      = 'tx_odsajaxmailsubscription_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_odsajaxmailsubscription_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ods_ajaxmailsubscription';	// The extension key.

	var $user; // user information
	var $config; // Configuration
	var $error; // Errors
	var $info; // Infomessage
	var $hooks;
	var $htmlMail; // htmlMail object
	var $tables=array('t'=>'tt_address','f'=>'fe_users');
	var $tables_mm=array(
		'fe_groups'=>array(
			'field'=>'usergroup',
			'table'=>'fe_users',
		),
		'sys_dmail_category'=>array(
			'MM'=>'sys_dmail_ttaddress_category_mm',
			'table'=>'tt_address',
			'user_local'=>true,
		),
		'sys_dmail_group'=>array(
			'MM'=>'sys_dmail_group_mm',
			'user_local'=>false,
		),
		'tt_address_group'=>array(
			'MM'=>'tt_address_group_mm',
			'table'=>'tt_address',
			'user_local'=>true,
		),
	);

	var $template; // Temlates

	function init($conf){
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin
		$this->pi_USER_INT_obj=1; // Make the plugin not cachable

		$this->hooks=array();
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->scriptRelPath])){
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->scriptRelPath] as $classRef){
				$this->hooks[]=&\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}

		/* --------------------------------------------------
			Configuration (order of priority)
			- FlexForm
			- TypoScript
		-------------------------------------------------- */
		$conf['use_mailer']=\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail') ? 'direct_mail' : '';

		$flex=array();
		$options=array(
			'default_group'=>'sDEF',
			'default_type'=>'sDEF',
			'mail_from'=>'sDEF',
			'mail_from_name'=>'sDEF',
			'mail_notify'=>'sDEF',
			'mail_reply'=>'sDEF',
			'mail_reply_name'=>'sDEF',
			'mail_return'=>'sDEF',
			'page_edit'=>'sDEF',
			'page_redirect_unsubscribe'=>'sDEF',
			'show_default'=>'sDEF',
			'template'=>'sDEF',
		);
		foreach($options as $option=>$sheet){
			$value=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],$option,$sheet);
			if($value){
				switch($option){
					case 'template':
						$flex[$option]='uploads/tx_odsajaxmailsubscription/'.$value;
					break;
					default:
						$flex[$option]=$value;
					break;
				}
			}
		}
		$this->config=array_merge($conf,$flex);

		if($this->cObj->data['pages']) $this->config['page_records']=$this->cObj->data['pages'];
		if($this->cObj->data['recursive']) $this->config['page_records_recursive']=$this->cObj->data['recursive'];

		$this->config['mail_from']=strtr($this->config['mail_from'],array('###DOMAIN###'=>$_SERVER['HTTP_HOST']));
		if(empty($this->config['page_edit'])) $this->config['page_edit']=$GLOBALS['TSFE']->id;

		// Backward compatibility to 0.2.x
		if(is_numeric($this->config['default_group'])) $this->config['default_group']='sys_dmail_group_'.$this->config['default_group'];
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->init($conf);

		$this->mail=\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Mail\MailMessage');
		if($this->config['mail_from']) $this->mail->setFrom(array($this->config['mail_from'] => $this->config['mail_from_name']));
		if($this->config['mail_reply']) $this->mail->setReplyTo(array($this->config['mail_reply'] => $this->config['mail_reply_name']));
		if($this->config['mail_return']) $this->mail->setReturnPath($this->config['mail_return']);

		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId]='<script src="'.$GLOBALS['TSFE']->tmpl->getFileName($this->config['javascript']).'" type="text/javascript"></script>';
// 		$GLOBALS['TSFE']->getPageRenderer()->addJsFile($GLOBALS['TSFE']->tmpl->getFileName($this->config['javascript']));

		$this->processInput();
		$this->loadTemplate();

		if($this->user){
			$content=$this->getSettings();
		}elseif($this->info){
			$content=$this->getInformation();
		}else{
			$content=$this->getSubscribe();
		}

		if($this->piVars['ajax']){
			echo $content;
			die();
		}

		$subpart['###INFORMATION###']='';
		$subpart['###SETTINGS###']='';
		$subpart['###SUBSCRIBE###']='<div id="'.$this->prefixId.'">'.$content.'</div>';
		$subpart['###INDICATION###']='<div id="'.$this->prefixId.'_indication" style="display: none;">'.$this->getIndication().'</div>';
		$content=$this->cObj->substituteMarkerArrayCached($this->template['total'],array(),$subpart);

		return $this->pi_wrapInBaseClass($content);
	}

	function loadTemplate(){
		$templateCode=$this->cObj->fileResource($this->config['template']);
		$this->template['total']=$this->cObj->getSubpart($templateCode,'###AJAXMAILSUBSCRIPTION###');
	}

	function processInput(){
		/* --------------------------------------------------
			Subscribe (submit)
		-------------------------------------------------- */
		if($this->piVars['submit']){
			// Check required fields
			$this->error=$this->checkRequiredFields(explode(',',$this->config['required.']['fields']),$this->piVars);
			if(!$this->error){
				$email=trim($this->piVars['email']);
				if(\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($email) && $email!=$this->pi_getLL('default_mail')){
					// Search address
					$user=$this->searchAddress(array('email'=>$email));
					if(!$user && $this->config['default_table']){
						// Create new address
						$table=$this->config['default_table'];
						$user=array(
							'pid'=>intval(strtok($this->config['page_records'],',')),
							'tstamp'=>time(),
							$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']=>1,
						);
						$user=array_merge($user,$this->getFieldUpdate(explode(',',$this->config['subscribe.']['fields']),$table));
						if($this->config['use_mailer']) $user['module_sys_dmail_html']=$this->config['default_type'];
						if($table=='fe_users'){
							$user['username']=$email;
							if($this->config['use_mailer']) $user['module_sys_dmail_newsletter']=1;
						}
						$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$user);
						$user['uid']=$GLOBALS['TYPO3_DB']->sql_insert_id();
						$user['table']=$table;
						// log
						$log=$user;
						$log['action']='create';
						$this->logging($log);
					}

					if($user){
						// Is user registered?
						if($user[$GLOBALS['TCA'][$user['table']]['ctrl']['enablecolumns']['disabled']]){
							// Create message
							$this->info=$this->pi_getLL('info_new');
							$templatename='mail_new';
						}else{
							// Inform user
							$this->info=$this->pi_getLL('info_change');
							$templatename='mail_change';
						}
						// Send email
						$this->sendUserMail($user,$templatename);
						// Notify user
						$this->info.='<br />'.$this->pi_getLL('check_mail');
					}else{
						$this->error=$this->pi_getLL('mail_invalid');
					}
				}else{
					$this->error=$this->pi_getLL('mail_invalid');
				}
			}
		}elseif($this->config['show_default']){
			$this->piVars['email']=$this->pi_getLL('default_mail');
		}

		/* --------------------------------------------------
			Confirmation (rid)
		-------------------------------------------------- */
		if($_GET['t'] && array_key_exists(substr($_GET['t'],0,1),$this->tables) && $_GET['u'] && $_GET['a']){
			$user=$this->searchWhere(array('uid'=>intval($_GET['u'])),$this->tables[substr($_GET['t'],0,1)]);
			if($user){
				if($this->checkAuthorisation($user,$_GET['a'])){
					// User authenticated
					$this->user=$user;
					// Enable user if disabled
					if($user[$GLOBALS['TCA'][$user['table']]['ctrl']['enablecolumns']['disabled']]){
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($user['table'],'uid='.$user['uid'],array($GLOBALS['TCA'][$user['table']]['ctrl']['enablecolumns']['disabled']=>0));
						$this->joinList($user,$this->config['default_group']);
						$this->info=$this->pi_getLL('activated');
						// Send email
						if(!empty($this->config['mail_confirmation'])){
							$this->sendMail($user['email'],'mail_subscribe');
						}
						// log
						$log=$user;
						$log['action']='subscribe';
						$this->logging($log);
					}
				}
			}
		}

		/* --------------------------------------------------
			Preferences
		-------------------------------------------------- */
		if($this->piVars['prefs'] && $this->user){
			$update=$this->getFieldUpdate(explode(',',$this->config['edit.']['fields']),$this->user['table']);
			$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->user['table'],'uid='.$this->user['uid'],$update);
			if($res){
				$this->info=$this->pi_getLL('update_success');
			}else{
				$this->info=$this->pi_getLL('update_fail');
			}
		}

		/* --------------------------------------------------
			Unsubscribe
		-------------------------------------------------- */
		if($_GET['action']=='delete' && $this->user){
			// Unsubscribe user
			$this->leaveList($this->user);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->user['table'],'uid='.$this->user['uid'],array($GLOBALS['TCA'][$this->user['table']]['ctrl']['enablecolumns']['disabled']=>1));
			// Send email
			if(!empty($this->config['mail_confirmation'])){
				$this->sendMail($this->user['email'],'mail_unsubscribe');
			}
			// Redirect
			if($this->config['page_redirect_unsubscribe']){
				header('Location: '.\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->pi_getPageLink($this->config['page_redirect_unsubscribe'])));
			}
			// log
			$log=$this->user;
			$log['action']='unsubscribe';
			$this->logging($log);
			// Delete session
			$this->user=NULL;
		}
	}

	function joinList($user,$group){
		$lists=$this->splitGroup($group);
		foreach($lists as $list=>$items){
			if(isset($this->tables_mm[$list]) && (!isset($this->tables_mm[$list]['table']) || $this->tables_mm[$list]['table']==$user['table'])){
				if(isset($this->tables_mm[$list]['MM'])){
					foreach($items as $item){
						if($this->tables_mm[$list]['user_local']){
							$insert=array(
								'uid_local'=>$user['uid'],
								'uid_foreign'=>$item,
							);
						}else{
							$insert=array(
								'uid_local'=>$item,
								'uid_foreign'=>$user['uid'],
							);
						}
						if(!isset($this->tables_mm[$list]['table'])) $insert['tablenames']=$user['table'];
						$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->tables_mm[$list]['MM'],$insert);
					}
				}elseif(isset($this->tables_mm[$list]['field'])){
					$field=$this->tables_mm[$list]['field'];
					$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery($field,$user['table'],'uid='.$user['uid']);
					$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$old=explode(',',$row[$field]);
					$new=$old ? array_merge($old,$items) : $items;
					$update=array(
						$field=>implode(',',$new)
					);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($user['table'],'uid='.$user['uid'],$update);
				}
			}
		}
	}

	function leaveList($user){
		foreach($this->tables_mm as $list=>$conf){
			if(isset($conf['MM']) && (!isset($conf['table']) || $conf['table']==$user['table'])){
				if($conf['user_local']){
					$where='uid_local='.$user['uid'];
				}else{
					$where='uid_foreign='.$user['uid'];
				}
				$GLOBALS['TYPO3_DB']->exec_DELETEquery($conf['MM'],$where.' AND (tablenames="'.$user['table'].'" OR tablenames="")');
			}
		}
	}

	function splitGroup($group){
		$groups=explode(',',$group);
		foreach($groups as $group){
			$item=\TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('_',$group,2);
			$ret[$item[0]][]=$item[1];
		}
		return($ret);
	}

	function logging($data){
		// Syslog entry
		$insert=array(
			'tablename'=>$data['table'],
//			'details'=>\TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($data),
			'details'=>'%s address %s (table: %s, uid: %s, list %s)',
			'tstamp'=>time(),
			'IP'=>\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
			'log_data'=>serialize(array($data['action'],$data['email'],$data['table'],$data['uid'],$this->config['default_group'])),
			'event_pid'=>0,
		);
		$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_log',$insert);
		// Notify mail
		if($this->config['mail_notify']){
			$this->sendMail(
				array_combine(explode(',',$this->config['mail_notify']),explode(',',$this->config['mail_notify'])),
				'mail_notify',
				array(
					'###EMAIL###'=>$data['email'],
					'###ACTION###'=>$this->pi_getLL('mail_notify_'.$data['action']),
					'###TABLE###'=>$data['table'],
					'###UID###'=>$data['uid'],
				)
			);
		}
	}

	function searchAddress($where){
		$ret=false;
		foreach($this->tables as $table){
			$row=$this->searchWhere($where,$table);
			if($row){
				$ret=$row;
				break;
			}
		}
		return($ret);
	}

	function searchWhere($fields,$table){
		$where=array();
		foreach($fields as $field=>$value){
			$where[]=$field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($value,$table);
		}
		if($this->config['page_records']) $where[]='pid IN ('.$this->pi_getPidList($this->config['page_records'],intval($this->config['page_records_recursive'])).')';
		$where[]='deleted=0';
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$table,implode(' AND ',$where),'',$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'],1);
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if($row){
			$row['table']=$table;
		}
		return($row);
	}

	function getSubscribe(){
		$template['subscribe']=$this->cObj->getSubpart($this->template['total'],'###SUBSCRIBE###');

		// Subscribe
		$marker=$this->getFieldMarker(explode(',',$this->config['subscribe.']['fields']),$this->piVars);
		$marker['###HEADER###']=$this->pi_getLL('text_subscribe');
		$marker['###FORM_ACTION###']=$this->pi_getPageLink($GLOBALS['TSFE']->id);
		$marker['###FORM_ONSUBMIT###']='';
		// submit should call xajax instead.
		$marker['###SUBMIT_ONCLICK###']='return ods_ajaxmailsubscription(this);';
		$marker['###SUBMIT_NAME###']=$this->prefixId.'[submit]';
		$marker['###SUBMIT_VALUE###']=$this->pi_getLL('subscribe');

		// Message
		$marker['###MESSAGE###']=$this->error ? $this->cObj->stdWrap($this->error,$this->conf['error.']['stdWrap.']) : '';

		return($this->cObj->substituteMarkerArrayCached($template['subscribe'],$marker));
	}

	function getInformation(){
		$template['information']=$this->cObj->getSubpart($this->template['total'],'###INFORMATION###');

		$marker['###HEADER###']=$this->pi_getLL('text_subscribe');
		$marker['###MESSAGE###']=$this->cObj->stdWrap($this->info,$this->conf['info.']['stdWrap.']);

		return($this->cObj->substituteMarkerArrayCached($template['information'],$marker));
	}

	function getSettings(){
		$template['settings']=$this->cObj->getSubpart($this->template['total'],'###SETTINGS###');

		// Preferences
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->user['table'],'deleted=0 AND uid='.$this->user['uid'],'','',1);
		if($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
			$marker=$this->getFieldMarker(explode(',',$this->config['edit.']['fields']),$row);
			$marker['###HEADER###']=$this->pi_getLL('text_unsubscribe');
			$marker['###PREFS_TEXT###']=$this->pi_getLL('text_prefs');
			// Form and submit
			$marker['###FORM_ACTION###']=$this->getPageEditLink($this->user);
			$marker['###FORM_ONSUBMIT###']='';
			$marker['###SUBMIT_NAME###']=$this->prefixId.'[prefs]';
			// submit should call xajax instead.
			$marker['###SUBMIT_ONCLICK###']='return ods_ajaxmailsubscription(this);';
			$marker['###SUBMIT_VALUE###']=$this->pi_getLL('update');
			
			// Edit
			$marker['###EDIT_LINK###']=$this->getPageEditLink($this->user);
			$marker['###EDIT_TEXT###']=$this->pi_getLL('page_edit');

			// Unsubscribe
			$marker['###UNSUBSCRIBE_LINK###']=$this->getPageEditLink($this->user,true);
			$marker['###UNSUBSCRIBE_ONCLICK###']="return window.confirm('".$this->pi_getLL('sure')."');";
			$marker['###UNSUBSCRIBE_TEXT###']=$this->pi_getLL('unsubscribe');

			// Message
			$marker['###MESSAGE###']=$this->info ? $this->cObj->stdWrap($this->info,$this->conf['info.']['stdWrap.']) : '';

			// Hook to change marker
			foreach($this->hooks as $hook){
				if(method_exists($hook,'unsubscribeMarker')){
					$hook->unsubscribeMarker($marker,$this);
				}
			}

			return($this->cObj->substituteMarkerArrayCached($template['settings'],$marker));
		}else{
			$this->user=false;
		}

		return('');
	}
	
	function getFieldMarker($fields,$defaults=array()){
		$marker=array();
		foreach($fields as $field){
			$marker['###'.strtoupper($field).'_DESC###']=$this->pi_getLL('desc_'.$field);
			$marker['###'.strtoupper($field).'_LABEL###']=$this->pi_getLL('desc_'.$field);
			$marker['###'.strtoupper($field).'_NAME###']=$this->prefixId.'['.$field.']';
			$marker['###'.strtoupper($field).'_VALUE###']=htmlspecialchars($defaults[$field]);
		}

		foreach(array('m','f') as $val){
			$marker['###GENDER_OPTIONS###'].='<option value="'.$val.'"';
			if($val==$defaults['gender']) $marker['###GENDER_OPTIONS###'].=' selected="selected"';
			$marker['###GENDER_OPTIONS###'].='>'.$this->pi_getLL('desc_gender.'.$val).'</option>';
		}

		return $marker;
	}

	function getFieldUpdate($fields,$table){
		$update=array();
		foreach($fields as $field){
			if(isset($this->piVars[$field])) $update[$field]=$GLOBALS['TYPO3_DB']->quoteStr(trim($this->piVars[$field]),$table);
		}
		// Update tt_address name field
		if($table=='tt_address' && ($update['first_name'] || $update['middle_name'] || $update['last_name'])){
			$tt_address=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address']);
			$name=sprintf(
				$tt_address['backwardsCompatFormat'],
				$update['first_name'],
				$update['middle_name'],
				$update['last_name']
			);
			if(!empty($name)) {
				$update['name']=$name;
			}
		}
		return $update;
	}


	function checkRequiredFields($fields,$values){
		$errors=array();
		foreach($fields as $field){
			if(empty($values[$field])) $errors[]=sprintf($this->pi_getLL('field_required'),$this->pi_getLL('desc_'.$field));
		}

		return $errors ? implode('<br />',$errors) : false;
	}

	function getIndication(){
		/* --------------------------------------------------
			Loading indication
		-------------------------------------------------- */
		$template['indication']=$this->cObj->getSubpart($this->template['total'],'###INDICATION###');
		return($template['indication']);
	}

	function sendUserMail(&$user, $template) {
		$marker=array(
			'###LINK###' => \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->getPageEditLink($user)),
			'###UNSUBSCRIBE_LINK###' => \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->getPageEditLink($user, true)),
		);
		$this->sendMail($user['email'], $template, $marker);
	}
	
	function sendMail($recipient,$templatename,$marker=array()){
		$template=$this->getMailTemplate($templatename);

		$marker['###SERVER###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');

		$this->mail->setTo($recipient);
		$this->mail->setSubject(strtr($template['subject'],$marker));
		if (!empty($template['html']) && $this->config['mail_html']) {
			$this->mail->setBody(strtr($template['html'],$marker),'text/html');
			$this->mail->addPart(strtr($template['text'],$marker),'text/plain');
		}else{
			$this->mail->setBody(strtr($template['text'],$marker),'text/plain');
		}
		$this->mail->send();
	}

	function getMailTemplate($templatename){
		$templateCode=$this->cObj->fileResource($this->config['mailtemplate']);
		$template['mailtemplate']=$this->cObj->getSubpart($templateCode,'###ODS_AJAXMAILSUBSCRIPTION_MAIL_'.strtoupper($GLOBALS['TSFE']->lang).'###');
		$template['subject']=trim($this->cObj->getSubpart($template['mailtemplate'],'###'.strtoupper($templatename).'_SUBJECT###'));
		if(empty($template['subject'])){
			$template['mailtemplate']=$this->cObj->getSubpart($templateCode,'###ODS_AJAXMAILSUBSCRIPTION_MAIL###');
			$template['subject']=trim($this->cObj->getSubpart($template['mailtemplate'],'###'.strtoupper($templatename).'_SUBJECT###'));
		}
		$template['text']=trim($this->cObj->getSubpart($template['mailtemplate'],'###'.strtoupper($templatename).'_BODY_TEXT###'));
		$template['html']=trim($this->cObj->getSubpart($template['mailtemplate'],'###'.strtoupper($templatename).'_BODY_HTML###'));
		return($template);
	}
	
	function getPageEditLink(&$user,$delete=false){
		$params=array(
			'L'=>$GLOBALS['TSFE']->sys_language_uid,
			't'=>substr($user['table'],0,1),
			'u'=>$user['uid'],
			'a'=>$this->getAuthorisation($user)
		);
		if($delete) $params['action']='delete';
		return $this->pi_getPageLink($this->config['page_edit'],'',$params);
	}
	
	/**
	 * Check the Authorisation and invalidates the link. Use this function only once!
	 *
	 * @param	array	$user
	 * @param	array	$a: The authorisation key
	 * @return	bool	true if the user is authenticated
	 */
	function checkAuthorisation(&$user, $a) {
		$expired=false;

		// Onetime key from ods_ajaxmailsubscription
		// Code must be 16 hex characters
		if(preg_match('/^[0-9a-f]{16}$/', $a)) {
			// Timecode + Authcode hash must match
			$timecode = substr($a,0,8);
			if($a == $this->getAuthorisation($user,$timecode)) {
				// Check expiration time
				// time difference has to be less then expiration time
				// http://php.net/manual/de/class.datetime.php ?
				if($this->conf['authcode_expiration_time'] == 0 || time() - hexdec($timecode) <= $this->conf['authcode_expiration_time'] * 60) {
					$user['tx_odsajaxmailsubscription_rid'] = '';
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($user['table'], 'uid='.$user['uid'], array('tx_odsajaxmailsubscription_rid'=>''));
					return true;
				} else {
					$expired = true;
				}
			}

		// Authcode from direct_mail unsubcription link
		// Code must be 8 hex characters
		} elseif(preg_match('/^[0-9a-f]{8}$/', $a)) {
			if($this->getAuthorisationCode($user)) {
				$expired = true;
			}
		}

		if($expired) {
			// Send email
			$this->sendUserMail($user, 'mail_change');
			// Notify user
			$this->info = $this->pi_getLL('link_expired');
			$this->info .= '<br />' . $this->pi_getLL('check_mail');
		}

		return false;
	}

	function getAuthorisation(&$user, $timecode=false) {
		if(!$timecode) $timecode=str_pad(dechex(time()), 8, '0', STR_PAD_LEFT);
 		return $timecode . substr(md5($timecode . $this->getAuthorisationCode($user) . $this->getAuthorisationRid($user)), 0, 8);
	}

	function getAuthorisationCode($user) {
 		return \TYPO3\CMS\Core\Utility\GeneralUtility::stdAuthCode($user,$this->config['authcode_fields']);
	}

	function getAuthorisationRid(&$user) {
		if(empty($user['tx_odsajaxmailsubscription_rid'])) {
			$user['tx_odsajaxmailsubscription_rid'] = substr(str_shuffle(str_repeat('0123456789abcdef' ,8)), 0, 8);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($user['table'], 'uid='.$user['uid'], array('tx_odsajaxmailsubscription_rid'=>$user['tx_odsajaxmailsubscription_rid']));
		}

		return $user['tx_odsajaxmailsubscription_rid'];
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ods_ajaxmailsubscription/pi1/class.tx_odsajaxmailsubscription_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ods_ajaxmailsubscription/pi1/class.tx_odsajaxmailsubscription_pi1.php']);
}

?>

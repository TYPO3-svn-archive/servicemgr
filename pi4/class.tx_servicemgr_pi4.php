<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Peter Schuster <typo3@peschuster.de>
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

require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');
if (t3lib_extMgm::isLoaded('date2cal')) require_once(t3lib_extMgm::extPath('date2cal').'src/class.jscalendar.php');

/**
 * Plugin 'Duty schedule' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi4 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi4';		// Same as class name
	var $scriptRelPath = 'pi4/class.tx_servicemgr_pi4.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();

		$this->tx_init();
		$this->tx_loadLL();
		$this->conf['requiredFields'] = t3lib_div::trimExplode(',',$this->conf['requiredFields']);
		$this->userID = $GLOBALS['TSFE']->fe_user->user[uid];
		$this->ts = mktime();
		$this->fetchConfigValue('viewmode');

		if (t3lib_extMgm::isLoaded('date2cal')) {
			$this->JSCalendar = JSCalendar::getInstance();
			if (($jsCode = $this->JSCalendar->getMainJS()) != '') {
				$GLOBALS['TSFE']->additionalHeaderData['servicemgr_date2cal'] = $jsCode;
			}
		}

		$GLOBALS['TYPO3_DB']->debugOutput = true;

		if ($this->piVars['submit']) {
			$content = $this->doSubmit();
		} else {
			$content = $this->showForm();
		}

		return $this->pi_wrapInBaseClass($content);
	}

	function showForm($defaultValues = array()) {
		$template = $this->cObj->getSubpart($this->template, '###ADDNEWEVENT###');

		if ($defaultValues === array()) {
			$defaultValues = $this->conf['defaultValues.'];
		}
		foreach ($defaultValues as $k => $v) {
					$defaultValues[$k] = is_array($v) ? implode(',',$v) : $v;	
		}
		
		if (is_array($this->formValidationErrors)) {
			$formError = $this->pi_getLL('error_missingfields').' ';
			foreach ($this->formValidationErrors as $k => $v) {
				$formErrors[] = $this->pi_getLL('L_'.strtoupper($k));
			}
			$formError .= implode(', ',$formErrors);
			$formError = $this->throwErrorMsg($formError);
		}
		
		$marker = array(
			'###FORM_ERRORS###' => $formError,

			'###L_DATE###' => $this->pi_getLL('L_DATE'),
			'###L_TIME###' => $this->pi_getLL('L_TIME'),
			'###L_DATETIME###' => $this->pi_getLL('L_DATETIME'),
			'###L_SUBJECT###' => $this->pi_getLL('L_SUBJECT'),
			'###L_SERIES###' => $this->pi_getLL('L_SERIES'),
			'###L_PUBLIC###' => $this->pi_getLL('L_PUBLIC'),
			'###L_TAGS###' => $this->pi_getLL('L_TAGS'),
			'###L_REQUIREDTEAMS###' => $this->pi_getLL('L_REQUIREDTEAMS'),
			'###L_DUTYSCHEDULEOPEN###' => $this->pi_getLL('L_DUTYSCHEDULEOPEN'),
			'###L_DOCUMENTS###' => $this->pi_getLL('L_DOCUMENTS'),
			'###L_NOTES###' => $this->pi_getLL('L_NOTES'),
			'###L_NOTESINTERNAL###' => $this->pi_getLL('L_NOTESINTERNAL'),
			'###L_SUBMIT###' => $this->pi_getLL('L_SUBMIT'),

			'###V_DATE###' => $defaultValues['date'],
			'###V_TIME###' => $defaultValues['time'],
			'###V_DATETIME###' => $defaultValues['datetime'],
			'###V_SUBJECT###' => $defaultValues['subject'],
			'###V_PUBLIC###' => $defaultValues['public'] ? 'checked="checked" ' : '',
			'###V_DUTYSCHEDULEOPEN###' => $defaultValues['dutyscheduleopen'] ? 'checked="checked" ' : '',
			'###V_NOTES###' => $defaultValues['notes'],
			'###V_NOTESINTERNAL###' => $defaultValues['notesinternal'],
		);

		$marker['###DATE_CAL###'] = $this->getDate2Cal('tx_servicemgr_pi4[date]');
		$marker['###TIME_CAL###'] = '';

		
		$series = $this->getSeries();
		$seriesContent = '<select name="tx_servicemgr_pi4[series]" id="frm-series">'."\n";
		foreach ($series as $serie) {
			$seriesContent .= '	<option value="'.$serie['uid'].'"'
								.($serie['uid'] == $defaultValues['series'] ? ' selected="selected"' : '')
								.'>'.$serie['name'].'</option>'."\n";
		}
		$seriesContent .= '</select>'."\n";
		$marker['###SERIES_SELECTOR###'] = $seriesContent;
		$marker['###SERIES_ADDLINK###'] = '<img title="'.$this->pi_getLL('title_addlink_series').'" alt="'.$this->pi_getLL('alt_addlink_series').'" src="'.$this->conf['addlink_img'].'" class="addIcon" />';

		
		$tags = $this->getTags();
		$tagsContent = '';
		foreach ($tags as $tag) {
			$tagsContent .= '<input type="checkbox" name="tx_servicemgr_pi4[tags][]" value="'
							.$tag['uid'].'" id="frm-tags-'.$tag['uid'].'"'
							.(in_array($tag['uid'], split(',',$defaultValues['tags'])) ? ' checked="checked"' : '')
							.' /> <label for="frm-tags-'.$tag['uid'].'">'.$tag['name'].'</label><br />';
		}
		$marker['###TAGS_CBS###'] = $tagsContent;
		$marker['###TAGS_ADDLINK###'] = '<img title="'.$this->pi_getLL('title_addlink_tags').'" alt="'.$this->pi_getLL('alt_addlink_tags').'" src="'.$this->conf['addlink_img'].'" />';

		
		$teams = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title',
					'fe_groups', 'deleted=0 AND tx_servicemgr_isteam=1');
		$teamsContent = '';
		foreach ($teams as $team) {
			$teamsContent .= '<input type="checkbox" name="tx_servicemgr_pi4[requiredteams][]" value="'.$team['uid'].'" id="frm-requiredteams-'.$team['uid'].'"'.(in_array($team['uid'], split(',',$defaultValues['requiredteams'])) ? ' checked="checked"' : '').' /> <label for="frm-requiredteams-'.$team['uid'].'">'.$team['title'].'</label><br />';
		}
		$marker['###REQUIREDTEAMS_CBS###'] = $teamsContent;

		
		$marker['###DOCUMENTS_ADDLINK###'] = '<img title="'.$this->pi_getLL('title_addlink_documents').'" alt="'.$this->pi_getLL('alt_addlink_documents').'" src="'.$this->conf['addlink_img'].'" />';

		
		$actionLink = $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'addQueryString' => 1,
			'addQueryString.' => array(
				'exclude' => 'cHash,no_cache',
		),
			'additionalParams' => '&no_cache=1',
			'useCacheHash' => false,
		));
		$marker['###ACTION_LINK###'] = $actionLink;

		$content = $this->cObj->substituteMarkerArray($template, $marker);

		return $content;
	}

	function getDate2Cal($inputField) {
		$content = '';
		if (t3lib_extMgm::isLoaded('date2cal')) {
			$this->JSCalendar->setDateFormat(false);
			$this->JSCalendar->setConfigOption('format','%d.%m.%Y');
			$this->JSCalendar->setConfigOption('ifFormat','%d.%m.%Y');
			$this->JSCalendar->setInputField($inputField);
			$content = $this->JSCalendar->renderImages();
		}
		return $content;
	}

	function doSubmit() {
		if ($this->piVars['submit'] && $this->doSubmit_validate()) {
			$startingPoints[0] = $GLOBALS['TSFE']->id;
			if (!empty($this->cObj->data["pages"])) {
				$startingPoints = explode(',',$this->cObj->data["pages"]);
			}
			list($date['day'], $date['month'], $date['year']) = split('\.',$this->piVars['date']);
			list($time['hour'], $time['minute']) = split(':',$this->piVars['time']);
			$datetime = mktime(intval($time['hour']),intval($time['minute']),0,intval($date['month']),intval($date['day']),intval($date['year']));

			$record = array(
				'pid' => $startingPoints[0],
				'datetime' => $datetime,
				'subject' => $this->piVars['subject'],
				'series' => intval($this->piVars['series']),
				'public' => intval($this->piVars['public']),
				'tags' => implode(',',$this->piVars['tags']),
				'requiredteams' => implode(',',$this->piVars['requiredteams']),
				'dutyscheduleopen' => intval($this->piVars['dutyscheduleopen']),
				'notes' => $this->piVars['notes'],
				'notes_internal' => $this->piVars['notes_internal'],
			);
			$record['l18n_diffsource'] = serialize($record);
			$record['tstamp'] = $record['crdate'] = $this->ts;

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_servicemgr_events', $record);
			$newUid = $GLOBALS['TYPO3_DB']->sql_insert_id();

			$content=$this->detailViewEvent(
				$newUid,
				array(
					'subparts' => array('subject','datetime','series','notes','backlink'),
					'backlink' => array('str' => $this->pi_getLL('back'), 'id' => $GLOBALS['TSFE']->id),
				),
				$this->cObj->getSubpart($this->cObj->fileResource('EXT:servicemgr/res/esv.html'), '###SINGLEEVENTEL###')
			);
		} else {
			$content = $this->showForm($this->piVars);
		}
		return $content;
	}

	function doSubmit_validate() {
		$result = true;
		foreach($this->piVars as $k => $v) {
			$this->piVars[$k] = !is_array($v) ? trim($v) : $v;
		}

		if (is_array($this->conf['requiredFields'])) {
			foreach ($this->conf['requiredFields'] as $field) {
				if (!$this->piVars[$field]) {
					$this->formValidationErrors[$field] = 1;
				}
			}
		} else {
			$result = false;
		}

		
		$this->piVars['public'] = $this->piVars['public'] == 1 ? 1 : 0;
		$this->piVars['dutyscheduleopen'] = $this->piVars['dutyscheduleopen'] == 1 ? 1 : 0;
		$this->piVars['tags'] = is_array($this->piVars['tags']) ? $this->piVars['tags'] : array();
		$this->piVars['requiredteams'] = is_array($this->piVars['requiredteams']) ? $this->piVars['requiredteams'] : array();

				
		if (@count($this->formValidationErrors) !== 0) {
			$result = false;
		}
		return $result;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi4/class.tx_servicemgr_pi4.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi4/class.tx_servicemgr_pi4.php']);
}

?>
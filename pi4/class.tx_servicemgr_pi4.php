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
/**
 * class.tx_servicemgr_pi4.php
 *
 * includes FrontEnd-Plugin 4 ('Event administration') class for servicemgr extension
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');
if (t3lib_extMgm::isLoaded('date2cal')) require_once(t3lib_extMgm::extPath('date2cal').'src/class.jscalendar.php');

/**
 * Plugin 'Event administration' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi4 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi4';		// Same as class name
	var $scriptRelPath = 'pi4/class.tx_servicemgr_pi4.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	var $ts;			//TimeStamp
	var $JSCalendar;	//JSCalendar (date2cal)

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The	content that is displayed on the website
	 */
	function main($content,$conf,$code='LIST')	{

		$this->init($conf,$code);

		$GLOBALS['TYPO3_DB']->debugOutput = true;

		if ($this->piVars['submit']) {
			$content = $this->doSubmit();
		} else {

			switch ($this->code) {
				CASE 'ADD':
					$content = $this->showForm();
					break;
				CASE 'LIST':
				default:
					$content = $this->showList();
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Does some initialization
	 *
	 * @param	array		$conf: conf array
	 * @return	void
	 */
	function init($conf,$code) {
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();

		$this->tx_init();
		$this->tx_loadLL();

		$this->code = $code;
		if (!empty($this->piVars['code'])) $this->code = $this->piVars['code'];

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
	}

	/**
	 * Returns list with plugin options
	 *
	 * @return	string		HTML
	 */
	function showList() {
		$links[] = $this->tx_linkToPage(
			'Add new Event',
			$GLOBALS['TSFE']->id,
			array($this->prefixId.'[code]'=>'ADD')
		);


		$content = '<ul>';
		foreach ($links as $link) {
			$content .= '<li>'.$link.'</li>';
		}
		$content .= '</ul>';
		return $content;
	}

	/**
	 * Returns Form for adding a new event
	 *
	 * @param	array	$defaultValues: data array with default form data
	 * @return	string	HTML
	 */
	function showForm($defaultValues = array()) {
		$template = $this->cObj->getSubpart($this->template, '###ADDNEWEVENT###');

		$this->initDefaultValues($defaultValues);


		$marker = array(
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
		);

		$marker = array_merge(
			$marker, array(
			'###V_DATE###' => $defaultValues['date'],
			'###V_TIME###' => $defaultValues['time'],
			'###V_DATETIME###' => $defaultValues['datetime'],
			'###V_SUBJECT###' => $defaultValues['subject'],
			'###V_PUBLIC###' => $defaultValues['public'] ? 'checked="checked" ' : '',
			'###V_DUTYSCHEDULEOPEN###' => $defaultValues['dutyscheduleopen'] ? 'checked="checked" ' : '',
			'###V_NOTES###' => $defaultValues['notes'],
			'###V_NOTESINTERNAL###' => $defaultValues['notesinternal'],
		));


		if (is_array($this->formValidationErrors)) {
			$formError = $this->pi_getLL('error_missingfields').' ';
			foreach ($this->formValidationErrors as $k => $v) {
				$formErrors[] = $this->pi_getLL('L_'.strtoupper($k));
			}
			$formError .= implode(', ',$formErrors);
			$formError = $this->throwErrorMsg($formError);
		}
		$marker['###FORM_ERRORS###'] = $formError;


		$marker['###DATE_CAL###'] = $this->getDate2Cal('tx_servicemgr_pi4[date]',$defaultValues['date']);
		$marker['###TIME_CAL###'] = '';


		//SERIES
		$series = $this->getSeries();
		if (!is_array($series)) {
			$seriesContent = $this->pi_getLL('error_noseries');
		} else {
			$seriesContent = '<select name="tx_servicemgr_pi4[series]" id="frm-series">'."\n";
			foreach ($series as $serie) {
				$seriesContent .= '	<option value="'.$serie['uid'].'"';
				if ($serie['uid'] == $defaultValues['series'])
					$seriesContent .= ' selected="selected"';

				$seriesContent .= '>'.$serie['name'].'</option>'."\n";
			}
			$seriesContent .= '</select>'."\n";
			$seriesAddLink = '<img title="'.$this->pi_getLL('title_addlink_series').'" alt="'.$this->pi_getLL('alt_addlink_series').'" src="'.$this->conf['addlink_img'].'" class="addIcon" />';
		}
		$marker['###SERIES_SELECTOR###'] = $seriesContent;
		$marker['###SERIES_ADDLINK###'] = $seriesAddLink;


		//TAGS
		$tags = $this->getTags();
		if (!is_array($series)) {
			$tagsContent = $this->pi_getLL('error_notags');
		} else {
			$defaultValues['tags'] = split(',',$defaultValues['tags']);
			foreach ($tags as $tag) {
				$tagsContent .= '<input type="checkbox" name="tx_servicemgr_pi4[tags][]" value="'.$tag['uid'].'" id="frm-tags-'.$tag['uid'].'"';
				if (in_array($tag['uid'], $defaultValues['tags'])) {
					$tagsContent .= ' checked="checked"';
				}
				$tagsContent .= ' /> <label for="frm-tags-'.$tag['uid'].'">'.$tag['name'].'</label><br />';
			}
		}
		$tagsAddLink = '<img title="'.$this->pi_getLL('title_addlink_tags').'" alt="'.$this->pi_getLL('alt_addlink_tags').'" src="'.$this->conf['addlink_img'].'" />';
		$marker['###TAGS_CBS###'] = $tagsContent;
		$marker['###TAGS_ADDLINK###'] = $tagsAddLink;


		//TEAMS
		$teams = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title',
					'fe_groups', 'deleted=0 AND tx_servicemgr_isteam=1');
		if (!is_array($teams)) {
			$teamsContent = $this->pi_getLL('error_noteams');
		} else {
			$defaultValues['requiredteams'] = split(',',$defaultValues['requiredteams']);
			foreach ($teams as $team) {
				$teamsContent .= '<input type="checkbox" name="tx_servicemgr_pi4[requiredteams][]" value="'.$team['uid'].'" id="frm-requiredteams-'.$team['uid'].'"';
				if (in_array($team['uid'], $defaultValues['requiredteams'])) {
					$teamsContent .= ' checked="checked"';
				}
				$teamsContent .= ' /> <label for="frm-requiredteams-'.$team['uid'].'">'.$team['title'].'</label><br />';
			}
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

	/**
	 * Sets defaultValues with TypoScript conf (if not set before)
	 * and dormats defaultValues for output
	 *
	 * @param	array		$data: defaultValues (call by reference)
	 */
	function initDefaultValues(&$data) {
		if ($data === array()) {
			$data = $this->conf['defaultValues.'];
		}
		foreach ($data as $k => $v) {
					$data[$k] = is_array($v) ? implode(',',$v) : $v;
		}
	}

	/**
	 * Returns date2cal image-buttons
	 *
	 * @param	string		$inputField: name of inputfield (id: name_hr)
	 * @return	string		HTML
	 */
	function getDate2Cal($inputField,$date='') {
		$content = '';
		if (t3lib_extMgm::isLoaded('date2cal')) {
			$this->JSCalendar->setDateFormat(false);
			$this->JSCalendar->setConfigOption('format','%d.%m.%Y');
			$this->JSCalendar->setConfigOption('ifFormat','%d.%m.%Y');
			if (!empty($date)) {
				$this->JSCalendar->setConfigOption('date', $date);
			}
			$this->JSCalendar->setInputField($inputField);
			$content = $this->JSCalendar->renderImages();
		}
		return $content;
	}

	/**
	 * Processes submission of new event
	 *
	 * @return	string	HTML
	 */
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

	/**
	 * Validates submitted data and removes wrapping whitespace
	 *
	 * @return	boolean		if data is valid -> true
	 */
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
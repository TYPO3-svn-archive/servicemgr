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


/**
 * Plugin 'Duty schedule' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi5 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi5';		// Same as class name
	var $scriptRelPath = 'pi5/class.tx_servicemgr_pi5.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	var $pi_checkCHash = true;

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
		
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		
		$this->template = $this->generalConf['TemplateFile'];
		if (!$this->template) {
			$this->template = 'EXT:servicemgr/res/tables.tmpl';
		}
		$this->template = $this->cObj->fileResource($this->template);

		switch ($this->conf['viewmode']) {
			CASE 'personal':
				break;
			CASE 'leader':
				break;
			CASE 'list':
			default:
				$content = $this->listView();
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function listView() {
		$template = $this->cObj->getSubpart($this->template,'###DUTYSCHEDULE###');
		$subparts = array('###HEADERROW###'=>'','###DATAROW###'=>'','###SPACERCELL###'=>'');
		foreach ($subparts as $subpart => $value) $subparts[$subpart] = $this->cObj->getSubpart($template, $subpart);
		
		$teams = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title',
					'fe_groups', 'deleted=0 AND tx_servicemgr_dutyschedule=1');
		
		$events = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,subject,datetime,requiredteams,notes,notes_internal',
					'tx_servicemgr_events', 'deleted=0 AND hidden=0', '', 'datetime ASC');
		
		$cells['header'] = $this->cObj->getSubpart($subparts['###HEADERROW###'],'###HEADERCELL###');
		$cells['data'] = $this->cObj->getSubpart($subparts['###DATAROW###'],'###DATACELL###');
		$cells['spacer'] = $this->cObj->getSubpart($template,'###SPACERCELL###');
		
		$temp['header'][] = $this->cObj->substituteMarker($cells['header'],'###H_LABEL###',$this->pi_getLL('date'));
		$temp['header'][] = $this->cObj->substituteMarker($cells['header'],'###H_LABEL###',$this->pi_getLL('subject'));
		$temp['header'][] = $this->cObj->substituteMarker($cells['header'],'###H_LABEL###',$this->pi_getLL('notes'));
		$temp['header'][] = $cells['spacer'];
		
		foreach ($teams as $key => $team) {
			$temp['header'][] = $this->cObj->substituteMarker($cells['header'],'###H_LABEL###',$team['title']);
			$teams[$key]['members'] = $this->getTeamMembers($team['uid']);
		}
		
		foreach ($events as $event) {
			$duty = $this->getSingleSchedule($event['uid']);
						
			$temp['data'] = array();
			$temp['data'][] = $this->cObj->substituteMarker($cells['data'],'###H_VALUE###',date('d.m.Y', $event['datetime']));
			$temp['data'][] = $this->cObj->substituteMarker($cells['data'],'###H_VALUE###',$event['subject']);
			$temp['data'][] = $this->cObj->substituteMarker($cells['data'],'###H_VALUE###',$event['notes'].'<br />'.$event['notes_internal']);
			$temp['data'][] = $cells['spacer'];

			foreach ($teams as $team) {
				$temp['duty'] = array();
				if (!is_array($duty[$team['uid']])) {
					$temp['duty'][] = '';
				} else {
					foreach ($duty[$team['uid']] as $key => $value) {
						$temp['duty'][] = $team['members'][$value]['name'];
					}
				}
				$temp['data'][] = $this->cObj->substituteMarker($cells['data'],'###H_VALUE###',implode(', ',$temp['duty']));
			}
			
			$temp['rows'][] = $this->cObj->substituteSubpart($subparts['###DATAROW###'],'###DATACELL###', implode('',$temp['data']));
		}
		
		$temp['header'] = $this->cObj->substituteSubpart($subparts['###HEADERROW###'],'###HEADERCELL###', implode('', $temp['header']));
		$temp['data'] = $this->cObj->substituteSubpart($subparts['###DATAROW###'],'###DATACELL###', implode('', $temp['rows']));
		
		$content = $template;
		$content = $this->cObj->substituteSubpart($content, '###HEADERROW###', $temp['header']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', $temp['data']);
		$content = $this->cObj->substituteSubpart($content, '###SPACERCELL###', '');
		return $content;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi5/class.tx_servicemgr_pi5.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi5/class.tx_servicemgr_pi5.php']);
}

?>
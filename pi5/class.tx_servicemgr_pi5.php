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
		$this->userID = $GLOBALS['TSFE']->fe_user->user[uid];
		$this->ts = mktime(0,0,0);
		$this->fetchConfigValue('viewmode');
		
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		
		if ($this->piVars['submit']) {
			$this->doSubmit($this->piVars['submittype']);
		}
		
		switch ($this->conf['viewmode']) {
			CASE 'personal':
				$content = $this->personalView($this->userID);
				break;
			CASE 'leader':
				break;
			CASE 'list':
			default:
				$content = $this->listView();
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * Returns personal duty schedule form
	 *
	 * @param	integer		$userID: uid of fe_user
	 * @return	string		HTML
	 */
	function personalView($userID) {
		
		$user = $this->getUserData(intval($userID));
		
		if (!empty($user['usergroup'])) {
			$teamsAndWhere = ' AND uid in ('.$user['usergroup'].')';
		}
		
		$teams = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,tx_servicemgr_dsname',
					'fe_groups', 'deleted=0 AND tx_servicemgr_dutyschedule=1'.$teamsAndWhere);
		
		$events = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,subject,datetime,requiredteams,notes,notes_internal',
					'tx_servicemgr_events', 'deleted=0 AND hidden=0 AND dutyscheduleopen=1 AND datetime>'.$this->ts, '', 'datetime ASC');
		
		$eventInfo = array('datetime','subject','notes','notes_internal');
		
		$data = array();
		$data[] = array_merge(
			$this->getEventHeaders(),
			array(array('&nbsp;','dutyschedule-spacer')),
			$this->getTeamHeaders($teams)
		);
		
		foreach ($events as $event) {
			$duty = $this->getSingleSchedule($event['uid']);
						
			$cellData = $this->getEventInformation($event,$eventInfo);
			$cellData[] = array('&nbsp;','dutyschedule-spacer');

			//Set data for each cell/team
			foreach ($teams as $team) {
				$temp['duty'] = array();
				if (!is_array($duty[$team['uid']])) {
					$temp['duty'][] = '<input type="checkbox" value="'.$user['uid'].'" name="tx_servicemgr_pi5[dutydata]['.$event['uid'].']['.$team['uid'].']" />';
				} else {
					
					if (in_array($user['uid'],$duty[$team['uid']])) {
						$temp['duty'][] = '<input type="checkbox" value="'.$user['uid'].'" name="tx_servicemgr_pi5[dutydata]['.$event['uid'].']['.$team['uid'].']" checked="checked" />';
					} else {
						$temp['duty'][] = '<input type="checkbox" value="'.$user['uid'].'" name="tx_servicemgr_pi5[dutydata]['.$event['uid'].']['.$team['uid'].']" />';
					}
					
					foreach ($duty[$team['uid']] as $key => $value) {
						if ($value != $user['uid']) {
							$temp['duty'][] = $team['members'][$value]['first_name'];
						}
					}
				}
				
				$cellData[] = array(
					implode(', ',$temp['duty']),
					$this->getCssClass($event['requiredteams'],$team),
				);
				
			}
			
			$data[] = $cellData;
		}
		
		$actionLink = $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'addQueryString' => 1,
			'addQueryString.' => array(
				'exclude' => 'cHash,no_cache',
			),
			'additionalParams' => '&no_cache=1',
			'useCacheHash' => false,
		));
		
		$content = '<form method="post" action="'.$actionLink.'">'; 
		$content .= $this->setData2Template($data);
		$content .= '<input type="hidden" name="'.$this->prefixId.'[submittype]" value="personal" />';
		$content .= '<div style="float:right;"><input type="submit" name="'.$this->prefixId.'[submit]" value="'.$this->pi_getLL('submitform').'" /></div><div class="clear"></div>';
		$content .= '</form>';
		
		return $content;
	}
	
	/**
	 * Returns array with user data
	 *
	 * @param	integer		$feuser_uid: uid of fe_user
	 * @return	array		user data
	 */
	function getUserData($feuser_uid) {
		//get content from database
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, username, first_name, last_name, date_of_birth, school, grade, showname, family, image, usergroup',   #select
        	'fe_users', #from
        	'deleted=0 and uid='.$feuser_uid  #where
		);

		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns general duty schedule table
	 *
	 * @return	string		HTML
	 */
	function listView() {
				
		$teams = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,tx_servicemgr_dsname',
					'fe_groups', 'deleted=0 AND tx_servicemgr_dutyschedule=1');
		
		
		$events = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,subject,datetime,requiredteams,notes,notes_internal',
					'tx_servicemgr_events', 'deleted=0 AND hidden=0', '', 'datetime ASC');
		
		$eventInfo = array('datetime','subject','notes','notes_internal');
		
		
		$data = array();
		$data[] = array_merge(
			$this->getEventHeaders(),
			array(array('&nbsp;','dutyschedule-spacer')),
			$this->getTeamHeaders($teams)
		);
				
		foreach ($events as $event) {
			$duty = $this->getSingleSchedule($event['uid']);
						
			$cellData = $this->getEventInformation($event,$eventInfo);
			$cellData[] = array('&nbsp;','dutyschedule-spacer');

			//Set data for each cell/team
			foreach ($teams as $team) {
				
				$temp['duty'] = array();
				
				if (!is_array($duty[$team['uid']])) {
					$temp['duty'][] = '&nbsp;';
				} else {
					
					foreach ($duty[$team['uid']] as $key => $value) {
						$temp['duty'][] = $team['members'][$value]['first_name'];
					}
				
				}					
				
				$cellData[] = array(
					implode(', ',$temp['duty']),
					$this->getCssClass($event['requiredteams'],$team),
				);	
			}
			$data[] = $cellData;
		}
				
		$content = $this->setData2Template($data);
		return $content;
	}
	
	/**
	 * Returns css-class for a team-table-cell
	 *
	 * @param	mixed		$requiredTeams: arary or comma-seperated list of required teams
	 * @param 	array		$team: team data
	 * @return	string		css-class
	 */
	function getCssClass($requiredTeams,$team) {
		$cssClass = 'dsc-normal';
		
		if (!is_array($requiredTeams)) {
			$requiredTeams = split(',',$requiredTeams);
		}
		
		if (!in_array($team['uid'],$requiredTeams)) {
			$cssClass = 'dsc-notneeded';
		}
		
		return $cssClass;
	}
	
	/**
	 * Builds HTML output for duty schedule
	 *
	 * @param	array		$data: data to be displayed $data[row][cell][data/cssClass]
	 * @return	string		HTML
	 */
	function setData2Template($data) {
		$template = $this->cObj->getSubpart($this->template,'###DUTYSCHEDULE###');
		$subparts = $this->getSubparts($template, array('###HEADERROW###','###DATAROW###','###HEADERCELL###','###DATACELL###'));
				
		$tempData['header'] = $data[0];
		
		//SET HEADER-DATA
		foreach ($tempData['header'] as $headData) {
			$marker = array(
				'###H_LABEL###' => $headData[0],
				'###LABEL_CLASS###' => $headData[1],
			);
			$temp[] = $this->cObj->substituteMarkerArray($subparts['###HEADERCELL###'],$marker);
		}
		$temp['header'][] = implode('',$temp);

		unset($data[0]);
		
		//SET ROWS
		foreach ($data as $rowData) {
			
			//SET CELLS
			$temp['data'] = array();			
			foreach ($rowData as $cellData) {
				$markers = array(
					'###H_VALUE###' => $cellData[0],
					'###VALUE_CLASS###' => $cellData[1],
				);
				$temp['data'][] = $this->cObj->substituteMarkerArray($subparts['###DATACELL###'],$markers);	
			}
			$temp['rows'][] = $this->cObj->substituteSubpart($subparts['###DATAROW###'],'###DATACELL###', implode('',$temp['data']));
		}
						
		$subpartContent['###HEADERROW###'] = $this->cObj->substituteSubpart($subparts['###HEADERROW###'],'###HEADERCELL###', implode('', $temp['header']));
		$subpartContent['###DATAROW###'] = $this->cObj->substituteSubpart($subparts['###DATAROW###'],'###DATACELL###', implode('', $temp['rows']));
							
		$content = $this->substituteMarkersAndSubparts($template,array(),$subpartContent);
		return $content;
	}
	
	/**
	 * Returns array with EventHeaders
	 *
	 * @return	array		array[][data][cssClass]
	 */
	function getEventHeaders() {
		$temp[] = array($this->pi_getLL('date'),'');
		$temp[] = array($this->pi_getLL('subject'),'');
		$temp[] = array($this->pi_getLL('notes'),'');
		return $temp;
	}
	
	/**
	 * Returns Team headers
	 * and sets team members in given teams array -> $teams[members]
	 *
	 * @param	array		$teams: array of teams
	 * @return	array		data[cell][data/cssClass]
	 */
	function getTeamHeaders(&$teams) {
		$temp = array();
		if (is_array($teams)) {
			foreach ($teams as $key => $team) {
				$name = empty($team['tx_servicemgr_dsname']) ? $team['title'] : $team['tx_servicemgr_dsname'];
				$temp[] = array(str_replace(' ','&nbsp;',$name),'');
				$teams[$key]['members'] = $this->getTeamMembers($team['uid']);
			}
		}
		return $temp;
	}
	
	/**
	 * Returns event information
	 *
	 * @param	array		$event: event data
	 * @param	array		$infos: table fields to be displayed
	 * @return	array		data[cell][data/cssClass]
	 */
	function getEventInformation($event,$infos) {
		$event['datetime'] = date('d.m.Y', $event['datetime']);
		
		if (in_array('notes',$infos) && in_array('notes_internal',$infos)) { 
			if (!empty($event['notes'])) $event['notes'] .= '<br />'; 
			$event['notes'] .= $event['notes_internal'];
			unset($infos[array_search('notes_internal',$infos)]);
		}
		
		$temp = array();
		
		if (is_array($event) && is_array($infos)) {
			foreach ($infos as $info) {
				$temp[] = array(empty($event[$info]) ? '&nbsp;' : $event[$info],'dsc-eventinfo');	
			}
		}
		
		return $temp;
	}
	
	/**
	 * Processes duty schedule submission
	 * and calls function for specific submission type
	 *
	 * @param	string		$submitType: type of submitted data
	 */
	function doSubmit($submitType) {
		switch ($submitType) {
			CASE 'personal':
				
				break;
			CASE 'team':
				break;
			default:
		}
	}
	
	/**
	 * 	Returns a subpart from the input content stream.
	 *
	 * @param	string		$template: The content stream, typically HTML template content.
	 * @param	array		$markerArray: The marker array, typically on the form array("###[the marker string]###")
	 * @return	array		The subparts found, if found.
	 */
	function getSubparts($template, $markerArray) {
		$content = array();
		foreach ($markerArray as $marker) {
			$content[$marker] = $this->cObj->getSubpart($template, $marker);
		}
		return $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi5/class.tx_servicemgr_pi5.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi5/class.tx_servicemgr_pi5.php']);
}

?>
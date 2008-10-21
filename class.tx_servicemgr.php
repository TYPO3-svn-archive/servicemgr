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

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
require_once(PATH_t3lib.'class.t3lib_tsparser_ext.php');

 /**
  * Top level class for the 'servicemgr' extension.
  *
  * Class contains general functions for the servicemgr-extension.
  * All plugin classes extend this class.
  *
  * $Id:
  *
  * @author		Peter Schuster <typo3@peschuster.de>
  * @package		TYPO3
  * @subpackage 	tx_servicemgr
  */
class tx_servicemgr extends tslib_pibase {
	var $prefixId		= 'tx_servicemgr';		// Same as class name
	var $scriptRelPath	= 'class.tx_servicemgr.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'servicemgr';	// The extension key.
	var $extConf;		// extension conf from TYPO3_CONF_VARS
	var $generalConf;	// TypoScript conf for plugin.tx_servicemgr

	/**
	 * Functions sets conf values and gets TypoScript conf
	 *
	 * @return	boolean		returns true when initiated succesfull
	 */
	function tx_init() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['servicemgr']);

		$this->generalConf = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$this->generalConf->tt_track = 0;
		$this->generalConf->init();
		$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootLine = $sys_page->getRootLine($GLOBALS['TSFE']->id);
		$this->generalConf->runThroughTemplates($rootLine);
		$this->generalConf->generateConfig();
		$this->generalConf = $this->generalConf->setup['plugin.']['tx_servicemgr.'];
		
		$this->pi_initPIflexForm();

		$GLOBALS['TSFE']->additionalHeaderData['tx_servicemgr_css'] = '	<link rel="stylesheet" type="text/css" href="typo3conf/ext/servicemgr/res/tables.css" />';
		return true;
	}

	/**
	 * Fetches configuration value from flexform. If value exists, value in
	 * <code>$this->conf</code> is replaced with this value.
	 *
	 * @author Dmitry Dulepov <dmitry@typo3.org>
	 * @param	string		$param	Parameter name. If <code>.</code> is found, the first part is section name, second is key (applies only to $this->conf)
	 * @return	void

	 */
	function fetchConfigValue($param) {
		if (strchr($param, '.')) {
			list($section, $param) = explode('.', $param, 2);
		}
		$value = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], $param, ($section ? 's' . ucfirst($section) : 'sDEF')));
		if (!is_null($value) && $value != '') {
			if ($section) {
				$this->conf[$section . '.'][$param] = $value;
			}
			else {
				$this->conf[$param] = $value;
			}
		}
	}
	
	/**
	 * loads LOCAL_LANG out of locallang_common.xml in extension base dir
	 *
	 * @return	void
	 */
	function tx_loadLL() {

		if ($this->LOCAL_LANG_loaded !== 1) {
			$this->pi_loadLL();
		}

		//locallang_common.xml is in extension basepath
		$basePath = t3lib_extMgm::extPath($this->extKey).'locallang_common.xml';

		//load locallang_common.xml
		$tempLOCAL_LANG = t3lib_div::readLLfile($basePath,$this->LLkey,$GLOBALS['TSFE']->renderCharset);

		//locallang_common.xml and locallang.xml get merged
		//locallang.xml overwrites locallang_common.xml
		$oldLL = $this->LOCAL_LANG;
		if (is_array($tempLOCAL_LANG)) {
			reset($tempLOCAL_LANG);
			while(list($k,$lA)=each($tempLOCAL_LANG)) {
				if (is_array($lA))	{
					foreach($lA as $llK => $llV)	{
						if (!is_array($llV))	{
							if ($oldLL[$k][$llK] == NULL) {
								$oldLL[$k][$llK] = $llV;
							}
						}
					}
				}
			}
		}
		$this->LOCAL_LANG = $oldLL;

		//set flag
		$this->LOCAL_LANG_COMMON_loaded = 1;
	}

	/**
	 * Returns all fe_users being part of a specific team
	 *
	 * @param	integer		$teamUID:		UID of specific team
	 * @param	boolean		$fillName:	when true and column name is empty username gets copied to name
	 * @return	array		uid, username, name, usergroup of all teammembers
	 */
	function getTeamMembers($teamUID, $fillName=true) {
		//get alle fe_users from database
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, username, name, usergroup',   #select
        	'fe_users', #from
        	'usergroup<>\'\' AND deleted=0'  #where
		);

		// dump arrays
		$returnValue = array();

		if ($res) {
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

				//fillName
				if ($fillName && $row['name'] == '') {
					$row['name'] = $row['username'];
				}

				// fill return value with all users being part of team with uid = $teamUID
				if (in_array($teamUID, split(',', $row['usergroup']))) {
					$returnValue[$row['uid']] = $row;
				}
			}
		}

		//if there is a result -> return
		if (count($returnValue) !== 0) {
			return $returnValue;
		} else {
			return false;
		}
	}

	/**
	 * Returns all tags from database
	 * gets uid, name, parrent
	 *
	 * @return	array		tags as array with key=uid
	 */
	function getTags() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, name, parrent',   #select
        	'tx_servicemgr_tags', #from
        	'hidden=0 AND deleted=0'  #where
		);


		$returnValue = array();
		if ($res) {
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$returnValue[$row['uid']] = $row;
			}
		}

		//if there is a result -> return
		if (count($returnValue) !== 0) {
			return $returnValue;
		} else {
			return false;
		}
	}

	/**
	 * gets information about an single event from database
	 *
	 * @param	integer		$eventId: UID of event
	 * @return	array		uid, datetime, subject, public, series, tags, requiredteams, documents, notes
	 */
	function getSingleEvent($eventId) {
		$resEvent = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime, subject, public, series, tags, requiredteams, documents, notes',   #select
        	'tx_servicemgr_events', #from
        	'uid='.$eventId.' and hidden=0 and deleted=0',  #where
			'', '',
			'0,1' #limit
		);

		if ($resEvent) {
			return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resEvent);
		} else {
			return false;
		}
	}

	/**
	 * Returns all available series from database
	 * gets uid and name of all series
	 *
	 * @return	array		array key = uid
	 */
	function getSeries($uid = '') {
		$where = 'hidden=0 AND deleted=0';
		if (!empty($uid)) {
			$where .= ' AND uid='.intval($uid);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, name',   #select
        	'tx_servicemgr_series', #from
        	$where  #where
		);

		// dump arrays
		$returnValue = array();

		if ($res) {
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

				// fill return value with tags
				$returnValue[$row['uid']] = $row;
			}
		}

		//if there is a result -> return
		if (count($returnValue) !== 0) {
			return $returnValue;
		} else {
			return false;
		}
	}

	/**
	 * Returns single schedule out of database
	 *
	 * @param	integer		$eventId: UID of event
	 * @return	array		unserialized duty column
	 */
	function getSingleSchedule($eventId) {
		$resSchedule = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, event, duty', #select
			'tx_servicemgr_dutyschedule', #from
			'event='.$eventId.' and hidden=0 and deleted=0' #where
		);

		if ($resSchedule) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resSchedule);
			if (unserialize($row['duty']) == false) {
				return array();
			} else {
				return unserialize($row['duty']);
			}
		} else {
			return false;
		}
	}

	/**
	 * get all sermon entries in database for specific event
	 * return array is 2 dimensional
	 *
	 * @param	integer		$eventId: UID of event
	 * @return	array		2-dimensional! (e.g. content[0]['title'])
	 */
	function getAudioFiles($eventId) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, event, title, file, filedate, playtime, filesize, bitrate, album',   #select
        	'tx_servicemgr_sermons', #from
        	'event='.$eventId.' and hidden=0 and deleted=0'  #where
		);

		$resultVar = array();
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$resultVar[] = $row;
			}
			return $resultVar;
		} else {
			return false;
		}
	}

	/**
	 * Returns number of audio files for a specific event
	 *
	 * @param	integer		$eventId: UID of event
	 * @return	integer		number of audiofiles
	 */
	function getAudiosPerEvent($eventId) {
		return count($this->getAudioFiles($eventId));
	}

	/**
	 * returns a detail view for an single event
	 *
	 * @param	integer	$eventId: UID of Event
	 * @param	array	$config: Configuration Array
	 */
	function detailViewEvent($eventId, $config, $template) {
		$row = $this->getSingleEvent(intval($eventId));
		
		$subparts = array(
			'subject' => $this->cObj->getSubpart($template, '###SP_SUBJECT###'),
			'datetime' => $this->cObj->getSubpart($template, '###SP_DATETIME###'),
			'series' => $this->cObj->getSubpart($template, '###SP_SERIES###'),
			'tags' => $this->cObj->getSubpart($template, '###SP_TAGS###'),
			'notes' => $this->cObj->getSubpart($template, '###SP_NOTES###'),
			'sermon' => $this->cObj->getSubpart($template, '###SP_SERMON###'),
			'backlink' => $this->cObj->getSubpart($template, '###SP_BACKLINK###'),
		);
		
		$subpartContent = array();

		if (in_array('subject',$config['subparts']) && !empty($row['subject'])) {
			$subpartContent['###SP_SUBJECT###'] = $this->cObj->substituteMarker($subparts['subject'], '###SUBJECT###', $row['subject']);
		} else { 
			$subpartContent['###SP_SUBJECT###'] = '';
		}
		
		if (in_array('datetime',$config['subparts']) && !empty($row['datetime'])) {
			$subpartContent['###SP_DATETIME###'] = $this->cObj->substituteMarkerArray(
				$subparts['datetime'], 
				array(
					'###DATE###' => date('d.m.Y', $row['datetime']),
					'###TIME###' => date('H:i', $row['datetime']),
				)
			);
		} else { 
			$subpartContent['###SP_DATETIME###'] = '';
		}
		
		if (in_array('series',$config['subparts']) && !empty($row['series'])) {
			$series = $this->getSeries($row['series']);
			$subpartContent['###SP_SERIES###'] = $this->cObj->substituteMarkerArray(
				$subparts['series'], 
				array(
					'###L_SERIES###' => $this->pi_getLL('series'),
					'###SERIES###' => $series[$row['series']]['name'],
				)
			);
		} else { 
			$subpartContent['###SP_SERIES###'] = '';
		}
		
		if (in_array('tags',$config['subparts']) && !empty($row['tags'])) {
			$subpartContent['###SP_TAGS###'] = $this->cObj->substituteMarkerArray(
				$subparts['tags'], 
				array(
					'###L_TAGS###' => $this->pi_getLL('tags'),
					'###TAGS###' => $tags,
				)
			);
		} else { 
			$subpartContent['###SP_TAGS###'] = '';
		}
		
		if (in_array('notes', $config['subparts']) && !empty($row['notes'])) {
			$subpartContent['###SP_NOTES###'] = $this->cObj->substituteMarker($subparts['notes'], '###NOTES###', $row['notes']);
		} else { 
			$subpartContent['###SP_NOTES###'] = '';
		}

		if (in_array('sermon',$config['subparts'])) {
			
			if (t3lib_extMgm::isLoaded('audioplayer')) {
				require_once(t3lib_extMgm::extPath('audioplayer').'class.tx_audioplayer.php');
				$audioplayer = t3lib_div::makeInstance('tx_audioplayer');
				$audioplayer->init();
				$audioplayer->setOptions(array('initialvolume'=>'100','animation'=>'no'));
			} else {
				$player = '';
			}
			
			$audioFiles = $this->getAudioFiles($eventId);
			$allPreachers = $this->getTeamMembers($this->generalConf['PreacherTeamUID']);
			$duty = $this->getSingleSchedule($eventId);
			$preacher = $duty[$this->generalConf['PreacherTeamUID']];
			if (is_array($preacher)) {
				$outPreacher = '';
				foreach($allPreachers as $singlePreacher) {
					if (in_array($singlePreacher['uid'], $preacher)) {
						$outPreacher .= $this->pi_linkToPage(
							$singlePreacher['name'],
							$this->generalConf['preacherdetailPID'],'',
							array('tx_feuser_pi2[showUid]'=>$singlePreacher['uid'])
						);
					}
				}
			}
			
			$subpartContent['###SP_SERMON###'] = '';
			foreach ($audioFiles as $audioFile) {
				$subpartContent['###SP_SERMON###'] .= $this->cObj->substituteMarkerArray(
					$subparts['sermon'], 
					array(
						'###PREACHER###' => $outPreacher,
						'###SUBJECT###' => $audioFile['title'],
						'###PLAYER###' => $audioplayer ? $audioplayer->getFlashPlayer($audioFile['file'], $audioFile['uid']) : '',
					)
				);
			}
			
		} else { 
			$subpartContent['###SP_SERMON###'] = '';
		}
		
		if (in_array('notes', $config['subparts']) && !empty($row['notes'])) {
			$subpartContent['###SP_NOTES###'] = $this->cObj->substituteMarker($subparts['notes'], '###NOTES###', $row['notes']);
		} else { 
			$subpartContent['###SP_NOTES###'] = '';
		}
		
		if (in_array('backlink',$config['subparts']) && !empty($config['backlink'])) {
			$subpartContent['###SP_BACKLINK###'] = $this->cObj->substituteMarker(
				$subparts['backlink'], 
				'###BACKLINK###', 
				$this->pi_linkToPage($config['backlink']['str'],$config['backlink']['id'])
			);
		} else { 
			$subpartContent['###SP_BACKLINK###'] = '';
		}
				
		$content = $this->substituteMarkersAndSubparts($template,array(),$subpartContent);
		
		return $content;	
		
		
	}
	
	/**
	 * extracts extension from filename
	 *
	 * @param	string		$filename: name of file
	 * @return	string		extension of file (without leading dot)
	 */
	function fileExtension($filename){
		$parts = split('\.', $filename);
		$parts = array_reverse($parts, false);
		return $parts[0];
	}

	/**
	 * Returns formarted error message
	 *
	 * @param	string		$msg: error message
	 * @return	string		formarted error message
	 */
	function throwErrorMsg($msg) {
		return '<div class="tx_servicemgr_errormsg">'.$msg.'</div>';
	}

	/**
	 * Returns typolink with cHash
	 *
	 * @param	string		$str			link text
	 * @param	integer		$id				uid of page
	 * @param	array		$urlParameter	array of url parameters
	 * @return	string		typolink
	 */
	function tx_linkToPage($str, $id, $urlParameter) {
		$additionalParams = '';
		foreach ($urlParameter as $key => $value) {
			$additionalParams .= '&'.$key.'='.$value;
		}
		$content = $this->cObj->typoLink(
        	$str,
        	array (
        		'parameter' => $id,
        		'useCacheHash'=>1,
        		'additionalParams'=>$additionalParams,
        	)
        );
        return $content;
	}
	
	/**
	 * Replaces $this->cObj->substituteArrayMarkerCached() because substitued
	 * function polutes cache_hash table a lot.
	 *
	 * @author	Dmitry Dulepov (dmitry@typo3.org)
	 * 
	 * @param	string		$template	Template
	 * @param	array		$markers	Markers
	 * @param	array		$subparts	Subparts
	 * @return	string		HTML

	 */
	function substituteMarkersAndSubparts($template, array $markers, array $subparts) {
		$content = $this->cObj->substituteMarkerArray($template, $markers);
		if (count($subparts) > 0) {
			foreach ($subparts as $name => $subpart) {
				$content = $this->cObj->substituteSubpart($content, $name, $subpart);
			}
		}
		return $content;
		fore
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr.php']);
}

?>
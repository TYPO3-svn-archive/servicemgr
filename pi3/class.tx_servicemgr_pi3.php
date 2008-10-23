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
require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr_mp3.php');

/**
 * Plugin 'Sermon administration' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi3 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi3';		// Same as class name
	var $scriptRelPath = 'pi3/class.tx_servicemgr_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	var $pi_checkCHash = true;
	var $template;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();

		$this->tx_init();
		$this->tx_loadLL();

		//DEBUG-CONFIG
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		#t3lib_div::debug($this->conf, 'TypoScript');
		#t3lib_div::debug($this->extConf, 'extConf');
		#t3lib_div::debug($this->generalConf, 'generalConf');
		#t3lib_div::debug($this->piVars, 'piVars');

		$this->piVars['eventId'] = intVal($this->piVars['eventId']);

		$this->template = $this->generalConf['TemplateFile'];
		if (!$this->template) {
			$this->template = 'EXT:servicemgr/res/tables.tmpl';
		}
		$this->template = $this->cObj->fileResource($this->template);

		switch ($this->piVars['action']) {
			CASE 'detail':
				$content = $this->detailView($this->piVars['eventId']);
				break;

			CASE 'uploadform':
				$content = $this->showUpload($this->piVars['eventId']);
				break;

			CASE 'doupload':
				$content = $this->doUpload($this->piVars['eventId']);
				break;

			CASE 'list':
			default:
				$content = $this->listView();
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * lists all events
	 *
	 * @return	string		table of events
	 */
	function listView() {
		//Template preparation
		#Subpart
		$subpart = $this->cObj->getSubpart($this->template,'###SERMONLISTADMIN###');
		#header row
		$headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
		#single row
		$singlerow = $this->cObj->getSubpart($subpart,'###ROW###');

		//substitue table header in template file
		$markerArray['###HDATE###'] = $this->pi_getLL('date');
		$markerArray['###HSUBJECT###'] = $this->pi_getLL('subject');
		$markerArray['###HSERIES###'] = $this->pi_getLL('series');
		$markerArray['###HAUDIOFILES###'] = $this->pi_getLL('audiofiles');
		$subpartArray['###HEADERROW###'] = $this->cObj->substituteMarkerArray($headerrow,$markerArray);

		//get content from database
		$resEvent = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime, subject, series',   #select
        	'tx_servicemgr_events', #from
        	'hidden=0 and deleted=0 and public=1'  #where
		);


		//substitue table rows in template
		if ($resEvent) {
			while ($rowEvent=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resEvent)) {

				if (!empty($rowEvent['series'])) {
					$resSeries = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        				'name',   #select
        				'tx_servicemgr_series', #from
        				'uid='.$rowEvent['series'].' and hidden=0 and deleted=0'
					);
					$rowSeries=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resSeries);
					$rowEvent['series'] = $rowSeries['name'];
				} else {
					$rowEvent['series'] = '-';
				}

				$audioFiles = $this->getAudioFiles($rowEvent['uid']);
				$rowEvent['audiofiles'] = '';
				foreach ($audioFiles as $audioFile) {
					$rowEvent['audiofiles'].=$audioFile['title'].'<br />';
				}

				$markerArray['###DATE###'] = date('d.m.Y', $rowEvent['datetime']);
				$markerArray['###SUBJECT###'] = $this->tx_linkToPage(
					$rowEvent['subject'],
					$GLOBALS['TSFE']->id,
					array(
						$this->prefixId.'[eventId]'=>$rowEvent['uid'],
						$this->prefixId.'[action]'=>'detail'
					)
				);
				$markerArray['###SERIES###'] = $rowEvent['series'];
				$markerArray['###AUDIOFILES###'] = $rowEvent['audiofiles'];

				if (empty($rowEvent['audiofiles'])) {
					$markerArray['###UPLOAD###'] = $this->cObj->typoLink(
						'&gt;',
						array(
							'parameter' => $GLOBALS['TSFE']->id,
							'addQueryString' => 1,
							'addQueryString.' => array(
								'exclude' => 'cHash,no_cache',
							),
							'additionalParams' => implode('&',array(
								'&no_cache=1',
								$this->prefixId.'[eventId]='.$rowEvent['uid'],
								$this->prefixId.'[action]=uploadform'
							)),
							'useCacheHash' => false,
						)
					); 
				} else {
					$markerArray['###UPLOAD###'] = '';
				}
				$liste .= $this->cObj->substituteMarkerArray($singlerow,$markerArray);
			}
			$subpartArray['###ROW###']=$liste;
		}

		return $this->substituteMarkersAndSubparts($subpart,$markerArray,$subpartArray); ;
	}

	/**
	 * detail view of event with information about sermon-audio-file(s)
	 *
	 * @param	integer		$eventId: uid of event
	 * @return	string		detail view
	 */
	function detailView($eventId) {
		if (t3lib_extMgm::isLoaded('audioplayer')) {
			require_once(t3lib_extMgm::extPath('audioplayer').'class.tx_audioplayer.php');
		} else {
			die('Extension "audioplayer" not loaded!');
		}
		$audioplayer = t3lib_div::makeInstance('tx_audioplayer');
		$audioplayer->init();
		$audioplayer->setOptions(array('initialvolume'=>'100'));
		return $audioplayer->getFlashPlayer('fileadmin/predigten/1-20080613-1.mp3');
	}

	/**
	 * Returns upload form for specific event
	 *
	 * @param	integer		$eventId: uid of event
	 * @param	array		$previousUploadData: data array of previous, failed upload
	 * @return	string		upload form
	 */
	function showUpload($eventId, $previousUploadData = array()) {

		//init template and set static labels
		$subpart = $this->cObj->getSubpart($this->template,'###SERMONUPLOADFORM###');
		$markerArray['###TITLE###'] = $this->pi_getLL('uploadTitle');
		$markerArray['###HSUBJECT###'] = $this->pi_getLL('subject');
		$markerArray['###HDATE###'] = $this->pi_getLL('date');
		$markerArray['###HTAGS###'] = $this->pi_getLL('tags');

		//get data out of database
		$singleEvent=$this->getSingleEvent($eventId);
		if ($singleEvent == false) {
			return $this->pi_getLL('ERROR.wrongEventKey').
					$this->listView();
		}

		$allTags = $this->getTags();

		$duty = $this->getSingleSchedule($eventId);
		if ($duty === false) {
			return $this->pi_getLL('ERROR.wrongEventKey');
		}

		$allPreachers = $this->getTeamMembers($this->generalConf['PreacherTeamUID']); // get all teammembers of preacher-team
		
		if ($previousUploadData === array()) {
			$actTags = split(',', $singleEvent['tags']); // get tags for this event
			$actPreachers = $duty[$this->generalConf['PreacherTeamUID']]; // get preacher(s) for this event
		} else {
			$actTags = $previousUploadData['data']['tags'];
			$actPreachers = $previousUploadData['data']['preachers'];
		}
		
		// generate output
		$outputTags = '';
		foreach($allTags as $tag) {
			$outputTags .= '<input type="checkbox" value="'.$tag['uid'].'" name="'.$this->prefixId.'[upload][tags][]" id="tag_'.$tag['uid'].'"';
			if (is_array($actTags)) {
				if (in_array($tag['uid'], $actTags)) {
					$outputTags .= ' checked="checked"';
				}
			}
			$outputTags .= '/><label for="tag_'.$tag['uid'].'">'.$tag['name'].'</label><br/>
			';
		}


		// if atleast one person is in preacher-team
		if(count($allPreachers) !== 0) {
			
			if (!is_array($actPreachers) && empty($actPreachers)) {
				$actPreachers = array();
			}

			// wrap preachers in select element
			$outputPreachers = '';
			if (count($actPreachers) > 1) {
				$outputPreachers = '<select size="3" id="'.$this->prefixId.'[upload][preachers]" name="'.$this->prefixId.'[upload][preachers][]" multiple="multiple">
				';
			} else {
				$outputPreachers = '<select size="1" id="'.$this->prefixId.'[upload][preachers]" name="'.$this->prefixId.'[upload][preachers][]">
				';
			}

			foreach ($allPreachers as $singlePreacher) {
				$outputPreachers .= '<option value="'.$singlePreacher['uid'].'"';
				if (in_array($singlePreacher['uid'], $actPreachers)){
					$outputPreachers .= ' selected="selected"';
				}
				$outputPreachers .= '>'.$singlePreacher['name'].'</option>
				';
			}
			$outputPreachers .= '</select>';
		}


		//
		// File
		//
		$markerArray['###HFILE###'] = '<label for="'.$this->prefixId.'[upload][file]">'.$this->pi_getLL('file').'</label>';
		$markerArray['###FILE###'] = '<input name="'.$this->prefixId.'" id="'.$this->prefixId.'[upload][file]" type="file" size="30" />';

		//
		// combine output
		//
		$markerArray['###SUBJECT###'] = $singleEvent['subject'];
		$markerArray['###DATE###'] = date('d.m.Y - H:i', $singleEvent['datetime']).' h';
		$markerArray['###PREACHER###'] = '<label for="'.$this->prefixId.'[upload][preachers]">'.$this->pi_getLL('preacher').':</label>&nbsp;'.$outputPreachers;
		$markerArray['###TAGS###'] = $outputTags;
		$markerArray['###SUBMIT###'] = '<input type="submit" name="'.$this->prefixId.'[upload][submit]" value="'.$this->pi_getLL('submitform').'" />';

		$actionLink = $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'addQueryString' => 1,
			'addQueryString.' => array(
				'exclude' => 'cHash,no_cache',
			),
			'additionalParams' => '&no_cache=1',
			'useCacheHash' => false,
		)); 
		
		$content = '<form name="'.$this->prefixId.'[upload]" action="'.$actionLink.'" method="post" enctype="multipart/form-data">';
		$content .= '<input type="hidden" name="'.$this->prefixId.'[action]" value="doupload" />';
		$content .= '<input type="hidden" name="'.$this->prefixId.'[eventId]" value="'.$eventId.'" />';
		$content .= $this->cObj->substituteMarkerArray($subpart,$markerArray);
		$content .= '</form>';

		return $content;
	}

	/**
	 * progresses upload
	 *
	 * @param	integer		$eventId: UID of event
	 * @return	string		error messages or detailView
	 */
	function doUpload($eventId) {
		$uploadData['file'] = $_FILES[$this->prefixId];
		$uploadData['data'] = $this->piVars['upload'];
		
		$uploadPath = $this->extConf['audioFileUploadPath'];
		
		// get data out of database
		$allPreachers = $this->getTeamMembers($this->generalConf['PreacherTeamUID']);
		$allSeries = $this->getSeries();

		if ($uploadData['file']['error'] == 0) {
			$uploadData['file']['extension'] = $this->fileExtension($uploadData['file']['name']);

			$allowedAudioType = str_replace(' ','',$this->extConf['allowedAudioType']);
			if (!in_array($uploadData['file']['extension'], split(',',$allowedAudioType))) {
				return $this->throwErrorMsg($this->pi_getLL('wrongFileExtension')).
						$this->showUpload($eventId, $uploadData);
			}

			//get single event from database
			$singleEvent=$this->getSingleEvent($eventId);
			if ($singleEvent == false) {
				return $this->throwErrorMsg($this->pi_getLL('ERROR.wrongEventKey')).
						$this->listView();
			}

			$countAudioPerEvent = $this->getAudiosPerEvent($eventId);
			$newFileName = $singleEvent['uid'].'-'.date('Ymd',$singleEvent['datetime']).'-'.$countAudioPerEvent.'.'.$uploadData['file']['extension'];

			if (!is_dir($uploadPath)) {
				mkdir($uploadPath, null, true);
			}

			if (!move_uploaded_file($uploadData['file']['tmp_name'], PATH_site.$uploadPath.'/'.$newFileName)) {
				return $this->throwErrorMsg($this->pi_getLL('error')).
						$this->listView();
			}

			$tx_mp3class = t3lib_div::makeInstance('tx_servicemgr_mp3'); //initiate mp3-/id3-functions

			foreach ($uploadData['data']['preachers'] as $singlePreacher) {
				$fileInformation['artist'][] = $allPreachers[$singlePreacher]['name'];
			}
			$fileInformation['album'] = $allSeries[$singleEvent['series']]['name'];
		    $fileInformation['title'] = $singleEvent['subject'];
			$fileInformation['year'] = date('Y', $singleEvent['datetime']);
		    $fileInformation['genre'] = $this->extConf['sermonGenre'];

		    $tx_mp3class->setAudioInformation($uploadPath.'/'.$newFileName, $fileInformation); //write id3-tags to file

		    $fileInformation = $tx_mp3class->getAudioInformation(PATH_site.$uploadPath.'/'.$newFileName); //get playtime, bitrate, etc.
		    $title = $singleEvent['subject'];
		    if ($countAudioPerEvent > 0) {
		    	$title .= ($countAudioPerEvent + 1);
		    }
		    $actTime = mktime();

		    $insertData = array(
		    	'pid' => '2',
		    	'tstamp' => $actTime,
		    	'crdate' => $actTime,
		    	'event' => $eventId,
			    'title' => $title,
		    	'file' => $uploadPath.'/'.$newFileName,
		    	'filedate' => filemtime(PATH_site.$uploadPath.'/'.$newFileName),
		    	'playtime' => $fileInformation['playtime'],
		    	'filesize' => $uploadData['file']['size'],
		    	'mimetype' => $uploadData['file']['type'],
		    	'bitrate' => $fileInformation['bitrate'],
		    	'album' => $singleEvent['series']
			);
			$insertData['l18n_diffsource'] = serialize(array('title'=>'','event'=>'','file'=>''));
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_servicemgr_sermons', $insertData);
			$sermonUID = $GLOBALS['TYPO3_DB']->sql_insert_id();

			// get schedule data for event
			$duty = $this->getSingleSchedule($eventId);
			if ($duty === false) {
				return $this->throwErrorMsg($this->pi_getLL('ERROR.wrongEventKey')).
						$this->listView();
			}
			$duty[$this->generalConf['PreacherTeamUID']] = $uploadData['data']['preachers'];
			
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_servicemgr_dutyschedule', #table
				'event='.$eventId, #WHERE
				array('duty' => serialize($duty)) #data
			);
			
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_servicemgr_events', #table
				'uid='.$eventId, #WHERE
				array('tags' => implode(',', $uploadData['data']['tags'])) #data
			);
			return $this->listView();

		} else {
			switch ($uploadData['file']['error']) {
				CASE 1:
				CASE 2:
					return $this->throwErrorMsg($this->pi_getLL('ERROR.largeFile')).
							$this->showUpload($eventId, $uploadData);
					break;
				CASE 3:
					return $this->throwErrorMsg($this->pi_getLL('ERROR.brokenFile')).
							$this->showUpload($eventId, $uploadData);
					break;
				CASE 4:
					return $this->throwErrorMsg($this->pi_getLL('ERROR.noFile')).
							$this->showUpload($eventId, $uploadData);
					break;

				DEFAULT:
					return $this->throwErrorMsg($this->pi_getLL('error')).
							$this->listView();
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi3/class.tx_servicemgr_pi3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi3/class.tx_servicemgr_pi3.php']);
}

?>
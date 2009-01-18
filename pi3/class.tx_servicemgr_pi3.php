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
 * class.tx_servicemgr_pi3.php
 *
 * includes FrontEnd-Plugin 3 ('Sermon administration') class for servicemgr extension
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');
require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr_mp3.php');

/**
 * Plugin 'Sermon administration' for the 'servicemgr' extension.
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage tx_servicemgr
 */
class tx_servicemgr_pi3 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi3';		// Same as class name
	var $scriptRelPath = 'pi3/class.tx_servicemgr_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	var $pi_checkCHash = true;
	var $template;
	var $continue = true;

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

		$this->piVars['eventId'] = intVal($this->piVars['eventId']);
		$GLOBALS['TSFE']->additionalHeaderData['tx_servicemgr_pi3-js'] = '	<script type="text/javascript" src="' . t3lib_extMgm::siteRelPath($this->extKey) . 'res/pi3.js"></script>';

		if ($this->piVars['submit']) {
			$this->event_upload();
		}

		if ($this->piVars['action'] == 'detail' && $this->piVars['sermonAction']) {
			$content = $this->sermon_process();
		}

		if ($this->continue) {
			switch ($this->piVars['action']) {
				CASE 'detail':
					unset($this->piVars['subject']);
					unset($this->piVars['preacher']);
					unset($this->piVars['tags']);
					unset($this->piVars['series']);
					$content = $this->event_detail();
					break;

				CASE 'list':
				default:
					$content = $this->event_list();
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}


	/**
	 * detail view of event with information about sermon-audio-file(s)
	 *
	 * @param	integer		$eventId: uid of event
	 * @return	string		detail view
	 */
	function detailView($eventId, $initialData = array()) {
		$template = $this->cObj->getSubpart($this->template, '###SERMONADMIN###');
		$event = array_merge($this->getSingleEvent($eventId), $initialData);

		$marker = array(
			'###L_DATE###' => $this->pi_getLL('L_DATE'),
			'###L_TIME###' => $this->pi_getLL('L_TIME'),
			'###L_DATETIME###' => $this->pi_getLL('L_DATETIME'),
			'###L_SUBJECT###' => $this->pi_getLL('L_SUBJECT'),
			'###L_SERIES###' => $this->pi_getLL('L_SERIES'),
			'###L_TAGS###' => $this->pi_getLL('L_TAGS'),
			'###L_SUBMIT###' => $defaultValues['submit'] ? $defaultValues['submit'] : $this->pi_getLL('L_SUBMIT'),
			'###L_SERMONFILES###' => $this->pi_getLL('L_SERMONFILES'),
			'###L_PREACHER###' => $this->pi_getLL('preacher'),
		);

		$marker = array_merge(
			$marker, array(
			'###V_DATE###' => date('d.m.Y', $event['datetime']),
			'###V_TIME###' => date('H:i', $event['datetime']),
			'###V_DATETIME###' => date('d.m.Y H:i', $event['datetime']),
			'###V_SUBJECT###' => $event['subject'],
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


		//SERIES
		$series = $this->getSeries();
		if (!is_array($series)) {
			$seriesContent = $this->pi_getLL('error_noseries');
		} else {
			$seriesContent = '<select name="tx_servicemgr_pi3[series]" id="frm-series">'."\n";
			foreach ($series as $serie) {
				$seriesContent .= '	<option value="'.$serie['uid'].'"';
				if ($serie['uid'] == $event['series'])
					$seriesContent .= ' selected="selected"';

				$seriesContent .= '>'.$serie['name'].'</option>'."\n";
			}
			$seriesContent .= '</select>'."\n";
			$seriesAddLink = '<img title="'.$this->pi_getLL('title_addlink_series').'" alt="'.$this->pi_getLL('alt_addlink_series').'" src="'.$this->conf['addlink_img'].'" class="addIcon" />';
		}
		$marker['###SERIES_SELECTOR###'] = $seriesContent;
		$marker['###SERIES_ADDLINK###'] = $seriesAddLink;

		//PREACHER
		$duty = $this->getSingleSchedule($eventId);
		if ($duty === false) {
			return $this->pi_getLL('ERROR.wrongEventKey');
		}

		$allPreachers = $this->getTeamMembers($this->generalConf['PreacherTeamUID']); // get all teammembers of preacher-team
		$actPreachers = $duty[$this->generalConf['PreacherTeamUID']]; // get preacher(s) for this event

		// if atleast one person is in preacher-team
		if(count($allPreachers) !== 0) {

			if (!is_array($actPreachers) && empty($actPreachers)) {
				$actPreachers = array();
			}

			// wrap preachers in select element
			$outputPreachers = '';
			if (count($actPreachers) > 1) {
				$outputPreachers = '<select size="3" id="'.$this->prefixId.'-preachers" name="'.$this->prefixId.'[preachers][]" multiple="multiple">
				';
			} else {
				$outputPreachers = '<select size="1" id="'.$this->prefixId.'-preachers" name="'.$this->prefixId.'[preachers][]">
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
		$marker['###PREACHER_SELECTOR###'] = $outputPreachers;




		//TAGS
		$tags = $this->getTags();
		if (!is_array($series)) {
			$tagsContent = $this->pi_getLL('error_notags');
		} else {
			$event['tags'] = split(',',$event['tags']);
			foreach ($tags as $tag) {
				$tagsContent .= '<input type="checkbox" name="tx_servicemgr_pi3[tags][]" value="'.$tag['uid'].'" id="frm-tags-'.$tag['uid'].'"';
				if (in_array($tag['uid'], $event['tags'])) {
					$tagsContent .= ' checked="checked"';
				}
				$tagsContent .= ' /> <label for="frm-tags-'.$tag['uid'].'">'.$tag['name'].'</label><br />';
			}
		}
		$tagsAddLink = '<img title="'.$this->pi_getLL('title_addlink_tags').'" alt="'.$this->pi_getLL('alt_addlink_tags').'" src="'.$this->conf['addlink_img'].'" />';
		$marker['###TAGS_CBS###'] = $tagsContent;
		$marker['###TAGS_ADDLINK###'] = $tagsAddLink;

		$sermons = $this->getAudioFiles($eventId);
		if (is_array($sermons) && !empty($sermons)) {
			$sermonTemplate = $this->cObj->getSubpart($template, '###SERMONFILE###');
			$sermonRows = array();
			foreach ($sermons as $sermon) {
				$sermonMarker = array(
					'###TITLE###' => $sermon['title'],
					'###PLAYTIME###' => $this->formatTime($sermon['playtime']),
					'###BITRATE###' => $this->formatBits($sermon['bitrate']),
					'###FILESIZE###' => $this->formatBytes($sermon['filesize']),
					'###MIMETYPE###' => $sermon['mimetype'],
				);
				$sermonRow[] = $this->cObj->substituteMarkerArray($sermonTemplate, $sermonMarker);
			}
		}



		$actionLink = $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'addQueryString' => 1,
			'addQueryString.' => array(
				'exclude' => 'cHash,no_cache,tx_servicemgr_pi3[action]',
			),
			'additionalParams' => '&no_cache=1&tx_servicemgr_pi3[action]=doupload',
			'useCacheHash' => false,
		));
		$marker['###ACTION_LINK###'] = $actionLink;

		$hiddenFieldTemplate = $this->cObj->getSubpart($template, '###HIDDENFIELD###');
		$hiddenFields = array();
		$hiddenFields[] = $this->cObj->substituteMarkerArray(
				$hiddenFieldTemplate,
				array(
					'###HF_NAME###' => $this->prefixId.'[uid]',
					'###HF_VALUE###' => $eventId,
				)
		);
		$hiddenFields = implode('', $hiddenFields);
		$content = $this->cObj->substituteSubpart($template, '###HIDDENFIELD###', $hiddenFields);
		$content = $this->cObj->substituteSubpart($content, '###SERMONFILE###', implode("\n", (array)$sermonRow));
		$content = $this->cObj->substituteMarkerArray($content, $marker);

		return $content;
	}

	function event_list() {
		$where = 'hidden=0 and deleted=0 and public=1';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid)',
					'tx_servicemgr_events', $where);
		if ($res) {
			$temp = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$this->numberOfPages = $temp[0];
		}

		$page = intVal($this->piVars['page']);
		$rpp = $this->conf['pageSize'];
		$start = $rpp*$page;

		// Get records
		$sorting = 'datetime DESC';


		$events = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid, datetime, subject', 'tx_servicemgr_events',
					$where, '', $sorting, $start . ',' . $rpp
		);
		foreach ($events as &$event)
			$event['sermons'] = $this->getAudioFiles($event['uid']);

		return $this->view_list($events);
	}

	function event_detail() {
		$eventId = intVal($this->piVars['eventId']);
		if ($eventId > 0) {
			$event = array_merge($this->getSingleEvent($eventId), $this->piVars);

			if (is_array($event['tags'])) $event['tags'] = implode(',', $event['tags']);
			if (!is_array($event['preacher']) && !empty($event['preacher'])) $event['preacher'] = t3lib_div::trimExplode(',', $event['preacher']);

			$selected = array(
				'tags' => t3lib_div::trimExplode(',', $event['tags']),
				'series' => intVal($event['series']),
				'preacher' => !empty($event['preacher']) ? $event['preacher'] : $this->getUserInCharge($event['uid'], $this->generalConf['PreacherTeamUID']),
			);

			if (!$event['date']) $event['date'] = date('d.m.Y', $event['datetime']);
			if (!$event['time']) $event['time'] = date('H:i', $event['datetime']);

			$event['tags'] = $this->getTags();
			foreach ($event['tags'] as &$tag)
				$tag['selected'] = in_array($tag['uid'], $selected['tags']) ? 1 : 0;

			$event['series'] = $this->getSeries();
			foreach ($event['series'] as &$series)
				$series['selected'] = ($series['uid'] == $selected['series'] ? 1 : 0);

			$event['preacher'] = $this->getTeamMembers($this->generalConf['PreacherTeamUID']);
			foreach($event['preacher'] as &$preacher)
				$preacher['selected'] = in_array($preacher['uid'], $selected['preacher']) ? 1 : 0;

			$event['sermons'] = $this->getAudioFiles($event['uid']);

			return $this->view_detail($event);
		}
	}

	function event_upload() {
		$file = $_FILES[$this->prefixId];
		$data = $this->piVars;

		if (($event = $this->getSingleEvent($data['eventId'])) == false)
				return $this->throwErrorMsg($this->pi_getLL('ERROR.wrongEventKey')) . $this->event_list();

		$preacher = $this->getTeamMembers($this->generalConf['PreacherTeamUID']);
		$series = $this->getSeries();

		if ($file['error']['sermonfile'] != 4) {
			if ($file['error']['sermonfile'] != 0) {
				switch ($file['error']['sermonfile']) {
					CASE 1:
					CASE 2:
						return $this->throwErrorMsg($this->pi_getLL('ERROR.largeFile')).
								$this->event_detail();
						break;

					CASE 3:
						return $this->throwErrorMsg($this->pi_getLL('ERROR.brokenFile')).
								$this->event_detail();
						break;

					default:
						return $this->throwErrorMsg($this->pi_getLL('error')).
								$this->event_list();
				}
			} else {

				$uploadPath = $this->extConf['audioFileUploadPath'];
				$file['extension']['sermonfile'] = $this->fileExtension($file['name']['sermonfile']);
				$allowedAudioTypes = t3lib_div::trimExplode(',',$this->extConf['allowedAudioType']);

				if (!in_array($file['extension']['sermonfile'], $allowedAudioTypes))
					return $this->throwErrorMsg($this->pi_getLL('wrongFileExtension')) . $this->event_detail();

				$audioIndex = $this->getAudiosPerEvent($eventId);
				$audioName = $event['uid'] . '-' . date('Ymd',$event['datetime']) . '-' . $audioIndex . '.' . $file['extension']['sermonfile'];
				$audioName_rel = $uploadPath . '/' . $audioName;
				$audioName_abs = PATH_site . $audioName_rel;

				if (!is_dir($uploadPath)) mkdir($uploadPath, null, true);
				if (!move_uploaded_file($file['tmp_name']['sermonfile'], $audioName_abs))
					return $this->throwErrorMsg($this->pi_getLL('error')) . $this->event_list();

				$fileInformation = array();
				if (!is_array($data['preacher'])) $data['preacher'] = t3lib_div::trimExplode(',', $data['preacher']);
				foreach ($data['preacher'] as $singlePreacher)
					$fileInformation['artist'][] = $preacher[$singlePreacher]['name'];

				$fileInformation['album'] = $series[$data['series']]['name'];
				$fileInformation['title'] = $data['subject'];
				$fileInformation['year'] = date('Y', $event['datetime']);
				$fileInformation['genre'] = $this->extConf['sermonGenre'];

				$tx_mp3class = t3lib_div::makeInstance('tx_servicemgr_mp3'); 										//initiate mp3-/id3-functions
				$tx_mp3class->setAudioInformation($audioName_rel, $fileInformation);				//write id3-tags to file
				$fileInformation = $tx_mp3class->getAudioInformation($audioName_abs);	//get playtime, bitrate, etc.

				if ($audioIndex > 0) $data['subject'] .= ($audioIndex + 1);

				$record = array(
					'pid' => $this->generalConf['storagePID'],
					'tstamp' => mktime(),
					'crdate' => mktime(),
					'event' => $event['uid'],
					'title' => $data['subject'],
					'file' => $audioName_rel,
					'filedate' => filemtime($audioName_abs),
					'playtime' => $fileInformation['playtime'],
					'filesize' => $file['size']['sermonfile'],
					'mimetype' => $file['type']['sermonfile'],
					'bitrate' => $fileInformation['bitrate'],
					'album' => $data['series']
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_servicemgr_sermons', $record);
				$sermonId = $GLOBALS['TYPO3_DB']->sql_insert_id();
			}
		}

		$dutySchedule = $this->getSingleSchedule($event['uid'], false);
		if (!is_array($data['preacher'])) $data['preacher'] = t3lib_div::trimExplode(',', $data['preacher']);
		if ($dutySchedule['event']) {
			$duty = $dutySchedule['duty'];
			$duty[$this->generalConf['PreacherTeamUID']] = $data['preacher'];
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_servicemgr_dutyschedule', #table
				'event=' . $event['uid'], #WHERE
				array('tstamp' => mktime(), 'duty' => serialize($duty)) #data
			);
		} else {
			$duty = array($this->generalConf['PreacherTeamUID'] => $data['preacher']);
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_servicemgr_dutyschedule', #table
				array(
					'pid' => $this->generalConf['storagePID'],
					'tstamp' => mktime(),
					'crdate' => mktime(),
					'event' => $event['uid'],
					'duty' => serialize($duty))
			);
		}

		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_servicemgr_events', #table
			'uid=' . $event['uid'], #WHERE
			array(
				'tags' => implode(',', (array)$data['tags']),
				'subject' => $data['subject'],
				'series' => $data['series'],
			)
		);

		return true;
	}

	function sermon_process() {
		switch ($this->piVars['sermonAction']) {
			CASE 'refresh':
				$content = $this->sermon_process_refresh();
				break;
			CASE 'delete':
				$content = $this->view_delete();
				break;
			CASE 'delete_do':
				$content = $this->sermon_process_delete();
				break;
			CASE 'edit':
				break;
		}
		return $content;
	}

	function sermon_process_refresh() {
		$tx_mp3class = t3lib_div::makeInstance('tx_servicemgr_mp3');
		$tx_mp3class = new tx_servicemgr_mp3;
		$tx_mp3class->updateFile($this->piVars['sermonId']);
	}

	function sermon_process_delete() {
		$sermon = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid, event, title, file, filedate, playtime, filesize, bitrate, album, mimetype',   #select
					'tx_servicemgr_sermons', #from
					'uid=' . $this->piVars['sermonId']  #where
		);
		$sermon = $sermon[0];
		$enc = md5($sermon['uid'] . $sermon['filedate'] . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
		if ($enc == $this->piVars['dh']) {
			$paths = explode('/', $sermon['file']);
			$filename = array_reverse($paths);
			$filename = $filename[0];
			$dirs = array_pop($paths);
			$new_path = implode('/', $paths) . '/ZZZdeleted_' . $filename;
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_servicemgr_sermons', 'uid=' . intVal($this->piVars['sermonId']), array('deleted' => 1, 'file' => $new_path));
			rename($sermon['file'], $new_path);
		}
	}

	function view_list($events) {
		$template = $this->cObj->getSubpart($this->template,'###SERMONLISTADMIN###');
		$templateHeader = $this->cObj->getSubpart($template,'###HEADERROW###');
		$templateDataRow = $this->cObj->getSubpart($template,'###ROW###');

		$marker = array(
			'###HDATE###' => $this->pi_getLL('date'),
			'###HSUBJECT###' => $this->pi_getLL('subject'),
			'###HAUDIOFILES###' => $this->pi_getLL('audiofiles'),
		);
		$tempData['header'] = $this->cObj->substituteMarkerArray($templateHeader,$marker);

		$tempData['data'] = array();
		foreach($events as $event) {
			$audioFiles = array();
			foreach ($event['sermons'] as $sermon)
				$audioFiles[] = $sermon['title'];

			$subjectTitle = substr($event['subject'], 0, 30);
			$subjectTitle .= strlen($event['subject']) != strlen($subjectTitle) ? '...' : '';
			$subject = $this->tx_linkToPage($subjectTitle, $GLOBALS['TSFE']->id,
					array($this->prefixId.'[eventId]'=>$event['uid'],$this->prefixId.'[action]'=>'detail')
			);

			$marker = array(
				'###DATE###' => date('d.m.Y', $event['datetime']),
				'###SUBJECT###' => $subject,
				'###AUDIOFILES###' => implode('<br />' , (array)$audioFiles),
			);
			$tempData['data'][] = $this->cObj->substituteMarkerArray($templateDataRow, $marker);
		}

		$content = $this->cObj->substituteSubpart($template, '###HEADERROW###', $tempData['header']);
		$content = $this->cObj->substituteSubpart($content, '###ROW###', implode(Chr(10), (array)$tempData['data']));
		$content = $this->cObj->substituteMarker($content, '###PAGEBROWSER###', $this->getListGetPageBrowser($this->numberOfPages));
		return $content;
	}

	function view_delete() {
		$template = $this->cObj->getSubpart($this->template, '###DELETEFORM###');

		$yes = array(
			$this->prefixId . '[action]' => 'detail',
			$this->prefixId . '[sermonAction]' => 'delete_do',
			$this->prefixId . '[sermonId]' => $this->piVars['sermonId'],
			$this->prefixId . '[eventId]' => $this->piVars['eventId'],
			$this->prefixId . '[dh]' => $this->piVars['dh'],
		);
		$no = array();

		$links['yes'] = $this->pi_linkToPage($this->pi_getLL('yes'),$GLOBALS['TSFE']->id, '',$yes);
		$links['no'] = $this->pi_linkToPage($this->pi_getLL('no'),$GLOBALS['TSFE']->id, '',$no);

		$marker = array(
			'###MESSAGE###' => $this->pi_getLL('sermon_delete'),
			'###YES###' => $links['yes'],
			'###NO###' => $links['no'],
		);
		$this->continue = false;

		$content = $this->cObj->substituteMarkerArray($template, $marker);
		return $content;
	}

	function view_detail($event) {
		$template = $this->cObj->getSubpart($this->template, '###SERMONADMIN###');

		$marker = array(
			'###L_DATE###' => $this->pi_getLL('L_DATE'),
			'###L_TIME###' => $this->pi_getLL('L_TIME'),
			'###L_SUBJECT###' => $this->pi_getLL('L_SUBJECT'),
			'###L_PREACHER###' => $this->pi_getLL('preacher'),
			'###L_SERIES###' => $this->pi_getLL('L_SERIES'),
			'###L_TAGS###' => $this->pi_getLL('L_TAGS'),
			'###L_SERMONFILES###' => $this->pi_getLL('L_SERMONFILES'),
			'###L_SUBMIT###' => $defaultValues['submit'] ? $defaultValues['submit'] : $this->pi_getLL('L_SUBMIT'),

			'###V_DATE###' => $event['date'],
			'###V_TIME###' => $event['time'],
			'###V_SUBJECT###' => $event['subject'],
		);


		if (is_array($this->formValidationErrors)) {
			$formError = $this->pi_getLL('error_missingfields').' ';
			foreach ($this->formValidationErrors as $k => $v) {
				$formErrors[] = $this->pi_getLL('L_'.strtoupper($k));
			}
			$formError .= implode(', ',$formErrors);
			$formError = $this->throwErrorMsg($formError);
		}
		$marker['###FORM_ERRORS###'] = $formError;

		if (!is_array($event['series'])) {
			$marker['###SERIES_SELECTOR###'] = $this->pi_getLL('error_noseries');
		} else {
			foreach ($event['series'] as $serie) {
				$series[] = '	<option value="' . $serie['uid'] . '"' . ($serie['selected'] ? ' selected="selected"' : '') . '>' . $serie['name'] . '</option>' . Chr(10);
			}
			$marker['###SERIES_SELECTOR###'] = '<select name="tx_servicemgr_pi3[series]" id="frm-series">' . Chr(10);
			$marker['###SERIES_SELECTOR###'] .= implode(Chr(10), $series);
			$marker['###SERIES_SELECTOR###'] .= Chr(10) . '</select>' . Chr(10);
		}
		$marker['###SERIES_ADDLINK###'] = '<img title="'.$this->pi_getLL('title_addlink_series').'" alt="'.$this->pi_getLL('alt_addlink_series').'" src="'.$this->conf['addlink_img'].'" class="addIcon" onclick="addSeries(' . $this->generalConf['storagePID'] . ');" />';

		if (!is_array($event['preacher'])) {
			$marker['###PREACHER_SELECTOR###'] = $this->pi_getLL('error_nopreachers');
		} else {
			foreach ($event['preacher'] as $preacher) {
				$preachers[] = '	<option value="' . $preacher['uid'] . '"' . ($preacher['selected'] ? ' selected="selected"' : '') . '>' . $preacher['name'] . '</option>';
			}
			$marker['###PREACHER_SELECTOR###'] = '<select name="tx_servicemgr_pi3[preacher]" id="frm-preacher">' . Chr(10);
			$marker['###PREACHER_SELECTOR###'] .= implode(Chr(10), $preachers);
			$marker['###PREACHER_SELECTOR###'] .= Chr(10) . '</select>' . Chr(10);
		}

		if (!is_array($event['tags'])) {
			$marker['###TAGS_CBS###'] = $this->pi_getLL('error_notags');
		} else {
			foreach ($event['tags'] as $tag) {
				$tags[] = '	<input type="checkbox" name="tx_servicemgr_pi3[tags][]" value="' . $tag['uid'] . '" id="frm-tags-' . $tag['uid'] . '"'
							. ($tag['selected'] ? ' checked="checked"' : '') . '/>'
							. '<label for="frm-tags-' . $tag['uid'] . '">' . $tag['name'] . '</label><br />';
			}
			$marker['###TAGS_CBS###'] .= implode(Chr(10), $tags);
		}
		$marker['###TAGS_ADDLINK###'] = '<img class="addIcon" title="'.$this->pi_getLL('title_addlink_tags').'" alt="'.$this->pi_getLL('alt_addlink_tags').'" src="'.$this->conf['addlink_img'].'" onclick="addTag(this.previousSibling, \'tx_servicemgr_pi3[tags]\', ' . $this->generalConf['storagePID'] . ');" />';


		if (is_array($event['sermons'])) {
			$sermonTemplate = $this->cObj->getSubpart($template, '###SERMONFILE###');
			$sermonRows = array();
			foreach ($event['sermons'] as $sermon) {
				$icons = array(
					'refresh' => $this->pi_linkToPage(
											'<img src="' . t3lib_extMgm::siteRelPath($this->extKey) . 'res/arrow_refresh_small.png" />',
											$GLOBALS['TSFE']->id, '',
											array(
												$this->prefixId . '[action]' => 'detail',
												$this->prefixId . '[sermonAction]' => 'refresh',
												$this->prefixId . '[sermonId]' => $sermon['uid'],
												$this->prefixId . '[eventId]' => $event['uid'],
											)
									),
					'delete' => $this->pi_linkToPage(
											'<img src="' . t3lib_extMgm::siteRelPath($this->extKey) . 'res/delete.png" />',
											$GLOBALS['TSFE']->id, '',
											array(
												$this->prefixId . '[action]' => 'detail',
												$this->prefixId . '[sermonAction]' => 'delete',
												$this->prefixId . '[sermonId]' => $sermon['uid'],
												$this->prefixId . '[eventId]' => $event['uid'],
												$this->prefixId . '[dh]' => md5($sermon['uid'] . $sermon['filedate'] . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']),
											)
									),
				);
				$sermonMarker = array(
					'###TITLE###' => '<span style="padding:0 2px 0 0;" id="tx-smgr-pi3-sermon-' . $sermon['uid'] . '" onclick="tx_servicemgr_pi3_editSermonName(this.parentNode);">' . $sermon['title'] . '</span><img alt="' . $this->pi_getLL('edit') . '" title="' . $this->pi_getLL('edit') . '" onclick="tx_servicemgr_pi3_editSermonName(this.parentNode);" src="' . t3lib_extMgm::siteRelPath($this->extKey) . 'res/pencil.png" />',
					'###PLAYTIME###' => $this->formatTime($sermon['playtime']),
					'###BITRATE###' => $this->formatBits($sermon['bitrate']),
					'###FILESIZE###' => $this->formatBytes($sermon['filesize']),
					'###MIMETYPE###' => $sermon['mimetype'],
					'###ICONS###' => implode('', $icons),
				);
				$sermonRow[] = $this->cObj->substituteMarkerArray($sermonTemplate, $sermonMarker);
			}
		}


		$actionLink = $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'addQueryString' => 1,
			'addQueryString.' => array(
				'exclude' => 'cHash,no_cache,tx_servicemgr_pi3[action]',
			),
			'additionalParams' => '&no_cache=1&tx_servicemgr_pi3[action]=detail',
			'useCacheHash' => false,
		));
		$marker['###ACTION_LINK###'] = $actionLink;

		$hiddenFieldTemplate = $this->cObj->getSubpart($template, '###HIDDENFIELD###');
		$hiddenFields = array();
		$hiddenFields[] = $this->cObj->substituteMarkerArray($hiddenFieldTemplate,
				array('###HF_NAME###' => $this->prefixId.'[uid]', '###HF_VALUE###' => $event['uid']));
		$hiddenFields = implode('', $hiddenFields);

		$content = $this->cObj->substituteSubpart($template, '###HIDDENFIELD###', $hiddenFields);
		$content = $this->cObj->substituteSubpart($content, '###SERMONFILE###', implode(Chr(10), (array)$sermonRow));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		$content .= '<div style="display:none;" id="tx-servicemgr-pi3-saveicon"><img src="' . t3lib_extMgm::siteRelPath($this->extKey) . 'res/disk.png" title="' . $this->pi_getLL('save') . '" alt="' . $this->pi_getLL('save') . '" onclick="tx_servicemgr_pi3_saveSermonName(this.parentNode);" /></div>';

		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi3/class.tx_servicemgr_pi3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi3/class.tx_servicemgr_pi3.php']);
}

?>
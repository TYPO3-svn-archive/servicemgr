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
 * class.tx_servicemgr_pi2.php
 *
 * includes FrontEnd-Plugin 2 ('Sermon archive') class for servicemgr extension
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');
require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr_mp3.php');

/**
 * Plugin 'Sermon archive' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi2 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_servicemgr_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	var $pi_checkCHash = true;

	var $template;

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
		$this->mp3Class = t3lib_div::makeInstance('tx_servicemgr_mp3');

		$this->fetchConfigValue('viewmode');
		$this->fetchConfigValue('detailviewPID');
		$this->fetchConfigValue('categorizebyseries');

		//DEBUG-CONFIG
		$GLOBALS['TYPO3_DB']->debugOutput = true;


		$this->piVars['eventId'] = intVal($this->piVars['eventId']);


		if (!$this->piVars['eventId']) {
			switch ($this->conf['viewmode']) {
				CASE 'podcast':
					$this->podcast();
					break;
				CASE 'latest':
					$content = $this->latest();
					break;
				CASE 'archive':
				default:
					$content = $this->listView();
			}

		} else {
			$content=$this->detailViewEvent(
			$this->piVars['eventId'],
			array(
					'subparts' => array('subject','datetime','series','notes','sermon','backlink'),
					'backlink' => array('str' => $this->pi_getLL('back'), 'id' => $GLOBALS['TSFE']->id),
			),
			$this->cObj->getSubpart($this->cObj->fileResource('EXT:servicemgr/res/esv.html'), '###SINGLEEVENTEL###')
			);
		}

		return $this->pi_wrapInBaseClass($content);
	}


	function sermons($number=0, $start=0, $end=0, $pagebrowser=false) {
		$where = 'hidden=0 AND deleted=0';
		if ($start > 0)
			$where .= ' AND (datetime>' . $start . ' OR datetime=0 )';
		if ($end > 0)
			$where .= ' AND (datetime<' . $end . ' OR datetime=0 )';
		$where .= ' AND uid IN (SELECT event FROM tx_servicemgr_sermons WHERE hidden=0 AND deleted=0)';

		if ($pagebrowser !== false && $number > 0) {
			$limit = $pagebrowser.','.$number;
		} elseif ($number > 0) {
			$limit = '0,'.$number;
		}

		$events = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid, datetime, subject, series',   #select
					'tx_servicemgr_events', #from
					$where,  #where
					'','datetime DESC',$limit
		);

		if (is_array($events)) {

			foreach ($events as &$event) {
				$event['sermons'] = $this->getAudioFiles($event['uid']);
				foreach ($event['sermons'] as &$sermon)
					$sermon = $this->mp3Class->checkFile($sermon['uid']);
			}
		}
		return $events;
	}


	function podcast() {
		$data = array(
			'language' => 'de-DE',
			'title' => 'D16 - Kirche für Jugendliche: Predigten',
			'copyright' => 'D16, Freie evangelische Gemeinde Gießen',
			'author' => 'D16 - Kirche für Jugendliche',
			'summary' => 'D16 ist die Jugendarbeit der Freien evangelischen Gemeinde Gießen.',
			'subtitle' => '',
			'email' => 'website@d16.de',
			'categorys' => array('Religion & Spirituality' => 'Christianity'),
		);
		$events = $this->sermons();
		foreach ($events as $event) {
			$data['items'][] = array(
				'title' => $event['sermons'][0]['title'],
				'subtitle' => $event['notes'],
				'summary' => $event['sermons'][0]['summary'],
				'author' => 'Prediger',
				'pubDate' => $event['datetime'],
				'keywords' => $this->getTags($event['uid']),
				'duration' => $event['sermons'][0]['playtime'],
				'enclosure' => array(
					'url' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$event['sermons'][0]['file'],
					'length' => $event['sermons'][0]['filesize'],
					'type' => $event['sermons'][0]['mimetype'],
				),
			);
		}
		if (t3lib_extMgm::isLoaded('podcastfeed')) {
			require_once(t3lib_extMgm::extPath('podcastfeed') . 'class.tx_podcastfeed_api.php');
			$podcastfeed = t3lib_div::makeInstance('tx_podcastfeed_api');
			$podcastfeed->main($data);
		}
	}

	/**
	 * Returns latest sermon
	 *
	 * @return	string		HTML
	 */
	function latest() {
		$content = '';
		$subpart = $this->cObj->getSubpart($this->template,'###SERMONLIST_LATEST###');

//		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
//					'tx_servicemgr_sermons.uid, tx_servicemgr_sermons.event, tx_servicemgr_sermons.title, tx_servicemgr_sermons.file,
//					tx_servicemgr_sermons.filedate, tx_servicemgr_sermons.playtime, tx_servicemgr_sermons.filesize,
//					tx_servicemgr_sermons.bitrate, tx_servicemgr_sermons.album, tx_servicemgr_events.datetime',   #select
//					'tx_servicemgr_sermons, tx_servicemgr_events', #from
//					'tx_servicemgr_events.hidden=0 AND tx_servicemgr_events.deleted=0 AND tx_servicemgr_sermons.hidden=0 AND tx_servicemgr_sermons.deleted=0
//					AND tx_servicemgr_events.uid=tx_servicemgr_sermons.event',  #where
//			'','datetime DESC','0,1'
//		);
//		if ($res) {
//			$sermon = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
//			$event = $this->getSingleEvent($sermon['event']);

		$events = $this->sermons(1);

		if (is_array($events[0])) {

			$event = $events[0];
			$sermon = $event['sermons'][0];
			$duty = $this->getSingleSchedule($event['uid']);

			$sermon = $this->mp3Class->checkFile($sermon['uid']);

			$allPreachers = $this->getTeamMembers($this->generalConf['PreacherTeamUID']);
			$preacher = $duty[$this->generalConf['PreacherTeamUID']];
			if (is_array($preacher)) {
				$outPreacher = '';
				foreach($allPreachers as $singlePreacher) {
					if (in_array($singlePreacher['uid'], $preacher)) {
						$outPreacher .= $this->cObj->typoLink(
							$singlePreacher['name'],
							array(
								'parameter' => $this->generalConf['preacherdetailPID'],
								'useCacheHash' => true,
								'additionalParams' => '&tx_feuser_pi1[showUid]='.$singlePreacher['uid']
							)
						);
					}
				}
			}

			if (t3lib_extMgm::isLoaded('audioplayer')) {
				require_once(t3lib_extMgm::extPath('audioplayer').'class.tx_audioplayer.php');
				$audioplayer = t3lib_div::makeInstance('tx_audioplayer');
				$audioplayer->init();
				$audioplayer->setOptions(array('initialvolume'=>80,'animation'=>'no', 'width'=>250));
			}

			$downloadLink = $this->pi_linkToPage(
						'<img src="'.t3lib_extMgm::extRelPath('servicemgr').'res/disk.png" alt="download" title="download" />',
				$GLOBALS['TSFE']->id, '',
				array(
							'eID' => 'tx_servicemgr_download',
							'sermonid'=>$sermon['uid']
				)
			);

			$markers = array(
				'###PREACHER###' => $outPreacher,
				'###SUBJECT###' => $sermon['title'],
				'###DATE###' => date('d.m.Y', $event['datetime']),
				'###TIME###' => date('H:i', $event['datetime']),
				'###DOWNLOAD###' => $downloadLink,
				'###PLAYER###' => $audioplayer ? $audioplayer->getFlashPlayer($sermon['file'], $sermon['uid']) : '',
			);
			$content = $this->cObj->substituteMarkerArray($subpart, $markers);
		}
		return $content;
	}

	/**
	 * Returns list of sermons
	 *
	 * @return	string		HTML
	 */
	function listView() {


		// Find starting record
		$where .= 'deleted=0 AND hidden=0 AND uid IN (SELECT event FROM tx_servicemgr_sermons WHERE hidden=0 AND deleted=0)';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid)',
					'tx_servicemgr_events', $where);
		if ($res) {
			$temp = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$numberOfPages = $temp[0];
		}

		$page = intval($this->piVars['page']);
		$rpp = $this->conf['pageSize'];
		$start = $rpp*$page;

		$events = $this->sermons($rpp, 0, 0, $start);

		$series = $this->getSeries();

		//substitue table rows in template
		if (is_array($events)) {

			if ($this->conf['categorizebyseries'] == 1) {
				$wiredEventsSeries = $this->wireEventsAndSeries($events);

				foreach ($wiredEventsSeries as $eventGroupAndSeries) {
					$tempEvents = array();
					foreach ($eventGroupAndSeries['events'] as $singleEventOfSeries) {
						$tempEvents[] = $events[$singleEventOfSeries];
					}
					$content .= '<h2>'.$series[$eventGroupAndSeries['series']]['name'].'</h2>';
					$content .= $this->getSermonListTable($tempEvents);
				}
			} else {
				$content = $this->getSermonListTable($events);
			}

			$content .= $this->getListGetPageBrowser($numberOfPages);
		}
		return $content;
	}

	function getSermonListTable($events) {
		//Template preparation
		$subpart = $this->cObj->getSubpart($this->template,'###SERMONLIST###');
		$headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
		$singlerow = $this->cObj->getSubpart($subpart,'###ROW###');
		$filearray = $this->cObj->getSubpart($subpart,'###FILES###');

		//substitue table header in template file
		$markerArray['###HDATE###'] = $this->pi_getLL('date');
		$markerArray['###HSUBJECT###'] = $this->pi_getLL('subject');
		$markerArray['###HFILE###'] = $this->pi_getLL('file');
		$subpartArray['###HEADERROW###'] = $this->cObj->substituteMarkerArray($headerrow,$markerArray);

		if (t3lib_extMgm::isLoaded('audioplayer')) {
			require_once(t3lib_extMgm::extPath('audioplayer').'class.tx_audioplayer.php');
			$audioplayer = t3lib_div::makeInstance('tx_audioplayer');
			$audioplayer->init();
			$audioplayer->setOptions(array('initialvolume'=>'80','animation'=>'no', 'pagebg' => 'E0E9EF'));
			$audioplayer->setHeaders($audioplayer->renderVars());
			$GLOBALS['TSFE']->additionalHeaderData['tx_servicemgr_pi2_sermonjs'] = '	<script type="text/javascript" src="typo3conf/ext/servicemgr/res/sermonplayer.js"></script>';
		}

		$eventRowsOutput = '';
		foreach ($events as $row) {
			$markerArray['###DATE###'] = date('d.m.Y', $row['datetime']);
			$markerArray['###SUBJECT###'] = $this->cObj->typoLink(
				$row['subject'],
				array(
					'parameter' => $GLOBALS['TSFE']->id,
					'useCacheHash' => true,
					'additionalParams' => '&tx_servicemgr_pi2[eventId]='.$row['uid'],
					'ATagParams' => 'onclick="return expandSermonElement(\'tx-servicemgr-sa-sermon-' . $row['uid'] . '\');"',
				)
			);
			$markerArray['###UID###'] = $row['uid'];


			$audioFileOutput = '';
			foreach ($row['sermons'] as $sermon) {

				$sermon = $this->mp3Class->checkFile($sermon['uid']);

				$markerArray['###FILETITLE###'] = (count($sermons)>1) ? $sermon['title'].' &#0150; ' : '';
				$markerArray['###SIZE###'] = $this->formatBytes($sermon['filesize']);
				$markerArray['###LENGTH###'] = $this->formatTime($sermon['playtime']);
				$markerArray['###DOWNLOAD###'] = $this->pi_linkToPage(
					'<img src="'.t3lib_extMgm::extRelPath('servicemgr').'res/disk.png" alt="download" title="'.$this->pi_getLL('download').'" />',
					$GLOBALS['TSFE']->id, '',
					array(
						'eID' => 'tx_servicemgr_download',
						'sermonid'=>$sermon['uid']
					)
				);
				$playLink['href'] = $this->cObj->getTypoLink_URL(
					$GLOBALS['TSFE']->id,
					array(
						'tx_servicemgr_pi2[eventId]' => $row['uid'],
						'tx_servicemgr_pi2[play]' => $sermon['uid'],
					)
				);
				$playLink['onclick'] = $audioplayer ? 'sermonshowplayer('.$sermon['uid'].'); return false;' : '';

				$markerArray['###PLAY###'] =
					'<a href="'.$playLink['href'].'" onclick="'.$playLink['onclick'].'">'
					.'<img src="'.t3lib_extMgm::extRelPath('servicemgr').'res/control_play_blue.png" alt="play" title="'.$this->pi_getLL('play').'" />'
					.'</a>';
				$markerArray['###DETAIL###'] = $this->cObj->typoLink(
					'<img src="' . t3lib_extMgm::siteRelPath($this->extKey) . 'res/table_go.png' .'" alt="Detail" title="Details" />',
					array(
						'parameter' => $GLOBALS['TSFE']->id,
						'useCacheHash' => true,
						'additionalParams' => '&tx_servicemgr_pi2[eventId]='.$row['uid'],
					)
				);
				$markerArray['###PLAYERID###'] = $sermon['uid'];
				$markerArray['###PLAYER###'] = $audioplayer->getFlashPlayer($sermon['file'], $sermon['uid']);
				$audioFileOutput .= $this->cObj->substituteMarkerArray($filearray,$markerArray);
			}
			$subpartArray['###FILES###']=$audioFileOutput;
			$eventRowsOutput .= $this->substituteMarkersAndSubparts($singlerow,$markerArray,$subpartArray);
		}
		$subpartArray['###ROW###']=$eventRowsOutput;
		return $this->substituteMarkersAndSubparts($subpart,$markerArray,$subpartArray);
	}

	/**
	 * Returns formarted filesize
	 *
	 * @param	string		$path: path to file
	 * @return	string		formarted file size
	 */
	function getFileSizeFormarted($path) {
		if (@file_exists($path)) {
			$filesize = formatBytes(filesize($path));
		} else {
			$filesize = "0 MB";
		}
		return $filesize;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi2/class.tx_servicemgr_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi2/class.tx_servicemgr_pi2.php']);
}

?>
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
 * class.tx_servicemgr_pi1.php
 *
 * includes FrontEnd-Plugin 1 ('Event preview') class for servicemgr extension
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');

/**
 * Plugin 'Event preview' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi1 extends tx_servicemgr {
	var $prefixId      = 'tx_servicemgr_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_servicemgr_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	var $pi_checkCHash = true;
	var $template;


	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The	content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();

		$this->tx_init();
		$this->tx_loadLL();

		$this->fetchConfigValue('previewdays');
		$this->fetchConfigValue('shownelements');
		$this->fetchConfigValue('upcoming');
		$this->fetchConfigValue('previewcolumns');
		$this->fetchConfigValue('detailviewPID');
		$this->fetchConfigValue('categorizebyseries');

		//DEBUG-CONFIG
		$GLOBALS['TYPO3_DB']->debugOutput = true;


		$this->piVars['eventId'] = intVal($this->piVars['eventId']);


		if (empty($this->piVars['eventId'])) {
			$content = $this->listView();
		} else {
			$this->piVars['backlink'] = intVal($this->piVars['backlink']);
			if (!t3lib_div::testInt($this->piVars['backlink']) || $this->piVars['backlink'] == 0) {
				$this->piVars['backlink'] = $GLOBALS['TSFE']->id;
			}

			$content= $this->detailViewEvent(
				$this->piVars['eventId'],
				array(
					'subparts' => array('subject','datetime','series','notes','sermon','backlink'),
					'backlink' => array('str' => $this->pi_getLL('back'), 'id' => $this->piVars['backlink']),
				),
				$this->cObj->getSubpart($this->cObj->fileResource('EXT:servicemgr/res/esv.html'), '###SINGLEEVENTEL###')
			);
		}
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * returns event preview table
	 *
	 * @return	string		event preview table
	 */
	function listView() {
		if($this->conf['shownelements'] != 0) {
			$limit = '0,'.intval($this->conf['shownelements']);
		}
		if($this->conf['upcoming'] == 1) {
			$d = mktime(0,0,1);
			$andWhere = ' AND datetime>'.$d;
		}
		if (!$this->conf['detailviewPID']) {
			$this->conf['detailviewPID'] = $GLOBALS['TSFE']->id;
		}

		//get content from database
		$events = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, datetime, subject, series, notes',   #select
			'tx_servicemgr_events', #from
			'hidden=0 AND deleted=0 AND inpreview=1'.$andWhere,  #where
			$groupBy='',
			'datetime',
			$limit
		);



		if (is_array($events)) {
			if($this->conf['upcoming'] == 1) {
				$content = $this->getEventListTable($events);
			} else {
				$this->templateCode = $this->cObj->fileResource('EXT:servicemgr/res/pi1_div.html');
				$key = 'tx_servicemgr_' . md5($this->templateCode);
				if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
					$headerParts = $this->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
				if ($headerParts) {
					$headerParts = $this->cObj->substituteMarker($headerParts, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath('servicemgr'));
					$GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;
				}
			}
				$content = $this->getEventListDiv($events);
			}
		}
		return $content;
	}

	function getEventListTable($events) {
		//Template preparation
		$subpart = $this->cObj->getSubpart($this->template,'###PREVIEWLIST###');
		$headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
		$singlerow = $this->cObj->getSubpart($subpart,'###ROW###');

		if (empty($this->conf['previewcolumns'])) {
			$cols = array('date','subject','notes');
		} else {
			$cols = split(',',$this->conf['previewcolumns']);
		}

		$colHeaders = array();
		foreach ($cols as $col) {
			$colHeaders[]= $this->pi_getLL($col);
		}
		$subpartArray['###HEADERROW###'] = $this->getTemplatedRow($headerrow,'###LABEL###',$colHeaders);

		$series = $this->getSeries();

		if ($this->conf['categorizebyseries'] == 1) {
			$wiredEventsSeries = $this->wireEventsAndSeries($events);

			$tempEvents = array();
			foreach ($wiredEventsSeries as $k => $eventGroupAndSeries) {

				if (!(($this->generalConf['defaultSeriesId'] == $eventGroupAndSeries['series']) && ($this->conf['dontShowDefaultSeries'] == 1))) {
					$tempEvents[] = array(
						'isSeries' => 1,
						'subject' => '&nbsp;',
						'param' => ' colspan="'.count($cols).'"',
					);
					$tempEvents[] = array(
						'isSeries' => 1,
						'subject' => $series[$eventGroupAndSeries['series']]['name'],
						'param' => ' colspan="'.count($cols).'" class="sep-series"' ,
					);
				} elseif ($k !== 0) {
					$tempEvents[] = array(
						'isSeries' => 1,
						'subject' => '&nbsp;',
						'param' => ' colspan="'.count($cols).'"',
					);
				}
				foreach ($eventGroupAndSeries['events'] as $singleEventOfSeries) {
					$tempEvents[] = $events[$singleEventOfSeries];
				}
			}
		} else {
			$tempEvents = $events;
		}

		foreach ($tempEvents as $row) {
			if ($row['isSeries'] == 1) {
				$rowData = array($row['subject']);
				$liste .= $this->getTemplatedRow($singlerow,'###VALUE###',$rowData, $row['param']);
			} else {
				$rowData = array();
				$rowData['date'] = date('d.m.Y', $row['datetime']);

				//create link to sermon archive?
				if ($this->generalConf['SermonArchivePID']) {
					$rowData['subject'] = $this->tx_linkToPage(
						$row['subject'],
						$this->conf['detailviewPID'],
						array(
							'tx_servicemgr_pi1[eventId]' => $row['uid'],
							'tx_servicemgr_pi1[backlink]' => $GLOBALS['TSFE']->id,
						)
					);
				} else {
					$rowData['subject'] = $row['subject'];
				}

				$rowData['notes'] = $row['notes'];

				$liste .= $this->getTemplatedRow($singlerow,'###VALUE###',$rowData);
			}
		}
		$subpartArray['###ROW###']=$liste;
		return $this->substituteMarkersAndSubparts($subpart,array(),$subpartArray);
	}

	/**
	 * Generates table row with data
	 *
	 * @param	string		$wrap: template with subpart and markers included
	 * @param	string		$subpart: name of subpart in wrap
	 * @param	array		$rowData: array of data
	 * @param	string		$cellParam: parameter for table cell, must start with leading whitespace
	 * @return	string		HTML
	 */
	function getTemplatedRow($wrap, $subpart, $rowData, $cellParam = '') {
		$template = $this->cObj->getSubpart($wrap, $subpart);

		foreach ($rowData as $data) {
			$markers = array(
				'###DATA###' => $data,
				'###CELLPARAM###' => $cellParam,
			);
			$rowContent .= $this->cObj->substituteMarkerArray($template,$markers);
		}

		return $this->cObj->substituteSubpart($wrap,$subpart,$rowContent);
	}

	function getEventListDiv($events) {
		$subpart = $this->cObj->getSubpart($this->templateCode,'###PI1_LIST###');
		$singlerow = $this->cObj->getSubpart($subpart,'###EVENT###');

		if (empty($this->conf['previewcolumns'])) {
			$cols = array('date','subject','notes');
		} else {
			$cols = split(',',$this->conf['previewcolumns']);
		}


		$series = $this->getSeries();

		if ($this->conf['categorizebyseries'] == 1) {
			$wiredEventsSeries = $this->wireEventsAndSeries($events);

			$output = array();
			foreach ($wiredEventsSeries as $k => $eventGroupAndSeries) {

				$marker = array(
					'###HEADER###' => '',
					'###HEADER_CLASS###' => '',
					'###SERIES_CLASS###' => $series[$eventGroupAndSeries['series']]['colorscheme'] ? ' ' . $series[$eventGroupAndSeries['series']]['colorscheme'] : ' blueseries',
				);

				if (!(($this->generalConf['defaultSeriesId'] == $eventGroupAndSeries['series']) && ($this->conf['dontShowDefaultSeries'] == 1))) {
					$marker['###HEADER###'] = $this->pi_getLL('series') . ': ' . $series[$eventGroupAndSeries['series']]['name'];
				} else {
					$marker['###HEADER###'] = '';
				}

				$output_events = array();
				foreach ($eventGroupAndSeries['events'] as $singleEventOfSeries) {
					$events[$singleEventOfSeries]['date'] = date('d.m.', $events[$singleEventOfSeries]['datetime']);
					$tempOutput = '';
					foreach ($cols as $col) {
						$tempOutput .= '<span class="tx-servicemgr-pi1-' . $col . '>' . $events[$singleEventOfSeries][$col] . '</span>';
					}

					//create link to sermon archive?
					if ($this->generalConf['SermonArchivePID']) {
						$tempOutput = $this->tx_linkToPage(
							$tempOutput,
							$this->conf['detailviewPID'],
							array(
								'tx_servicemgr_pi1[eventId]' => $events[$singleEventOfSeries]['uid'],
								'tx_servicemgr_pi1[backlink]' => $GLOBALS['TSFE']->id,
							)
						);
					}

					$tempOutput .= '<div class="clear"></div>';
					$output_events[] = $this->cObj->substituteMarker($singlerow, '###DATA###', $tempOutput);
				}

				$tempContent = $this->cObj->substituteSubpart($subpart, '###EVENT###', implode(Chr(10), $output_events));
				$output[] = $this->cObj->substituteMarkerArray($tempContent, $marker);
			}
		} else {
		}
		return implode(Chr(10), $output);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi1/class.tx_servicemgr_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi1/class.tx_servicemgr_pi1.php']);
}

?>
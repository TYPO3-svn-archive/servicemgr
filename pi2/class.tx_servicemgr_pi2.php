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


		//DEBUG-CONFIG
		$GLOBALS['TYPO3_DB']->debugOutput = true;
//		t3lib_div::debug($this->conf, 'TypoScript');
//		t3lib_div::debug($this->extConf, 'extConf');
//		t3lib_div::debug($this->generalConf, 'generalConf');
//		t3lib_div::debug($this->piVars, 'piVars');

		$this->piVars['eventId'] = intVal($this->piVars['eventId']);

		$this->template = $this->generalConf['TemplateFile'];
		if (!$this->template) {
			$this->template = 'EXT:servicemgr/res/tables.tmpl';
		}
		$this->template = $this->cObj->fileResource($this->template);

		if (!$this->piVars['eventId']) {
			$content = $this->listView();
		} else {
			$content=$this->detailViewEvent(
				$this->piVars['eventId'],
				array('fields'=>array('subject','date','time', 'series')),
				$this->cObj->getSubpart($this->cObj->fileResource('EXT:servicemgr/res/esv.html'), '###SINGLEEVENTEL###')
			);
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function listView() {

		//Template preparation
		#Subpart
        $subpart = $this->cObj->getSubpart($this->template,'###SERMONLIST###');
        #header row
        $headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
        #single row
        $singlerow = $this->cObj->getSubpart($subpart,'###ROW###');
        #file array
        $filearray = $this->cObj->getSubpart($subpart,'###FILES###');

        //substitue table header in template file
        $markerArray['###HDATE###'] = $this->pi_getLL('date');
        $markerArray['###HSUBJECT###'] = $this->pi_getLL('subject');
        $markerArray['###HFILE###'] = $this->pi_getLL('file');
        $subpartArray['###HEADERROW###'] = $this->cObj->substituteMarkerArray($headerrow,$markerArray);


		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime, subject',   #select
        	'tx_servicemgr_events', #from
        	'hidden=0 and deleted=0'  #where
        );

		//substitue table rows in template
        if ($res) {
        	while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
        		$markerArray['###DATE###'] = date('d.m.Y', $row['datetime']);
				$markerArray['###SUBJECT###'] = $row['subject'];

				$sermons = $this->getAudioFiles($row['uid']);

				$liste2='';
				foreach ($sermons as $sermon) {
					$markerArray['###FILETITLE###'] = $sermon['title'];
        			$markerArray['###SIZE###'] = $this->formatBytes($sermon['filesize']);
        			$markerArray['###LENGTH###'] = $this->formatTime($sermon['playtime']);
        			$markerArray['###DOWNLOAD###'] = $this->pi_linkToPage(
        				'DL',
        				$GLOBALS['TSFE']->id,
        				$target='',
        				$urlParameters = array(
        					'eID' => 'tx_servicemgr_download',
        					'sermonid'=>$sermon['uid']
        				)
        			);
        			$markerArray['###PLAY###'] = 'Play';
        			$liste2 .= $this->cObj->substituteMarkerArray($filearray,$markerArray);
				}
				$subpartArray['###FILES###']=$liste2;
                $liste .= $this->substituteMarkersAndSubparts($singlerow,$markerArray,$subpartArray);
            }
            $subpartArray['###ROW###']=$liste;
        }

        return $this->substituteMarkersAndSubparts($subpart,$markerArray,$subpartArray);
	}

	/**
	 * formats bytes in Bytes, Kilobytes or Megabytes
	 * output with two positions after decimal point
	 *
	 * @param	integer		$bytes
	 * @return	string
	 */
	function formatBytes($bytes) {
		$bytes = intval($bytes);
		if ($bytes > 1024) {
			$bytes /= 1024;
			if ($bytes > 1024) {
				$bytes /= 1024;
				$bytes_seperated = split('\.', $bytes);
				$bytes = $bytes_seperated[0].$this->pi_getLL('decimalchar').substr($bytes_seperated[1], 0, 2).' MB';
			} else {
				$bytes_seperated = split('\.', $bytes);
				$bytes = $bytes_seperated[0].$this->pi_getLL('decimalchar').substr($bytes_seperated[1], 0, 2).' KB';
			}
		} else {
			$bytes .= ' B';
		}
		return $bytes;
	}

	/**
	 * splits seconds in seconds, minutes and hours
	 * output like 'hh:mm:ss Std', 'm:ss Min', ...
	 *
	 * @param	integer		$seconds: time in seconds
	 * @return	string		formated time
	 */
	function formatTime($seconds) {
		$seconds = intval($seconds);
		$content = '';
		$output = array();
		if ($seconds > 60) {
			 $output[0] = $seconds - (60 * intval($seconds / 60));
			 $seconds = intval($seconds / 60);
			 if ($seconds > 60) {
			 	$output[1] = $seconds - (60 * intval($seconds / 60));
			 	$seconds = intval($seconds / 60);
			 	if ($seconds > 60) {
			 		$output[2] = $seconds - (60 * intval($seconds / 60));
			 		$seconds = intval($seconds / 60);
			 	} else {
			 		$output[2] = $seconds;
			 	}
			 } else {
			 	$output[1] = $seconds;
			 }
		} else {
		 	$output[0] = $seconds;
		}
		switch (count($output)) {
			CASE 1:
				$content = substr('00'.$output[0],-2,2).' s';
				break;
			CASE 2:
				$content = $output[1].':'.substr('00'.$output[0],-2,2).' Min';
				break;
			CASE 3:
				$content = $output[2].substr('00'.$output[1],-2,2).':'.substr('00'.$output[0],-2,2).'Std';
				break;
			DEFAULT:
				$content = '0 s';
		}
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$path: ...
	 * @return	[type]		...
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
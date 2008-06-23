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
require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');

/**
 * Plugin 'Sermon archive' for the 'servicemgr' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_servicemgr
 */
class tx_servicemgr_pi2 extends tslib_pibase {
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
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		
		$this->tx_servicemgr = t3lib_div::makeInstance('tx_servicemgr');
		$this->tx_servicemgr->init();
		
		$this->pi_loadLL();
		$this->tx_servicemgr->tx_loadCommonLL($this->LOCAL_LANG);
		
		$GLOBALS['TSFE']->set_no_cache();
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		t3lib_div::debug($this->conf, 'TypoScript');
		t3lib_div::debug($this->tx_servicemgr->extConf, 'extConf');
		t3lib_div::debug($this->tx_servicemgr->generalConf, 'generalConf');

		$this->template = $this->tx_servicemgr->generalConf['TemplateFile'];
		if (!$this->template) {
			$this->template = 'EXT:servicemgr/res/tables.tmpl';
		}
		$this->template = $this->cObj->fileResource($this->template);
		
		if (!$this->piVars['eventId']) {
			$content = $this->listView();
		} else {
			$content='
				<strong>This is a few paragraphs:</strong><br />
				<p>This is line 1</p>
				<p>This is line 2</p>
		
				<h3>This is a form:</h3>
				<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" method="POST">
					<input type="hidden" name="no_cache" value="1">
					<input type="text" name="'.$this->prefixId.'[input_field]" value="'.htmlspecialchars($this->piVars['input_field']).'">
					<input type="submit" name="'.$this->prefixId.'[submit_button]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">
				</form>
				<br />
				<p>You can click here to '.$this->pi_linkToPage('get to this page again',$GLOBALS['TSFE']->id).'</p>
			';
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function listView() {
		
		//Template preparation
		#Subpart
        $subpart = $this->cObj->getSubpart($this->template,'###SERMONLIST###');
        #header row
        $headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
        #single row
        $singlerow = $this->cObj->getSubpart($subpart,'###ROW###');
		
        //substitue table header in template file
        $markerArray['###HDATE###'] = $this->pi_getLL('date');
        $markerArray['###HSUBJECT###'] = $this->pi_getLL('subject');
        $markerArray['###HSIZELENGTH###'] = '';
        $subpartArray['###HEADERROW###'] = $this->cObj->substituteMarkerArrayCached($headerrow,$markerArray);
		
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime, subject, audiofiles',   #select
        	'tx_servicemgr_events', #from
        	'hidden=0 and deleted=0',  #where
        	$groupBy='',
        	$orderBy='',
        	$limit=''
        );
        
		//substitue table rows in template
        if ($res) {
        	while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
        		$markerArray['###DATE###'] = date('d.m.Y', $row['datetime']);
				$markerArray['###SUBJECT###'] = $row['subject'];
       		
				$sizelength = split(',', $row['audiosize']);
				if (empty($sizelength[0])) {
					$sizelength[0] = $this->getFileSizeFormarted($row['audiofiles']);
				}
        		$markerArray['###SIZELENGTH###'] = $sizelength[0];
        		$markerArray['###DOWNLOAD###'] = $row['audiofiles'];
        		$markerArray['###PLAY###'] = '&gt;';
                $liste .= $this->cObj->substituteMarkerArrayCached($singlerow,$markerArray); 
            }
            $subpartArray['###ROW###']=$liste;
        }
        
        return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,array()); ;
	}
	
	function getFileSizeFormarted($path) {
		if (@file_exists($path)) {
			$filesize = filesize($path);
			if ($filesize > 1024) {
				$filesize /= 1024;
				if ($filesize > 1024) {
					$filesize /= 1024;
					$filesize_seperated = split('.', $filesize);
					$filesize = $filesize_seperated.$this->pi_getLL('decimalchar').substr($filesize_seperated[1], 0, 2).' MB';
				} else {
					$filesize_seperated = split('.', $filesize);
					$filesize = $filesize_seperated.$this->pi_getLL('decimalchar').substr($filesize_seperated[1], 0, 2).' KB';
				}
			} else {
				$filesize_seperated = split('.', $filesize);
				$filesize = $filesize_seperated.' B';
			}
		} else {
			$filesize = "0 MB";
		}
		return $filesize;
	}
	
	function getAudioLength($path) {
		if (t3lib_extMgm::isLoaded('t3getid3')) {
			require_once(t3lib_extMgm::extPath('t3getid3').'getid3/getid3.php');
		} else {
			die('GetID3() Library not loaded!');
		}

		// Get a new instance of the GetID3 library
		$getID3 = t3lib_div::makeInstance('getID3');
		
		// Analyze file
		if (file_exists($path))
		{
			$getID3->Analyze($path);

		    // Show audio bitrate and length
    		$audiolength = @$getID3->info['playtime_string'];
		} else {
	    	$audiolength = "--:--";
		}
		return $audiolength;
	}

} //end class


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi2/class.tx_servicemgr_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi2/class.tx_servicemgr_pi2.php']);
}

?>
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
	var $tx_servicemgr;


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


		//DEBUG-CONFIG
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		t3lib_div::debug($this->conf, 'TypoScript');
		t3lib_div::debug($this->extConf, 'extConf');
		t3lib_div::debug($this->generalConf, 'generalConf');
		t3lib_div::debug($this->piVars, 'piVars');

		$this->piVars['eventId'] = intVal($this->piVars['eventId']);

		$this->template = $this->generalConf['TemplateFile'];
		if (!$this->template) {
			$this->template = 'EXT:servicemgr/res/tables.tmpl';
		}
		$this->template = $this->cObj->fileResource($this->template);

		if (empty($this->piVars['eventId'])) {
			$content = 'VOLL'.$this->listView();
		} else {
			$content= 'LEER';
		}
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * returns event preview table
	 *
	 * @return	string		event preview table
	 */
	function listView() {

		//Template preparation
		#Subpart
        $subpart = $this->cObj->getSubpart($this->template,'###PREVIEWLIST###');
        #header row
        $headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
        #single row
        $singlerow = $this->cObj->getSubpart($subpart,'###ROW###');

        //substitue table header in template file
        $markerArray['###HDATE###'] = $this->pi_getLL('date');
        $markerArray['###HSUBJECT###'] = $this->pi_getLL('subject');
        $markerArray['###HNOTES###'] = $this->pi_getLL('notes');
        $subpartArray['###HEADERROW###'] = $this->cObj->substituteMarkerArrayCached($headerrow,$markerArray);

		//get content from database
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime, subject, notes',   #select
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

        		//create link to sermon archive?
        		if ($this->tx_servicemgr->generalConf['SermonArchivePID']) {
        			$markerArray['###SUBJECT###'] = $this->pi_linkToPage(
        				$row['subject'],
        				$this->tx_servicemgr->generalConf['SermonArchivePID'],
        				$target='',
        				$urlParameters=array('tx_servicemgr_pi2[eventId]'=>$row['uid'])
        			);
        		} else {
        			$markerArray['###SUBJECT###'] = $row['subject'];
        		}

        		$markerArray['###NOTES###'] = $row['notes'];
                $liste .= $this->cObj->substituteMarkerArrayCached($singlerow,$markerArray);
            }
            $subpartArray['###ROW###']=$liste;
        }

		return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,array()); ;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi1/class.tx_servicemgr_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi1/class.tx_servicemgr_pi1.php']);
}

?>
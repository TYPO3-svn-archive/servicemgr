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

		//Template preparation
        $subpart = $this->cObj->getSubpart($this->template,'###PREVIEWLIST###');
        $headerrow = $this->cObj->getSubpart($subpart,'###HEADERROW###');
        $singlerow = $this->cObj->getSubpart($subpart,'###ROW###');

        if (empty($this->conf['previewcolumns'])) {
        	$cols = array('date','subject','notes');
        } else {
        	$cols = split(',',$this->conf['previewcolumns']);
        }
        
        $colHeaders = '';
        $colHeader = $this->cObj->getSubpart($headerrow,'###LABEL###');
        foreach ($cols as $col) {
        	$colHeaders .= $this->cObj->substituteMarker($colHeader,'###H_LABEL###',$this->pi_getLL($col));
        }
        $subpartArray['###HEADERROW###'] = $this->cObj->substituteSubpart($headerrow,'###LABEL###',$colHeaders);
        
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime, subject, notes',   #select
        	'tx_servicemgr_events', #from
        	'hidden=0 AND deleted=0'.$andWhere,  #where
        	$groupBy='',
        	'datetime',
        	$limit
        );


        //substitue table rows in template
        if ($res) {
        	$colValue = $this->cObj->getSubpart($singlerow,'###VALUE###');
        	while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
        		$markerArray['date'] = date('d.m.Y', $row['datetime']);

        		//create link to sermon archive?
        		if ($this->generalConf['SermonArchivePID']) {
        			$markerArray['subject'] = $this->tx_linkToPage(
        				$row['subject'],
        				$this->conf['detailviewPID'],
        				array(
        					'tx_servicemgr_pi1[eventId]' => $row['uid'],
        					'tx_servicemgr_pi1[backlink]' => $GLOBALS['TSFE']->id,
        				)
        			);
        		} else {
        			$markerArray['subject'] = $row['subject'];
        		}

        		$markerArray['notes'] = $row['notes'];
        		
        		$rowContent = '';
        		foreach ($cols as $col) {
        			$rowContent .= $this->cObj->substituteMarker($colValue,'###H_VALUE###',$markerArray[$col]);
        		}
        		
                $liste .= $this->cObj->substituteSubpart($singlerow,'###VALUE###',$rowContent);
            }
            $subpartArray['###ROW###']=$liste;
        }

		return $this->substituteMarkersAndSubparts($subpart,array(),$subpartArray);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi1/class.tx_servicemgr_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/pi1/class.tx_servicemgr_pi1.php']);
}

?>
<?php
/**
 * ************************************************************
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
 * **************************************************************/

class tx_servicemgr_ajax {
	var $prefixId      = 'tx_servicemgr_ajax';				// Same as class name
	var $scriptRelPath = 'class.tx_servicemgr_ajax.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';						// The extension key.

	function main() {

		switch ($_GET['action']) {
			CASE 'showinlineplayer':
				$playerid = intval($_GET['playerid']);
				$content = $this->getAudioPlayer($playerid);
				break;
			default: 
				break;
		}
		return $content;
	}
	
	function getAudioPlayer($playerid) {
		
		if (t3lib_extMgm::isLoaded('audioplayer')) {
			
			tslib_eidtools::connectDB();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	        	'uid, file',   #select
        		'tx_servicemgr_sermons', #from
        		'uid='.$playerid.' and hidden=0 and deleted=0'  #where
			);
			$res ? $audioFile = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) : die('no fetching result');
			
			require_once(t3lib_extMgm::extPath('audioplayer').'class.tx_audioplayer.php');
			$audioplayer = t3lib_div::makeInstance('tx_audioplayer');
			$audioplayer->init();
			$audioplayer->setOptions(array('autostart'=>true));
			$content = $audioplayer->getFlashPlayer($audioFile['file'], $audioFile['uid']);
		} else {
			$content = 'notloaded';
		}
		return $content;
	}
}

if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');

if (!defined ('T3UNIT_TESTING')) {
	$ajax = t3lib_div::makeInstance('tx_servicemgr_ajax');
	echo $ajax->main();
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_ajax.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_ajax.php']);
}

?>
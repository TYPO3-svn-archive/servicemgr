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

class tx_servicemgr_download{
	var $prefixId      = 'tx_servicemgr_download';		// Same as class name
	var $scriptRelPath = 'class.tx_servicemgr_download.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'servicemgr';	// The extension key.
	
	function main() {
		$sermonId = intval($_GET['sermonid']);
		$fI = $this->getProperties($sermonId);
		if (@file_exists(PATH_site.$fI['file'])) {
			$fI['name'] = str_replace(' ','-',$fI['title']);
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['servicemgr']);
			$fI['prefix'] = $extConf['sermonPrefix'];
			$fI['filename'] = $fI['prefix'].'_'.date('Ymd',$fI['datetime']).'_'.$fI['name'].'.'.$fI['extension'];
			
			header('Content-Type: '.$fI['mimetype']);
			header('Content-Length: '.$fI['filesize']);
			header('Content-Transfer-Encoding: Binary');
			header('Content-Disposition: attachment; filename="'.$fI['filename'].'"');
			@readfile(PATH_site.$fI['file']);
		} else {
			die('file not found');
		}
	}
	
	function getProperties($sermonId) {
		tslib_eidtools::connectDB();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, event, title, file, filedate, playtime, filesize, mimetype, bitrate, album',   #select
        	'tx_servicemgr_sermons', #from
        	'uid='.$sermonId.' and hidden=0 and deleted=0'  #where
		);
		$res ? $sermon = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) : die('no fetching result');
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
        	'uid, datetime',   #select
        	'tx_servicemgr_events', #from
        	'uid='.$sermon['event'].' and hidden=0 and deleted=0'  #where
		);
		$res ? $event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) : die('no fetching result');
		
		$sermon['datetime'] = $event['datetime'];
		$sermon['extension'] = $this->fileExtension($sermon['file']);
		return $sermon;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$filename: ...
	 * @return	[type]		...
	 */
	function fileExtension($filename){
		$parts = split('\.', $filename);
		$parts = array_reverse($parts, false);
		return $parts[0];
	}
}
	
if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');

$download = t3lib_div::makeInstance('tx_servicemgr_download');
$download->main();

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_download.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_download.php']);
}

?>
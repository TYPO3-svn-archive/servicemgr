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
define('T3UNIT_TESTING','1');

require_once(PATH_tslib.'class.tslib_eidtools.php');
require_once (t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr_download.php');

 /**
  * Testcase for checking the servicemgr extension
  *
  * $Id:
  *
  * @author		Peter Schuster <typo3@peschuster.de>
  * @package	TYPO3
  * @subpackage tx_servicemgr
  */
class tx_servicemgr_download_testcase extends tx_t3unit_testcase {

	function test_str2download() {
		$txSMgr_download = new tx_servicemgr_download;
		$result = $txSMgr_download->str2download('Ähmm, döses String - ist [ein] Test.string!');
		self::assertEquals($result, 'Aehmm-doeses-String-ist-ein-Teststring', 'str2download result: '.$result);
		$result = $txSMgr_download->str2download('Ähmm, döses String - ist [ein] Test.string!', false);
		self::assertEquals($result, 'Aehmm-doeses-String-ist-ein-Test.string', 'str2download result: '.$result);
	}
	
	function test_fileExtension() {
		$txSMgr_download = new tx_servicemgr_download;
		$result = $txSMgr_download->fileExtension('hallo.hgt');
		self::assertEquals($result, 'hgt', 'fileExtension result: '.$result);
		$result = $txSMgr_download->fileExtension('ha.llo.xgud');
		self::assertEquals($result, 'xgud', 'fileExtension result: '.$result);
	}
}

?>
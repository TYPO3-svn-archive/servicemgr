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
/**
 * class.tx_servicemgr_mp3.php
 *
 * includes class for dealing with mp3 files
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

/**
 * Functions for handling mp3 files for the 'servicemgr' extension.
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage tx_servicemgr
 */
class tx_servicemgr_mp3 {

	/**
	 * Get ID3-Information from MP3-File
	 *
	 * @param	string		$path: path to file
	 * @return	array		id3 audio data
	 */
	function getAudioInformation($path) {

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
			$fileInformation = $getID3->Analyze($path);
			getid3_lib::CopyTagsToComments($fileInformation);

		    return array(
				'filename'=>$fileInformation['filename'],
				'bitrate'=>$fileInformation['bitrate'],
		    	'samplerate'=>$fileInformation['audio']['sample_rate'],
				'playtime'=>intval($fileInformation['playtime_seconds']),
				'artist'=>implode(' & ', $fileInformation['comments_html']['artist']),
				'album'=>$fileInformation['comments']['album'],
		    	'title'=>$fileInformation['comments']['title'],
				'year'=>$fileInformation['comments']['year'],
		    	'genre'=>$fileInformation['comments']['genre'],
		    	'filesize'=>$fileInformation['filesize'],
		    	'id3version'=>$fileInformation['getID3version']
			);

		} else {
	    	return false;
		}
	}

	/**
	 * Writes tags to MP3-file
	 *
	 * @param	string		$path: path to file
	 * @param	array		$fileInformation: id3 data to be set
	 * @return	mixed		true or error msg
	 */
	function setAudioInformation($path, $fileInformation) {
		if (t3lib_extMgm::isLoaded('t3getid3')) {
			require_once(t3lib_extMgm::extPath('t3getid3').'getid3/getid3.php');
		} else {
			die('GetID3() Library not loaded!');
		}

		// Get a new instance of the GetID3 library
		$getID3 = t3lib_div::makeInstance('getID3');
		$getID3->setOption(array('encoding'=>'UTF-8'));

		if (t3lib_extMgm::isLoaded('t3getid3')) {
			require_once(t3lib_extMgm::extPath('t3getid3').'getid3/write.php');
		} else {
			die('GetID3() Library not loaded!');
		}


		$tagwriter = t3lib_div::makeInstance('getid3_writetags');

		if (file_exists($path))
		{
			$tagwriter->filename = $path;
			$tagwriter->tagformats[] = 'id3v2.3';
			$tagwriter->remove_other_tags = false;
			$tagwriter->overwrite_tags = true;


			$tagData['ARTIST'][] = implode(' & ', $fileInformation['artist']);
			$tagData['ALBUM'][] = $fileInformation['album'];
		    $tagData['TITLE'][] = $fileInformation['title'];
			$tagData['YEAR'][] = $fileInformation['year'];
		    $tagData['GENRE'][] = $fileInformation['genre'];

			$tagwriter->tag_data = $tagData;

			if ($tagwriter->WriteTags()) {
				if (!empty($tagwriter->warnings)) {
					return implode('\n', $tagwriter->warnings);
				} else {
					return true;
				}
			} else {
				return implode('\n', $tagwriter->errors);
			}

		} else {
	    	return false;
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_mp3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_mp3.php']);
}

?>
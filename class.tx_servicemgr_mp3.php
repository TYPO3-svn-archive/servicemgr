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

require_once(t3lib_extMgm::extPath('servicemgr').'class.tx_servicemgr.php');
if (t3lib_extMgm::isLoaded('t3getid3')) {
	require_once(t3lib_extMgm::extPath('t3getid3').'getid3/getid3.php');

}

/**
 * Functions for handling mp3 files for the 'servicemgr' extension.
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage tx_servicemgr
 */
class tx_servicemgr_mp3 extends tx_servicemgr {
	var $getID3;
	var $id3TagWriter;

	function __construct() {
		if (t3lib_extMgm::isLoaded('t3getid3')) {
			$this->getID3 = t3lib_div::makeInstance('getID3');

			require_once(t3lib_extMgm::extPath('t3getid3').'getid3/write.php');
			$this->id3TagWriter = t3lib_div::makeInstance('getid3_writetags');

			$this->tx_init();
		} else {
			die('GetID3() Library not loaded!');
		}
	}

	/**
	 * Get ID3-Information from MP3-File
	 *
	 * @param	string		$path: path to file
	 * @return	array		id3 audio data
	 */
	function getAudioInformation($path) {
		// Analyze file
		if (file_exists($path)) {
			$fileInformation = $this->getID3->Analyze($path);
			getid3_lib::CopyTagsToComments($fileInformation);

			if (!is_array($fileInformation['comments_html']['artist'])) $fileInformation['comments_html']['artist'] = array();

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
		$this->getID3->setOption(array('encoding'=>'UTF-8'));

		if (file_exists($path))
		{
			$this->id3TagWriter->filename = $path;
			$this->id3TagWriter->tagformats[] = 'id3v2.3';
			$this->id3TagWriter->remove_other_tags = false;
			$this->id3TagWriter->overwrite_tags = true;

			$tagData['ARTIST'][] = implode(' & ', $fileInformation['artist']);
			$tagData['ALBUM'][] = $fileInformation['album'];
			$tagData['TITLE'][] = $fileInformation['title'];
			$tagData['YEAR'][] = $fileInformation['year'];
			$tagData['GENRE'][] = $fileInformation['genre'];

			$this->id3TagWriter->tag_data = $tagData;

			if ($this->id3TagWriter->WriteTags()) {
				if (!empty($this->id3TagWriter->warnings)) {
					return implode('\n', $this->id3TagWriter->warnings);
				} else {
					return true;
				}
			} else {
				return implode('\n', $this->id3TagWriter->errors);
			}

		} else {
				return false;
		}
	}

	/**
	 * Checks whether file was modified
	 *
	 * @param	integer		$sermonUid: uid of sermon to check
	 */
	function checkFile($sermonUid) {

		$sermon = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid, event, title, file, filedate, playtime, filesize, bitrate, album',   #select
					'tx_servicemgr_sermons', #from
					'uid='.$sermonUid  #where
		);
		if (is_array($sermon[0])) $sermon = $sermon[0];

		if (@file_exists(PATH_site.$sermon['path'])) {
			$last_modified = filemtime(PATH_site.$sermon['path']);
			if ($last_modified != $sermon['filedate']) {
				$sermon = $this->updateFile($sermonUid, $sermon);
			}
		}
		return $sermon;
	}

	/**
	 * Updates id3 tags of sermon file and file information in database
	 *
	 * @param	integer		$sermonUid: uid of sermon to be updated
	 * @param	array		$sermon: array with sermon information out of database
	 */
	function updateFile($sermonUid,$sermon=array()) {
		if ($sermon == array()) {
			$sermon = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid, event, title, file, filedate, playtime, filesize, bitrate, album',   #select
					'tx_servicemgr_sermons', #from
					'uid='.$sermonUid  #where
			);
		}
		if (is_array($sermon[0])) $sermon = $sermon[0];

		if (@file_exists(PATH_site.$sermon['path'])) {
			$last_modified = filemtime(PATH_site.$sermon['path']);

			$event = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid, datetime, subject, series',   #select
				'tx_servicemgr_events', #from
				'uid='.$sermon['event']  #where
			);
			if (is_array($event[0])) $event = $event[0];

			//UPDATE DB-DATA
			$fI = $this->getAudioInformation(PATH_site.$sermon['file']);
			$sermonData = array(
				'tstamp' => mktime(),
				'filedate' => $last_modified,
				'playtime' => $fI['playtime'],
				'filesize' => $fI['filesize'],
				'bitrate' => $fI['bitrate'],
				'album' => $event['series']
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_servicemgr_sermons', #table
				'uid='.$sermon['uid'], #WHERE
				$sermonData #data
			);


			//UPDATE ID3-DATA
			$dutyschedule = $this->getSingleSchedule($event['uid']);
			$preacherTeamSchedule = $dutyschedule[$this->generalConf['PreacherTeamUID']];

			$fI = array();
			$fI['artist'] = array();
			if (is_array($preacherTeamSchedule)) {
				$users = $this->getTeamMembers($this->generalConf['PreacherTeamUID']);
				foreach ($preacherTeamSchedule as $userId) {
					$fI['artist'][] = $users[$userId]['name'];
				}
			}

			$series = $this->getSeries();
			$fI['album'] = $series[$event['series']]['name'];
			$fI['title'] = $sermon['title'];
			$fI['year'] = date('Y', $event['datetime']);
			$fI['genre'] = $this->extConf['sermonGenre'];

			$this->setAudioInformation(PATH_site.$sermon['file'], $fI);

			$sermon = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid, event, title, file, filedate, playtime, filesize, bitrate, album',   #select
					'tx_servicemgr_sermons', #from
					'uid='.$sermonUid  #where
			);
			if (is_array($sermon[0])) $sermon = $sermon[0];
		}
		return $sermon;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_mp3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/servicemgr/class.tx_servicemgr_mp3.php']);
}

?>
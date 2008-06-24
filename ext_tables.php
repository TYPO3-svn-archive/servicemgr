<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA["tx_servicemgr_events"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events',		
		'label'     => 'subject',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => "ORDER BY datetime",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_events.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, datetime, subject, public, series, tags, requiredteams, documents, notes",
	)
);

$TCA["tx_servicemgr_series"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_series',		
		'label'     => 'name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_series.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, name",
	)
);

$TCA["tx_servicemgr_tags"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_tags',		
		'label'     => 'name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_tags.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, parrent",
	)
);

$TCA["tx_servicemgr_teamtype"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_teamtype',		
		'label'     => 'name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_teamtype.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, name",
	)
);

$TCA["tx_servicemgr_dutyschedule"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_dutyschedule',		
		'label'     => 'event',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_dutyschedule.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, event, duty",
	)
);

$TCA["tx_servicemgr_statistics"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics',		
		'label'     => 'recordid',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_statistics.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, recordid, type, hit",
	)
);

$TCA["tx_servicemgr_sermons"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons',		
		'label'     => 'title',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_servicemgr_sermons.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, title, file, filedate, playtime, filesize, mimetype, bitrate, album",
	)
);

$tempColumns = Array (
	"tx_servicemgr_image" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_users.tx_servicemgr_image",		
		"config" => Array (
			"type" => "group",
			"internal_type" => "file",
			"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
			"max_size" => 500,	
			"uploadfolder" => "uploads/tx_servicemgr",
			"size" => 1,	
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
	"tx_servicemgr_description" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_users.tx_servicemgr_description",		
		"config" => Array (
			"type" => "text",
			"cols" => "30",	
			"rows" => "5",
		)
	),
);


t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","tx_servicemgr_image;;;;1-1-1, tx_servicemgr_description");

$tempColumns = Array (
	"tx_servicemgr_leaders" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_leaders",		
		"config" => Array (
			"type" => "select",	
			"foreign_table" => "fe_users",	
			"foreign_table_where" => "AND fe_users.usergroup LIKE '%###THIS_UID###%' ORDER BY fe_users.uid",	
			"size" => 10,	
			"minitems" => 0,
			"maxitems" => 10,
		)
	),
	"tx_servicemgr_category" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_categroy",		
		"config" => Array (
			"type" => "select",	
			"foreign_table" => "tx_servicemgr_teamtype",	
			"foreign_table_where" => "ORDER BY tx_servicemgr_teamtype.uid",	
			"size" => 1,	
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
	"tx_servicemgr_isteam" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_isteam",		
		"config" => Array (
			"type" => "check",
		)
	),
	"tx_servicemgr_dutyschedule" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_dutyschedule",		
		"config" => Array (
			"type" => "check",
		)
	),
	"tx_servicemgr_asteaminschedule" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_asteaminschedule",		
		"config" => Array (
			"type" => "check",
		)
	),
	"tx_servicemgr_image" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_image",		
		"config" => Array (
			"type" => "group",
			"internal_type" => "file",
			"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
			"max_size" => 500,	
			"uploadfolder" => "uploads/tx_servicemgr",
			"size" => 1,	
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
	"tx_servicemgr_description" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:servicemgr/locallang_db.xml:fe_groups.tx_servicemgr_description",		
		"config" => Array (
			"type" => "text",
			"cols" => "30",	
			"rows" => "7",
		)
	),
);


t3lib_div::loadTCA("fe_groups");
t3lib_extMgm::addTCAcolumns("fe_groups",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_groups","tx_servicemgr_leaders;;;;1-1-1, tx_servicemgr_category, tx_servicemgr_isteam, tx_servicemgr_dutyschedule, tx_servicemgr_asteaminschedule, tx_servicemgr_image, tx_servicemgr_description");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:servicemgr/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","Event preview");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:servicemgr/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi2/static/","Sermon archive");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi3']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:servicemgr/locallang_db.xml:tt_content.list_type_pi3', $_EXTKEY.'_pi3'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi3/static/","Sermon administration");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi4']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:servicemgr/locallang_db.xml:tt_content.list_type_pi4', $_EXTKEY.'_pi4'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi4/static/","Teams");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi5']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:servicemgr/locallang_db.xml:tt_content.list_type_pi5', $_EXTKEY.'_pi5'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi5/static/","Duty schedule");
?>
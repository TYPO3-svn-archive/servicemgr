<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_servicemgr_events"] = array (
	"ctrl" => $TCA["tx_servicemgr_events"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,datetime,subject,public,series,tags,requiredteams,dutyscheduleopen,documents,audiofiles,notes,notes_internal"
	),
	"feInterface" => $TCA["tx_servicemgr_events"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_servicemgr_events',
				'foreign_table_where' => 'AND tx_servicemgr_events.pid=###CURRENT_PID### AND tx_servicemgr_events.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"datetime" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.datetime",		
			"config" => Array (
				"type"     => "input",
				"size"     => "12",
				"max"      => "20",
				"eval"     => "datetime",
				"checkbox" => "0",
				"default"  => "0"
			)
		),
		"subject" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.subject",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required,trim",
			)
		),
		"public" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.public",		
			"config" => Array (
				"type" => "check",
				"default" => 1,
			)
		),
		"series" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.series",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_servicemgr_series",	
				"foreign_table_where" => "AND tx_servicemgr_series.pid=###STORAGE_PID### ORDER BY tx_servicemgr_series.uid",	
				"size" => 5,	
				"minitems" => 0,
				"maxitems" => 1,	
				"wizards" => Array(
					"_PADDING" => 2,
					"_VERTICAL" => 1,
					"add" => Array(
						"type" => "script",
						"title" => "Create new record",
						"icon" => "add.gif",
						"params" => Array(
							"table"=>"tx_servicemgr_series",
							"pid" => "###CURRENT_PID###",
							"setValue" => "prepend"
						),
						"script" => "wizard_add.php",
					),
					"list" => Array(
						"type" => "script",
						"title" => "List",
						"icon" => "list.gif",
						"params" => Array(
							"table"=>"tx_servicemgr_series",
							"pid" => "###CURRENT_PID###",
						),
						"script" => "wizard_list.php",
					),
				),
			)
		),
		"tags" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.tags",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_servicemgr_tags",	
				"foreign_table_where" => "AND tx_servicemgr_tags.pid=###STORAGE_PID### ORDER BY tx_servicemgr_tags.uid",	
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 20,	
				"wizards" => Array(
					"_PADDING" => 2,
					"_VERTICAL" => 1,
					"add" => Array(
						"type" => "script",
						"title" => "Create new record",
						"icon" => "add.gif",
						"params" => Array(
							"table"=>"tx_servicemgr_tags",
							"pid" => "###CURRENT_PID###",
							"setValue" => "prepend"
						),
						"script" => "wizard_add.php",
					),
					"list" => Array(
						"type" => "script",
						"title" => "List",
						"icon" => "list.gif",
						"params" => Array(
							"table"=>"tx_servicemgr_tags",
							"pid" => "###CURRENT_PID###",
						),
						"script" => "wizard_list.php",
					),
				),
			)
		),
		"requiredteams" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.requiredteams",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "fe_groups",	
				"foreign_table_where" => "ORDER BY fe_groups.uid",	
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 20,
			)
		),
		"dutyscheduleopen" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.dutyscheduleopen",		
			"config" => Array (
				"type" => "check",
				"default" => 1,
			)
		),
		"documents" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.documents",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "",	
				"disallowed" => "php,php3",	
				"max_size" => 1000,	
				"uploadfolder" => "uploads/tx_servicemgr",
				"size" => 5,	
				"minitems" => 0,
				"maxitems" => 10,
			)
		),
		"notes" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.notes",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"notes_internal" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_events.notes_internal",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, datetime, subject, public, series, tags, requiredteams, dutyscheduleopen, documents, notes, notes_internal")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_servicemgr_series"] = array (
	"ctrl" => $TCA["tx_servicemgr_series"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name"
	),
	"feInterface" => $TCA["tx_servicemgr_series"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_servicemgr_series',
				'foreign_table_where' => 'AND tx_servicemgr_series.pid=###CURRENT_PID### AND tx_servicemgr_series.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_series.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required,trim",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_servicemgr_tags"] = array (
	"ctrl" => $TCA["tx_servicemgr_tags"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,parrent"
	),
	"feInterface" => $TCA["tx_servicemgr_tags"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_servicemgr_tags',
				'foreign_table_where' => 'AND tx_servicemgr_tags.pid=###CURRENT_PID### AND tx_servicemgr_tags.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_tags.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required,trim",
			)
		),
		"parrent" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_tags.parrent",		
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("",0),
				),
				"foreign_table" => "tx_servicemgr_tags",	
				"foreign_table_where" => "ORDER BY tx_servicemgr_tags.uid",	
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, parrent")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_servicemgr_teamtype"] = array (
	"ctrl" => $TCA["tx_servicemgr_teamtype"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,inschedule"
	),
	"feInterface" => $TCA["tx_servicemgr_teamtype"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_servicemgr_teamtype',
				'foreign_table_where' => 'AND tx_servicemgr_teamtype.pid=###CURRENT_PID### AND tx_servicemgr_teamtype.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_teamtype.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required,trim",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_servicemgr_dutyschedule"] = array (
	"ctrl" => $TCA["tx_servicemgr_dutyschedule"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,event"
	),
	"feInterface" => $TCA["tx_servicemgr_dutyschedule"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_servicemgr_dutyschedule',
				'foreign_table_where' => 'AND tx_servicemgr_dutyschedule.pid=###CURRENT_PID### AND tx_servicemgr_dutyschedule.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"event" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_dutyschedule.event",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_servicemgr_events",	
				"foreign_table_where" => "AND tx_servicemgr_events.pid=###STORAGE_PID### ORDER BY tx_servicemgr_events.uid",	
				"size" => 20,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, event")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_servicemgr_statistics"] = array (
	"ctrl" => $TCA["tx_servicemgr_statistics"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,recordid,type,hit"
	),
	"feInterface" => $TCA["tx_servicemgr_statistics"]["feInterface"],
	"columns" => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"recordid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics.recordid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "tx_servicemgr_events",	
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"type" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics.type",		
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics.type.I.0", "0"),
					Array("LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics.type.I.1", "1"),
					Array("LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics.type.I.2", "2"),
				),
				"size" => 1,	
				"maxitems" => 1,
			)
		),
		"hit" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_statistics.hit",		
			"config" => Array (
				"type" => "none",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, recordid, type, hit")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_servicemgr_sermons"] = array (
	"ctrl" => $TCA["tx_servicemgr_sermons"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,event,title,file,filedate,playtime,bitrate,album"
	),
	"feInterface" => $TCA["tx_servicemgr_sermons"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_servicemgr_sermons',
				'foreign_table_where' => 'AND tx_servicemgr_sermons.pid=###CURRENT_PID### AND tx_servicemgr_sermons.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "trim",
			)
		),
		"file" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.file",		
			"config" => Array (
				"type"     => "input",
				"size"     => "15",
				"max"      => "255",
				"checkbox" => "",
				"eval"     => "trim",
				"wizards"  => array(
					"_PADDING" => 2,
					"link"     => array(
						"type"         => "popup",
						"title"        => "Link",
						"icon"         => "link_popup.gif",
						"script"       => "browse_links.php?mode=wizard",
						"JSopenParams" => "height=300,width=500,status=0,menubar=0,scrollbars=1"
					)
				)
			)
		),
		"filedate" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.filedate",		
			"config" => Array (
				"type" => "none",
			)
		),
		"playtime" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.playtime",		
			"config" => Array (
				"type" => "none",
			)
		),
		"filesize" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.filesize",		
			"config" => Array (
				"type" => "none",
			)
		),
		"mimetype" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.mimetype",		
			"config" => Array (
				"type" => "none",
			)
		),
		"bitrate" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.bitrate",		
			"config" => Array (
				"type" => "none",
			)
		),
		"album" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:servicemgr/locallang_db.xml:tx_servicemgr_sermons.album",		
			"config" => Array (
				"type" => "none",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, title;;;;2-2-2, file;;;;3-3-3, filedate, playtime, filesize, bitrate, album")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);
?>
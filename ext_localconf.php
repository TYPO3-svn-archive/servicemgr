<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_servicemgr_events=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_servicemgr_series=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_servicemgr_tags=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_servicemgr_teamtype=1
');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_servicemgr_eventpreview = < plugin.tx_servicemgr_eventpreview.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'eventpreview/class.tx_servicemgr_eventpreview.php','_eventpreview','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_servicemgr_pi2 = < plugin.tx_servicemgr_pi2.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_servicemgr_pi2.php','_pi2','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_servicemgr_pi3 = < plugin.tx_servicemgr_pi3.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi3/class.tx_servicemgr_pi3.php','_pi3','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_servicemgr_pi4 = < plugin.tx_servicemgr_pi4.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi4/class.tx_servicemgr_pi4.php','_pi4','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_servicemgr_pi5 = < plugin.tx_servicemgr_pi5.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi5/class.tx_servicemgr_pi5.php','_pi5','list_type',1);
?>
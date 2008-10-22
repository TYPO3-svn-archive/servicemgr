# TYPO3 Extension Manager dump 1.1
#
# Host: localhost    Database: typo3_420_final
#--------------------------------------------------------


#
# Table structure for table "tx_servicemgr_events"
#
CREATE TABLE tx_servicemgr_events (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  sys_language_uid int(11) NOT NULL default '0',
  l18n_parent int(11) NOT NULL default '0',
  l18n_diffsource mediumblob NOT NULL,
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  datetime int(11) NOT NULL default '0',
  subject varchar(255) NOT NULL default '',
  public tinyint(3) NOT NULL default '0',
  series int(11) NOT NULL default '0',
  tags tinytext NOT NULL,
  requiredteams tinytext NOT NULL,
  dutyscheduleopen tinyint(4) NOT NULL default '1',
  documents blob NOT NULL,
  notes text NOT NULL,
  notes_internal text NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid),
);


#
# Table structure for table "tx_servicemgr_series"
#
CREATE TABLE tx_servicemgr_series (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  sys_language_uid int(11) NOT NULL default '0',
  l18n_parent int(11) NOT NULL default '0',
  l18n_diffsource mediumblob NOT NULL,
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  PRIMARY KEY (uid),
  KEY parent (pid),
);


#
# Table structure for table "tx_servicemgr_tags"
#
CREATE TABLE tx_servicemgr_tags (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  sys_language_uid int(11) NOT NULL default '0',
  l18n_parent int(11) NOT NULL default '0',
  l18n_diffsource mediumblob NOT NULL,
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  parrent int(11) NOT NULL default '0',
  PRIMARY KEY (uid),
  KEY parent (pid),
);


#
# Table structure for table "tx_servicemgr_teamtype"
#
CREATE TABLE tx_servicemgr_teamtype (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  sys_language_uid int(11) NOT NULL default '0',
  l18n_parent int(11) NOT NULL default '0',
  l18n_diffsource mediumblob NOT NULL,
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  PRIMARY KEY (uid),
  KEY parent (pid),
);


#
# Table structure for table "tx_servicemgr_dutyschedule"
#
CREATE TABLE tx_servicemgr_dutyschedule (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  sys_language_uid int(11) NOT NULL default '0',
  l18n_parent int(11) NOT NULL default '0',
  l18n_diffsource mediumblob NOT NULL,
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  event int(11) NOT NULL default '0',
  duty blob NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid),
);


#
# Table structure for table "tx_servicemgr_statistics"
#
CREATE TABLE tx_servicemgr_statistics (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  recordid blob NOT NULL,
  type int(11) NOT NULL default '0',
  hit tinytext NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);


#
# Table structure for table "tx_servicemgr_sermons"
#
CREATE TABLE tx_servicemgr_sermons (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL default '0',
  tstamp int(11) NOT NULL default '0',
  crdate int(11) NOT NULL default '0',
  cruser_id int(11) NOT NULL default '0',
  sys_language_uid int(11) NOT NULL default '0',
  l18n_parent int(11) NOT NULL default '0',
  l18n_diffsource mediumblob NOT NULL,
  deleted tinyint(4) NOT NULL default '0',
  hidden tinyint(4) NOT NULL default '0',
  event int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  file varchar(255) default '',
  filedate int(11) default '0',
  playtime int(11) default '0',
  bitrate int(11) default '0',
  album varchar(255) default '',
  filesize int(11) NOT NULL default '0',
  mimetype varchar(255) NOT NULL default '',
  PRIMARY KEY (uid),
  KEY parent (pid),
);


#
# Table structure for table "fe_users"
#
CREATE TABLE fe_users (
  tx_servicemgr_description text NOT NULL,
);


#
# Table structure for table "fe_groups"
#
CREATE TABLE fe_groups (
  tx_servicemgr_category int(11) NOT NULL default '0',
  tx_servicemgr_dutyschedule tinyint(3) NOT NULL default '0',
  tx_servicemgr_leaders blob NOT NULL,
  tx_servicemgr_isteam tinyint(3) NOT NULL default '0',
  tx_servicemgr_image blob NOT NULL,
  tx_servicemgr_asteaminschedule tinyint(3) NOT NULL default '0',
  tx_servicemgr_dsname  varchar(30) NOT NULL default '',
);

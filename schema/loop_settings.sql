CREATE TABLE IF NOT EXISTS /*_*/loop_settings (
  `lset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lset_timestamp` TIMESTAMP NOT NULL,
  `lset_imprintlink` varbinary(255) NOT NULL,
  `lset_privacylink` varbinary(255) NOT NULL,
  `lset_oncampuslink` varbinary(255) NOT NULL,
  `lset_rightstext` varbinary(255) NOT NULL,
  `lset_rightstype` varbinary(255) NOT NULL,
  `lset_rightsurl` varbinary(255) NOT NULL,
  `lset_rightsicon` varbinary(255) NOT NULL,
  `lset_customlogo` varbinary(255) NOT NULL,
  `lset_customlogofilename` varbinary(255) NOT NULL,
  `lset_customlogofilepath` varbinary(255) NOT NULL,
  `lset_languagecode` varbinary(255) NOT NULL,
	`lset_extrafooter` varbinary(255) NOT NULL,
  `lset_facebookicon` varbinary(255) NOT NULL,
  `lset_facebooklink` varbinary(255) NOT NULL,
  `lset_twittericon` varbinary(255) NOT NULL,
  `lset_twitterlink` varbinary(255) NOT NULL,
  `lset_youtubeicon` varbinary(255) NOT NULL,
  `lset_youtubelink` varbinary(255) NOT NULL,
  `lset_githubicon` varbinary(255) NOT NULL,
  `lset_githublink` varbinary(255) NOT NULL,
  `lset_instagramicon` varbinary(255) NOT NULL,
  `lset_instagramlink` varbinary(255) NOT NULL,
  `lset_skinstyle` varbinary(255) NOT NULL,
  `lset_numberingfigures` varbinary(255),
  `lset_numberingformulas` varbinary(255),
  `lset_numberinglistings` varbinary(255),
  `lset_numberingmedia` varbinary(255),
  `lset_numberingtables` varbinary(255),
  `lset_numberingtasks` varbinary(255),
  `lset_numberingtype` varbinary(255),
  PRIMARY KEY (lset_id)
) /*$wgDBTableOptions*/;
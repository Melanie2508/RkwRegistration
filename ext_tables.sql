#
# Table structure for table 'tx_rkwregistration_domain_model_registration'
#
CREATE TABLE tx_rkwregistration_domain_model_registration (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	category varchar(255) DEFAULT '' NOT NULL,
	user int(11) DEFAULT '0' NOT NULL,
	user_sha1 varchar(255) DEFAULT '' NOT NULL,
	token_yes varchar(255) DEFAULT '' NOT NULL,
	token_no varchar(255) DEFAULT '' NOT NULL,
	valid_until int(11) unsigned DEFAULT '0' NOT NULL,
	data longtext NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
);

#
# Table structure for table 'tx_rkwregistration_domain_model_service'
#
CREATE TABLE tx_rkwregistration_domain_model_service (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	user int(11) DEFAULT '0' NOT NULL,
	usergroup int(11) DEFAULT '0' NOT NULL,
	service_sha1 varchar(255) DEFAULT '' NOT NULL,
	token_yes varchar(255) DEFAULT '' NOT NULL,
	token_no varchar(255) DEFAULT '' NOT NULL,
	valid_until int(11) unsigned DEFAULT '0' NOT NULL,
	enabled_by_admin tinyint(1) DEFAULT '0' NOT NULL,


	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),

);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (

	tx_rkwregistration_title int(11) unsigned DEFAULT '0' NOT NULL,
	tx_rkwregistration_gender tinyint(4) DEFAULT '99' NOT NULL,
	tx_rkwregistration_mobile varchar(255) DEFAULT '' NOT NULL,
	tx_rkwregistration_federal_state varchar(255) DEFAULT '' NOT NULL,

    tx_rkwregistration_twitter_id int(11) DEFAULT '0' NOT NULL,
	tx_rkwregistration_twitter_url varchar(255) DEFAULT '' NOT NULL,

    tx_rkwregistration_facebook_id varchar(255) DEFAULT '' NOT NULL,
	tx_rkwregistration_facebook_url varchar(255) DEFAULT '' NOT NULL,

	tx_rkwregistration_xing_url varchar(255) DEFAULT '' NOT NULL,

    tx_rkwregistration_registered_by tinyint(4) DEFAULT '0' NOT NULL,
	tx_rkwregistration_register_remote_ip varchar(255) DEFAULT '' NOT NULL,
	tx_rkwregistration_language_key varchar(255) DEFAULT 'default' NOT NULL,

    tx_rkwregistration_login_error_count tinyint(4) DEFAULT '0' NOT NULL,
    tx_rkwregistration_is_anonymous tinyint(4) DEFAULT '0' NOT NULL,
    tx_rkwregistration_cross_domain_token varchar(255) DEFAULT '' NOT NULL,
    tx_rkwregistration_cross_domain_token_tstamp int(11) DEFAULT '0' NOT NULL,

    tx_rkwregistration_data_protection_status tinyint(4) DEFAULT '0' NOT NULL,

    tx_rkwregistration_privacy varchar(255) DEFAULT '' NOT NULL,

);


CREATE TABLE fe_groups (
	tx_rkwregistration_is_service tinyint(4) unsigned DEFAULT '0' NOT NULL,
	tx_rkwregistration_service_opening_date int(11) unsigned DEFAULT '0' NOT NULL,
	tx_rkwregistration_service_closing_date int(11) unsigned DEFAULT '0' NOT NULL,
    tx_rkwregistration_service_mandatory_fields varchar(255) DEFAULT '' NOT NULL,
	tx_rkwregistration_service_admins int(11) unsigned DEFAULT '0' NOT NULL,
	tx_rkwregistration_service_pid int(11) unsigned DEFAULT '0' NOT NULL,

);


#
# Table structure for table 'tx_rkwregistration_fegroups_beusers_mm'
#
CREATE TABLE tx_rkwregistration_fegroups_beusers_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);



#
# Table structure for table 'tx_rkwregistration_domain_model_privacy'
#
CREATE TABLE tx_rkwregistration_domain_model_privacy (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	frontend_user int(11) DEFAULT '0' NOT NULL,
	registration_user_sha1 varchar(255) DEFAULT '' NOT NULL,
	parent int(11) DEFAULT '0' NOT NULL,
	foreign_table varchar(255) DEFAULT '' NOT NULL,
	foreign_uid int(11) DEFAULT '0' NOT NULL,
	ip_address varchar(255) DEFAULT '' NOT NULL,
	user_agent longtext NOT NULL,
	extension_name varchar(255) DEFAULT '' NOT NULL,
	plugin_name varchar(255) DEFAULT '' NOT NULL,
	controller_name varchar(255) DEFAULT '' NOT NULL,
	action_name varchar(255) DEFAULT '' NOT NULL,
	comment varchar(255) DEFAULT '' NOT NULL,
	server_host varchar(255) DEFAULT '' NOT NULL,
	server_uri varchar(255) DEFAULT '' NOT NULL,
	server_referer_url varchar(255) DEFAULT '' NOT NULL,

	child int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),

);

#
# Table structure for table 'tx_rkwregistration_domain_model_encrypteddata'
#
CREATE TABLE tx_rkwregistration_domain_model_encrypteddata (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	frontend_user int(11) DEFAULT '0' NOT NULL,
    foreign_uid int(11) DEFAULT '0' NOT NULL,
	foreign_table varchar(255) DEFAULT '' NOT NULL,
	foreign_class varchar(255) DEFAULT '' NOT NULL,
	encrypted_data text NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),

);

#
# Table structure for table 'tx_rkwregistration_domain_model_title'
#
CREATE TABLE tx_rkwregistration_domain_model_title (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

    name varchar(255) DEFAULT '' NOT NULL,
    name_long varchar(255) DEFAULT '' NOT NULL,
    name_female varchar(255) DEFAULT '' NOT NULL,
    name_female_long varchar(255) DEFAULT '' NOT NULL,
	is_title_after tinyint(4) unsigned DEFAULT '0' NOT NULL,
	is_included_in_salutation tinyint(4) unsigned DEFAULT '0' NOT NULL,
	is_checked tinyint(4) unsigned DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

    sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY parent (pid),
);

#
# Table structure for table 'tx_rkwregistration_title_record_mm'
#
CREATE TABLE tx_rkwregistration_title_record_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,

	KEY uid_local_foreign (uid_local,uid_foreign)
);

#
# Table structure for table 'tx_rkwregistration_domain_model_shippingaddress'
#
CREATE TABLE tx_rkwregistration_domain_model_shippingaddress (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

    frontend_user int(11) unsigned DEFAULT '0' NOT NULL,
	gender tinyint(4) DEFAULT '0' NOT NULL,
	title int(11) DEFAULT '0' NOT NULL,
	first_name varchar(255) DEFAULT '' NOT NULL,
	last_name varchar(255) DEFAULT '' NOT NULL,
	company varchar(255) DEFAULT '' NOT NULL,
	address varchar(255) DEFAULT '' NOT NULL,
	zip varchar(255) DEFAULT '' NOT NULL,
	city varchar(255) DEFAULT '' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	status tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),

);

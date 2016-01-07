#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	gender varchar(1) DEFAULT '' NOT NULL,
	tx_odsajaxmailsubscription_rid varchar(8) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'tt_address'
#
CREATE TABLE tt_address (
	tx_odsajaxmailsubscription_rid varchar(8) DEFAULT '' NOT NULL,
);

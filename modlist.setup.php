<?php
/* ====================
Copyright (c) 2008-2009, Vladimir Sibirov.
All rights reserved. Distributed under BSD License.

[BEGIN_SED_EXTPLUGIN]
Code=modlist
Name=Moderators List
Description=Manage your forum moderators
Version=2.0.2
Date=2010-jan-27
Author=Trustmaster
Copyright=(c) Vladimir Sibirov, 2008-2010
Notes=Don not forget to sync sections and groups right after install
SQL=
Auth_guests=R
Lock_guests=W12345A
Auth_members=R
Lock_members=W12345A
[END_SED_EXTPLUGIN]

[BEGIN_SED_EXTPLUGIN_CONFIG]
sections=01:radio::1:Enable in sections?
topics=02:radio::1:Enable in topics?
sep=03:string:: :Name delimiter (default is space)
base_gid=04:string::4:Base group ID (a template for all moderator groups)
base_level=05:string::30:Group level
base_title=06:string::Moderators of:Group title prefix
[END_SED_EXTPLUGIN_CONFIG]
==================== */
if ( !defined('SED_CODE') ) { die("Wrong URL."); }

$db_modlist = 'sed_modlist';

if($action == 'install')
{
	$sql = "CREATE TABLE IF NOT EXISTS $db_modlist (
		ml_id INT NOT NULL AUTO_INCREMENT,
		ml_uid INT NOT NULL,
		ml_gid INT NOT NULL,
		ml_sid INT NOT NULL,
		PRIMARY KEY(ml_id)
	) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	sed_sql_query($sql);
}
elseif($action == 'uninstall')
{
	sed_sql_query("DROP TABLE IF EXISTS $db_modlist");
}
?>
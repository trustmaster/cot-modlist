<?php
/* ====================
Copyright (c) 2008-2009, Vladimir Sibirov.
All rights reserved. Distributed under BSD License.

[BEGIN_SED_EXTPLUGIN]
Code=modlist
Part=topics
File=modlist.topics
Hooks=forums.topics.tags
Tags=forums.topics.tpl:{FORUMS_TOPICS_MODLIST}
Order=10
[END_SED_EXTPLUGIN]
==================== */
if (!defined('SED_CODE')) { die('Wrong URL.'); }

if($cfg['plugin']['modlist']['topics'])
{
	require_once($cfg['plugins_dir'].'/modlist/inc/config.php');

	$ml = array();
	$ml_sql = sed_sql_query("SELECT u.user_name, u.user_id
		FROM $db_modlist AS m LEFT JOIN $db_users AS u ON m.ml_uid = u.user_id
		WHERE m.ml_sid = $s");
	while($ml_row = sed_sql_fetchassoc($ml_sql))
	{
		$ml[] = sed_build_user($ml_row['user_id'], $ml_row['user_name']);
	}
	$t->assign('FORUMS_TOPICS_MODLIST', implode($cfg['plugin']['modlist']['sep'], $ml));
}
?>
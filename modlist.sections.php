<?php
/* ====================
Copyright (c) 2008-2009, Vladimir Sibirov.
All rights reserved. Distributed under BSD License.

[BEGIN_SED_EXTPLUGIN]
Code=modlist
Part=sections
File=modlist.sections
Hooks=forums.sections.loop
Tags=forums.sections.tpl:{FORUMS_SECTIONS_ROW_MODLIST}
Order=10
[END_SED_EXTPLUGIN]
==================== */
if (!defined('SED_CODE')) { die('Wrong URL.'); }

if($cfg['plugin']['modlist']['sections'])
{
	require_once($cfg['plugins_dir'].'/modlist/inc/config.php');
	$ml = array();
	$ml_sql = sed_sql_query("SELECT u.user_name, u.user_id
		FROM $db_modlist AS m LEFT JOIN $db_users AS u ON m.ml_uid = u.user_id
		WHERE m.ml_sid = {$fsn['fs_id']}");

	while($ml_row = sed_sql_fetchassoc($ml_sql))
	{
		$ml[] = sed_build_user($ml_row['user_id'], $ml_row['user_name']);
	}
	if(count($ml) > 0)
	{
		$mlist = $ml[0];
		for($i = 1; $i < count($ml); $i++)
		{
			$mlist .= ', ';
			if($i % 2 == 0) $mlist .= '<br />';
			$mlist .= $ml[$i];
		}
	}
	else $mlist = '';
	$t->assign('FORUMS_SECTIONS_ROW_MODLIST', $mlist);
}

?>
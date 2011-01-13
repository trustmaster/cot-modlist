<?php
/* ====================
Copyright (c) 2008-2009, Vladimir Sibirov.
All rights reserved. Distributed under BSD License.

[BEGIN_SED_EXTPLUGIN]
Code=modlist
Part=admin
File=modlist.admin
Hooks=tools
Tags=
Order=10
[END_SED_EXTPLUGIN]
==================== */

if (!defined('SED_CODE')) { die('Wrong URL.'); }

$act = sed_import('act', 'G', 'ALP');
$sid = sed_import('sid', 'G', 'INT');
$mid = sed_import('mid', 'G', 'INT');
$add = sed_import('add', 'G', 'BOL');
$del = sed_import('del', 'G', 'BOL');
$uname = sed_import('uname', 'G', 'STX');

require_once($cfg['plugins_dir'].'/modlist/inc/config.php');

/**
 * Adds a new moderators group.
 *
 * @param int $fs_id Forum section id
 * @return bool
 */
function ml_add_group($fs_id)
{
	global $cfg, $db_auth, $db_groups;
	$ntitle = $cfg['plugin']['modlist']['base_title'].' '.$fs_id;
	$ndesc = 'Moderators in forums section #'.$fs_id;
	$nicon = '';
	$nalias = 'moderators_fs_'.$fs_id;
	$nlevel = $cfg['plugin']['modlist']['base_level'];
	$nmaxsingle = 0;
	$nmaxtotal = 0;
	$ncopyrightsfrom = $cfg['plugin']['modlist']['base_gid'];
	$ndisabled = false;
	$nhidden = true;
	$sql = sed_sql_query("INSERT INTO $db_groups (grp_alias, grp_level, grp_disabled, grp_hidden, grp_title, grp_desc, grp_icon, grp_pfs_maxfile, grp_pfs_maxtotal, grp_ownerid) VALUES ('".sed_sql_prep($nalias)."', ".(int)$nlevel.", ".(int)$ndisabled.", ".(int)$nhidden.", '".sed_sql_prep($ntitle)."', '".sed_sql_prep($ndesc)."', '".sed_sql_prep($nicon)."', ".(int)$nmaxsingle.", ".(int)$nmaxtotal.", ".(int)$usr['id'].")");
	$grp_id = sed_sql_insertid();
	$sql = sed_sql_query("SELECT auth_code, auth_option, auth_rights FROM $db_auth WHERE auth_groupid='".$ncopyrightsfrom."' order by auth_code ASC, auth_option ASC");
	while ($row = sed_sql_fetcharray($sql))
	{
		if($row['auth_code'] == 'forums' && $row['auth_option'] == $fs_id && ($row['auth_rights'] & 128 != 128))
			$row['auth_rights'] += 128;
		$sql1 = sed_sql_query("INSERT into $db_auth (auth_groupid, auth_code, auth_option, auth_rights, auth_rights_lock, auth_setbyuserid) VALUES (".(int)$grp_id.", '".$row['auth_code']."', '".$row['auth_option']."', ".(int)$row['auth_rights'].", 0, ".(int)$usr['id'].")");
	}
	return true;
}

/**
 * Removes a group and all associated entries.
 *
 * @param int $group_id Group id
 * @return int
 */
function ml_remove_group($group_id)
{
	global $cfg, $db_auth, $db_groups, $db_groups_users, $db_modlist;
	$count = 0;
	sed_sql_query("DELETE FROM $db_groups WHERE grp_id=$group_id");
	$count += sed_sql_affectedrows();
	sed_sql_query("DELETE FROM $db_auth WHERE auth_groupid=$group_id");
	$count += sed_sql_affectedrows();
	sed_sql_query("DELETE FROM $db_groups_users WHERE gru_groupid=$group_id");
	$count += sed_sql_affectedrows();
	sed_sql_query("DELETE FROM $db_modlist WHERE ml_gid=$group_id");
	$count += sed_sql_affectedrows();
	return $count;
}


if($act == 'sync')
{

	// Get all sections with no sutable groups assigned and add the groups
	$condition = "LEFT JOIN $db_groups AS g ON CONCAT('moderators_fs_', CAST(s.fs_id AS CHAR)) = g.grp_alias
	WHERE g.grp_alias IS NULL";
	$sql = sed_sql_query("SELECT s.fs_id, s.fs_title FROM $db_forum_sections AS s $condition");
	$count = sed_sql_numrows($sql);
	while($row = sed_sql_fetcharray($sql))
	{
		ml_add_group($row['fs_id']);
	}
	$plugin_body .= "$count {$L['ml_groups_added']}.<br />";
	// Get all moderator groups with no suitable sections and remove them
	$condition = "LEFT JOIN $db_forum_sections ON $db_modlist.ml_sid = $db_forum_sections.fs_id
	WHERE $db_forum_sections.fs_id IS NULL";
	$sql = sed_sql_query("SELECT ml_gid FROM $db_modlist $condition");
	$count = sed_sql_numrows($sql);
	$count2 = 0;
	while($row = sed_sql_fetcharray($sql))
	{
		$count2 += ml_remove_group($row['ml_gid']);
	}
	$plugin_body .= "$count {$L['ml_groups_removed']} $count2.<br />";
	// Done, clear the cache
	sed_auth_clear('all');
	sed_cache_clear('sed_groups');
	$plugin_body .= '<a href="'.sed_url('admin', 'm=tools&p=modlist').'">'.$L['ml_back'].'</a>';
	
}
elseif($act == 'sectlist')
{
	// Show all sections
	$plugin_body = '<table class="cells">';
	$sql = sed_sql_query("SELECT fs_id, fs_title, fs_category FROM $db_forum_sections");
	while($row = sed_sql_fetcharray($sql))
		$plugin_body .= '<tr><td><a href="'.sed_url('admin', 'm=tools&p=modlist&act=sectview&sid='.$row['fs_id']).'">'.$row['fs_category'].' / '.$row['fs_title'].'</a></td></tr>';
	$plugin_body .= '</table><a href="'.sed_url('admin', 'm=tools&p=modlist').'">'.$L['ml_back'].'</a>';
}
elseif($act == 'sectview' && $sid > 0)
{
	// Add a new moderator if passed
	if($add && !empty($uname))
	{
		$uid = @sed_sql_result(sed_sql_query("SELECT user_id FROM $db_users WHERE user_name = '$uname'"), 0, 0);
		$gid = @sed_sql_result(sed_sql_query("SELECT grp_id FROM $db_groups WHERE grp_alias = 'moderators_fs_$sid'"), 0, 0);
		if($uid > 0 && $gid > 0)
		{
			// Add into it if not already in
			if(sed_sql_result(sed_sql_query("SELECT COUNT(*) FROM $db_groups_users WHERE gru_groupid = $gid AND gru_userid = $uid"), 0, 0) == 0)
			{
				sed_sql_query("INSERT INTO $db_groups_users (gru_userid, gru_groupid) VALUES($uid, $gid)");
			}
			if(sed_sql_result(sed_sql_query("SELECT COUNT(*) FROM $db_modlist WHERE ml_gid = $gid AND ml_uid = $uid"), 0, 0) == 0)
			{
				sed_sql_query("INSERT INTO $db_modlist (ml_uid, ml_gid, ml_sid) VALUES ($uid, $gid, $sid)");
			}
		}
		else $plugin_body .= $L['ml_err_noitems'].'<br />';
	}
	// Remove a moderator if passed
	if($del && $mid > 0)
	{
		$row = sed_sql_fetcharray(sed_sql_query("SELECT * FROM $db_modlist WHERE ml_id = $mid"));
		sed_sql_query("DELETE FROM $db_groups_users WHERE gru_userid = {$row['ml_uid']} AND gru_groupid = {$row['ml_gid']}");
		sed_sql_query("DELETE FROM $db_modlist WHERE ml_id = $mid");
	}
	// Show moderators assigned to a section
	$plugin_body .= '<table class="cells"><tr><td class="coltop">'.$L['ml_item'].'</td><td class="coltop">'.$L['ml_delete'].'</td></tr>';
	$sql = sed_sql_query("SELECT u.user_id, u.user_name, m.ml_id, m.ml_sid FROM $db_modlist AS m
	LEFT JOIN $db_users AS u ON m.ml_uid = u.user_id WHERE m.ml_sid = $sid");
	while($row = sed_sql_fetcharray($sql))
	{
		$user = sed_build_user($row['user_id'], $row['user_name']);
		$plugin_body .= '<tr><td>'.$user.'</td><td><a href="'.sed_url('admin', 'm=tools&p=modlist&act=sectview&sid='.$sid.'&del=1&mid='.$row['ml_id']).'" onclick="return confirm(\''.$L['ml_ensure'].'\')">'.$L['ml_delete'].'</a></td></tr>';
	}
	$plugin_body .= '</table><a href="'.sed_url('admin', 'm=tools&p=modlist&act=sectlist').'">'.$L['ml_back'].'</a>';
	$plugin_body .= '<h4>'.$L['ml_assign'].'</h4>';
	$plugin_body .= '<form action="'.sed_url('admin').'" method="get">
	<input type="hidden" name="m" value="tools" /><input type="hidden" name="p" value="modlist" /><input type="hidden" name="act" value="sectview" />
	<input type="hidden" name="sid" value="'.$sid.'" /><input type="hidden" name="add" value="1" />'.$L['ml_uname'].' <input type="text" name="uname" /> <input type="submit" value="'.$L['ml_add'].'" /></form>';
}
else
{
	$plugin_body = '<ul>
<li><a href="'.sed_url('admin', 'm=tools&p=modlist&act=sync').'">'.$L['ml_sync'].'</a></li>
<li><a href="'.sed_url('admin', 'm=tools&p=modlist&act=sectlist').'"><strong>'.$L['ml_section_list'].'</strong></a></li>
</ul>';
}
?>
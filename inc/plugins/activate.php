<?php
/**
 * Activate Users from Mod CP
 * Copyright 2010 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Neat trick for caching our custom template(s)
if(my_strpos($_SERVER['PHP_SELF'], 'modcp.php'))
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'modcp_nav_activate,modcp_activate,modcp_activate_none,modcp_activate_row';
}

// Tell MyBB when to run the hooks
$plugins->add_hook("modcp_nav", "activate_nav");
$plugins->add_hook("modcp_start", "activate_run");

// The information that shows up on the plugin manager
function activate_info()
{
	global $lang;
	$lang->load("activate", true);

	return array(
		"name"				=> $lang->activate_info_name,
		"description"		=> $lang->activate_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function activate_activate()
{
	global $db;
	$insert_array = array(
		'title'		=> 'modcp_nav_activate',
		'template'	=> $db->escape_string('<tr><td class="trow1 smalltext"><a href="modcp.php?action=activate" class="modcp_nav_item" style="background:url(\'images/activate.png\') no-repeat left center;">{$lang->mcp_nav_activate}</a></td></tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'modcp_activate',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->activate_users}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="modcp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="6"><strong>{$lang->activate_users}</strong></td>
</tr>
<tr>
<td class="tcat" align="center" width="20%"><span class="smalltext"><strong>{$lang->username}</strong></span></td>
<td class="tcat" align="center" width="20%"><span class="smalltext"><strong>{$lang->dateline}</strong></span></td>
<td class="tcat" align="center" width="15%"><span class="smalltext"><strong>{$lang->ipaddress}</strong></span></td>
<td class="tcat" align="center" width="20%"><span class="smalltext"><strong>{$lang->email_address}</strong></span></td>
<td class="tcat" align="center" width="25%"><span class="smalltext"><strong>{$lang->type}</strong></span></td>
<td class="tcat" align="center" width="1"><input name="allbox" title="Select All" type="checkbox" class="checkbox checkall" value="1" /></td>
</tr>
{$activate}
{$activatepages}
</table>
<br />
<div align="center">
<input type="hidden" name="action" value="do_activate" />
<input type="submit" class="button" name="activate" value="{$lang->activate_users}" />
<input type="submit" class="button" name="delete" value="{$lang->input_delete}" />
</div>
</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'modcp_activate_none',
		'template'	=> $db->escape_string('<tr>
<td class="trow1" colspan="6" align="center">{$lang->no_awaiting_activation}</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'modcp_activate_row',
		'template'	=> $db->escape_string('<tr>
<td class="{$alt_bg}" align="center">{$user[\'username\']}</td>
<td class="{$alt_bg}" align="center">{$dateline}</td>
<td class="{$alt_bg}" align="center">{$user[\'regip\']}</td>
<td class="{$alt_bg}" align="center">{$user[\'email\']}</td>
<td class="{$alt_bg}" align="center">{$user[\'type\']}</td>
<td class="{$alt_bg}" align="center"><input type="checkbox" class="checkbox" name="check[{$user[\'uid\']}]" value="{$user[\'uid\']}" /></td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_ipsearch}')."#i", '{$nav_ipsearch}{$nav_activate}');
}

// This function runs when the plugin is deactivated.
function activate_deactivate()
{
	global $db;
	$db->delete_query("templates", "title IN('modcp_nav_activate','modcp_activate','modcp_activate_none','modcp_activate_row')");

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_activate}')."#i", '', 0);
}

// Mod CP nav menu
function activate_nav()
{
	global $mybb, $lang, $templates, $nav_activate;
	$lang->load("activate");

	if($mybb->usergroup['issupermod'] == 1 || $mybb->usergroup['cancp'] == 1)
	{
		eval("\$nav_activate = \"".$templates->get("modcp_nav_activate")."\";");
	}
}

// Mod CP activation page
function activate_run()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $header, $footer, $modcp_nav, $multipage;
	$lang->load("activate");

	if($mybb->input['action'] == "do_activate")
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		if($mybb->usergroup['issupermod'] == 0 && $mybb->usergroup['cancp'] == 0)
		{
			error_no_permission();
		}

		$mybb->input['check'] = $mybb->get_input('check', 2);
		if(empty($mybb->input['check']))
		{
			error($lang->no_users_selected);
		}

		// Clean input - only accept integers thanks!
		$mybb->input['check'] = array_map('intval', $mybb->input['check']);
		$uids = implode(", ", $mybb->input['check']);

		if($mybb->input['activate']) // activate users
		{
			$updated_accounts = array(
				"usergroup" => "2",
				"coppauser" => "0"
			);
			$db->update_query("users", $updated_accounts, "uid IN ({$uids})");
			$db->delete_query("awaitingactivation", "uid IN ({$uids})");
			$message = $lang->redirect_users_activated;

			$lang->mod_activate = $lang->sprintf($lang->mod_activate, $uids);
			log_moderator_action(array("users" => $uids), $lang->mod_activate);
		}

		if($mybb->input['delete']) // delete users
		{
			require_once MYBB_ROOT.'inc/datahandlers/user.php';
			$userhandler = new UserDataHandler('delete');

			$deleted = $userhandler->delete_user($uids);

			$message = $lang->redirect_users_deleted;

			$lang->mod_delete = $lang->sprintf($lang->mod_delete, $uids);
			log_moderator_action(array("users" => $uids), $lang->mod_delete);
		}

		redirect("modcp.php?action=activate", $message);
	}

	if($mybb->input['action'] == "activate")
	{
		add_breadcrumb($lang->mcp_nav_home, "modcp.php");
		add_breadcrumb($lang->mcp_nav_activate, "modcp.php?action=activate");

		if($mybb->usergroup['issupermod'] == 0 && $mybb->usergroup['cancp'] == 0)
		{
			error_no_permission();
		}

		if(!(int)$mybb->settings['threadsperpage'])
		{
			$mybb->settings['threadsperpage'] = 20;
		}

		// Figure out if we need to display multiple pages.
		$perpage = $mybb->get_input('perpage', 1);
		if(!$perpage || $perpage <= 0)
		{
			$perpage = $mybb->settings['threadsperpage'];
		}

		$query = $db->simple_select("users", "COUNT(uid) AS count", "usergroup ='5'");
		$result = $db->fetch_field($query, "count");

		// Figure out if we need to display multiple pages.
		if($mybb->input['page'] != "last")
		{
			$page = $mybb->get_input('page', 1);
		}

		$pages = $result / $perpage;
		$pages = ceil($pages);

		if($mybb->input['page'] == "last")
		{
			$page = $pages;
		}

		if($page > $pages || $page <= 0)
		{
			$page = 1;
		}
		if($page)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$multipage = multipage($result, $perpage, $page, "modcp.php?action=activate");
		if($result > $perpage)
		{
			eval("\$activatepages = \"".$templates->get("modcp_reports_multipage")."\";");
		}

		$query2 = $db->query("
			SELECT u.*, u.username AS user_name, a.type AS reg_type, u.coppauser AS coppa
			FROM ".TABLE_PREFIX."users u
			LEFT JOIN ".TABLE_PREFIX."awaitingactivation a ON (a.uid=u.uid)
			WHERE u.usergroup='5'
			ORDER BY u.regdate DESC
			LIMIT {$start}, {$perpage}
		");
		while($user = $db->fetch_array($query2))
		{
			$alt_bg = alt_trow();
			$user['username'] = build_profile_link($user['user_name'], $user['uid']);
			$dateline = my_date($mybb->settings['dateformat'], $user['regdate']).", ".my_date($mybb->settings['timeformat'], $user['regdate']);
			if($user['reg_type'] == r)
			{
				$user['type'] = $lang->email_activation;
			}
			else if($user['coppa'] == 1)
			{
				$user['type'] = $lang->admin_activation_coppa;
			}
			else
			{
				$user['type'] = $lang->administrator_activation;
			}

			$user['regip'] = my_inet_ntop($db->unescape_binary($user['regip']));
			eval("\$activate .= \"".$templates->get("modcp_activate_row")."\";");
		}

		if(!$activate)
		{
			eval("\$activate = \"".$templates->get("modcp_activate_none")."\";");
		}

		eval("\$modactivate = \"".$templates->get("modcp_activate")."\";");
		output_page($modactivate);
	}
}

?>
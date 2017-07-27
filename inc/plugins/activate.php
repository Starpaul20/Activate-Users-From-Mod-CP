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
if(THIS_SCRIPT == 'modcp.php')
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'modcp_nav_activate,modcp_activate,modcp_activate_actions,modcp_activate_none,modcp_activate_row';
}

// Tell MyBB when to run the hooks
$plugins->add_hook("modcp_nav", "activate_nav");
$plugins->add_hook("modcp_start", "activate_run");

$plugins->add_hook("admin_formcontainer_output_row", "activate_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "activate_usergroup_permission_commit");

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
		"version"			=> "1.2",
		"codename"			=> "activate",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function activate_activate()
{
	global $db, $cache;

	if(!$db->field_exists("canactivateusers", "usergroups"))
	{
		switch($db->type)
		{
			case "pgsql":
				$db->add_column("usergroups", "canactivateusers", "smallint NOT NULL default '1'");
				break;
			default:
				$db->add_column("usergroups", "canactivateusers", "tinyint(1) NOT NULL default '1'");
				break;
		}

		$cache->update_usergroups();
	}

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
					{$activate_delete_actions}
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
		'title'		=> 'modcp_activate_actions',
		'template'	=> $db->escape_string('<input type="submit" class="button" name="activate" value="{$lang->activate_users}" onclick="return confirm(\'{$lang->confirm_activate_users}\');" />
<input type="submit" class="button" name="delete" value="{$lang->input_delete}" onclick="return confirm(\'{$lang->confirm_delete_users}\');" />'),
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
	global $db, $cache;

	if($db->field_exists("canactivateusers", "usergroups"))
	{
		$db->drop_column("usergroups", "canactivateusers");
	}

	$cache->update_usergroups();

	$db->delete_query("templates", "title IN('modcp_nav_activate','modcp_activate','modcp_activate_actions','modcp_activate_none','modcp_activate_row')");

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_activate}')."#i", '', 0);
}

// Mod CP nav menu
function activate_nav()
{
	global $mybb, $lang, $templates, $nav_activate;
	$lang->load("activate");

	if($mybb->usergroup['canactivateusers'] == 1)
	{
		eval("\$nav_activate = \"".$templates->get("modcp_nav_activate")."\";");
	}
}

// Mod CP activation page
function activate_run()
{
	global $db, $mybb, $lang, $templates, $theme, $cache, $headerinclude, $header, $footer, $modcp_nav, $multipage, $activate_delete_actions;
	$lang->load("activate");

	if($mybb->input['action'] == "do_activate")
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		if($mybb->usergroup['canactivateusers'] == 0)
		{
			error_no_permission();
		}

		// Clean input - only accept integers thanks!
		$mybb->input['check'] = $mybb->get_input('check', MyBB::INPUT_ARRAY);
		if(empty($mybb->input['check']))
		{
			error($lang->no_users_selected);
		}

		$mybb->input['check'] = array_map('intval', $mybb->input['check']);
		$user_ids = implode(", ", $mybb->input['check']);

		if(empty($user_ids))
		{
			error($lang->no_users_selected);
		}

		$users_to_delete = array();
		if($mybb->get_input('delete')) // Delete selected user(s)
		{
			require_once MYBB_ROOT.'inc/datahandlers/user.php';
			$userhandler = new UserDataHandler('delete');

			$query = $db->simple_select("users", "uid, usergroup", "uid IN ({$user_ids})");
			while($user = $db->fetch_array($query))
			{
				if($user['usergroup'] == 5)
				{
					$users_to_delete[] = (int)$user['uid'];
				}
			}

			if(!empty($users_to_delete))
			{
				$userhandler->delete_user($users_to_delete, 1);
			}

			$message = $lang->redirect_users_deleted;

			$lang->mod_delete = $lang->sprintf($lang->mod_delete, $user_ids);
			log_moderator_action(array("users" => $user_ids), $lang->mod_delete);
		}

		if($mybb->get_input('activate'))  // Activate selected user(s)
		{
			$query = $db->simple_select("users", "uid, username, email, usergroup, coppauser", "uid IN ({$user_ids})");
			while($user = $db->fetch_array($query))
			{
				if($user['coppauser'])
				{
					$updated_user = array(
						"coppauser" => 0
					);
				}
				else
				{
					$db->delete_query("awaitingactivation", "uid='{$user['uid']}'");
				}

				// Move out of awaiting activation if they're in it.
				if($user['usergroup'] == 5)
				{
					$updated_user['usergroup'] = 2;
				}

				$db->update_query("users", $updated_user, "uid='{$user['uid']}'");

				$emailmessage = $lang->sprintf($lang->email_adminactivateaccount, $user['username'], $mybb->settings['bbname'], $mybb->settings['bburl']);
				my_mail($user['email'], $lang->sprintf($lang->emailsubject_activateaccount, $mybb->settings['bbname']), $emailmessage);
			}

			$message = $lang->redirect_users_activated;

			$lang->mod_activate = $lang->sprintf($lang->mod_activate, $user_ids);
			log_moderator_action(array("users" => $user_ids), $lang->mod_activate);
		}

		$cache->update_awaitingactivation();
		redirect("modcp.php?action=activate", $message);
	}

	if($mybb->input['action'] == "activate")
	{
		add_breadcrumb($lang->mcp_nav_home, "modcp.php");
		add_breadcrumb($lang->mcp_nav_activate, "modcp.php?action=activate");

		if($mybb->usergroup['canactivateusers'] == 0)
		{
			error_no_permission();
		}

		if(!$mybb->settings['threadsperpage'] || (int)$mybb->settings['threadsperpage'] < 1)
		{
			$mybb->settings['threadsperpage'] = 20;
		}

		// Figure out if we need to display multiple pages.
		$perpage = $mybb->get_input('perpage', MyBB::INPUT_INT);
		if(!$perpage || $perpage <= 0)
		{
			$perpage = $mybb->settings['threadsperpage'];
		}

		$query = $db->simple_select("users", "COUNT(uid) AS count", "usergroup ='5'");
		$result = $db->fetch_field($query, "count");

		// Figure out if we need to display multiple pages.
		if($mybb->input['page'] != "last")
		{
			$page = $mybb->get_input('page', MyBB::INPUT_INT);
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
			SELECT u.uid, u.username, u.regdate, u.regip, u.email, u.coppauser, a.type AS reg_type, a.validated
			FROM ".TABLE_PREFIX."users u
			LEFT JOIN ".TABLE_PREFIX."awaitingactivation a ON (a.uid=u.uid)
			WHERE u.usergroup='5'
			ORDER BY u.regdate DESC
			LIMIT {$start}, {$perpage}
		");
		while($user = $db->fetch_array($query2))
		{
			$alt_bg = alt_trow();

			$user['username'] = htmlspecialchars_uni($user['username']);
			$user['username'] = build_profile_link($user['username'], $user['uid']);
			$dateline = my_date('relative', $user['regdate']);

			if($user['reg_type'] == 'r' || $user['reg_type'] == 'b' && $user['validated'] == 0)
			{
				$user['type'] = $lang->email_activation;
			}
			elseif($user['coppauser'] == 1)
			{
				$user['type'] = $lang->admin_activation_coppa;
			}
			else
			{
				$user['type'] = $lang->administrator_activation;
			}

			if(empty($user['regip']))
			{
				$user['regip'] = $lang->na;
			}
			else
			{
				$user['regip'] = my_inet_ntop($db->unescape_binary($user['regip']));
			}

			eval("\$activate .= \"".$templates->get("modcp_activate_row")."\";");
		}

		$activate_delete_actions = '';
		if(!empty($activate))
		{
			eval("\$activate_delete_actions = \"".$templates->get("modcp_activate_actions")."\";");
		}

		if(!$activate)
		{
			eval("\$activate = \"".$templates->get("modcp_activate_none")."\";");
		}

		eval("\$modactivate = \"".$templates->get("modcp_activate")."\";");
		output_page($modactivate);
	}
}

// Admin CP permission control
function activate_usergroup_permission($above)
{
	global $mybb, $lang, $form;
	$lang->load("activate", true);

	if($above['title'] == $lang->user_options && $lang->user_options)
	{
		$above['content'] .= "<div class=\"group_settings_bit\">".$form->generate_check_box("canactivateusers", 1, $lang->can_activate_users, array("checked" => $mybb->input['canactivateusers']))."</div>";
	}

	return $above;
}

function activate_usergroup_permission_commit()
{
	global $mybb, $updated_group;
	$updated_group['canactivateusers'] = $mybb->get_input('canactivateusers', MyBB::INPUT_INT);
}

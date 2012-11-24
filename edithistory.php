<?php
/**
 * Edit History Log
 * Copyright 2010 Starpaul20
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'edithistory.php');

$templatelist = "edithistory,edithistory_nohistory,edithistory_item,edithistory_comparison,multipage_page_current,multipage_page,multipage_nextpage,multipage_prevpage,multipage";

require_once "./global.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("edithistory");

// Get post info
$pid = intval($mybb->input['pid']);
$post = get_post($pid);
$post['subject'] = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

// Invalid post
if(!$post['pid'] && $mybb->input['action'] != "viewfull")
{
	error($lang->error_invalidpost);
}

$fid = $post['fid'];

// Determine who can see the edit histories
if($mybb->settings['editmodvisibility'] == "2" && $mybb->usergroup['cancp'] != 1)
{
	error_no_permission();
}
else if($mybb->settings['editmodvisibility'] == "1" && ($mybb->usergroup['issupermod'] != 1 && $mybb->usergroup['cancp'] != 1))
{
	error_no_permission();
}
else if(!is_moderator($fid, "caneditposts"))
{
	error_no_permission();
}

if($fid > 0)
{
	$forum = get_forum($fid);

	if(!$forum)
	{
		error($lang->error_invalidforum);
	}

	// Make navigation
	build_forum_breadcrumb($forum['fid']);

	// Permissions
	$forumpermissions = forum_permissions($forum['fid']);

	if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
	{
		error_no_permission();
	}
	
	// Check if this forum is password protected and we have a valid password
	check_forum_password($forum['fid']);
}

// Make navigation
add_breadcrumb($post['subject'], get_thread_link($post['tid']));
add_breadcrumb($lang->nav_edithistory, "edithistory.php?pid={$pid}");

// Comparing old/current post
if($mybb->input['action'] == "compare")
{
	add_breadcrumb($lang->nav_compareposts, "edithistory.php?action=compare&pid={$pid}");

	$query = $db->simple_select("edithistory", "*", "eid='".intval($mybb->input['eid'])."'");
	$editlog = $db->fetch_array($query);

	if(!$editlog['eid'])
	{
		error($lang->error_no_log);
	}

	if($editlog['pid'] != $pid)
	{
		error($lang->error_cannot_compare_other_posts);
	}

	$lang->edit_history = $lang->sprintf($lang->edit_history, $post['subject']);
	$dateline = my_date($mybb->settings['dateformat'], $editlog['dateline']).", ".my_date($mybb->settings['timeformat'], $editlog['dateline']);
	$lang->edit_as_of = $lang->sprintf($lang->edit_as_of, $dateline);

	require_once MYBB_ROOT."inc/3rdparty/diff/Diff.php";        
	require_once MYBB_ROOT."inc/3rdparty/diff/Diff/Renderer/inline.php";

	$message1 = explode("\n", $editlog['originaltext']);
	$message2 = explode("\n", $post['message']);

	$diff = new Text_Diff('auto', array($message1, $message2));
	$renderer = new Text_Diff_Renderer_inline();

	if($editlog['originaltext'] == $post['message'])
	{
		$comparison = $lang->post_same;
	}
	else
	$comparison = $renderer->render($diff);

	$post['message'] = htmlspecialchars_uni($post['message']);

	eval("\$postcomparison = \"".$templates->get("edithistory_comparison")."\";");
	output_page($postcomparison);
}

// Viewing full text
if($mybb->input['action'] == "viewfull")
{
	$query = $db->query("
		SELECT e.*, u.username AS user_name
		FROM ".TABLE_PREFIX."edithistory e
		LEFT JOIN ".TABLE_PREFIX."users u ON (e.uid=u.uid)
		WHERE e.eid='".intval($mybb->input['eid'])."'
	");
	$viewfulledit = $db->fetch_array($query);

	if(!$viewfulledit['eid'])
	{
		error($lang->error_no_log);
	}

	$post = get_post($viewfulledit['pid']);
	$forum = get_forum($post['fid']);

	// Parse the post
	$fulltext_parser = array(
		"allow_html" => $forum['allowhtml'],
		"allow_mycode" => $forum['allowmycode'],
		"allow_smilies" => $forum['allowsmilies'],
		"allow_imgcode" => $forum['allowimgcode'],
		"allow_videocode" => $forum['allowvideocode'],
		"filter_badwords" => 1
	);

	if(!$viewfulledit['reason'])
	{
		$viewfulledit['reason'] = $lang->na;
	}
	else
	$viewfulledit['reason'] = htmlspecialchars_uni($viewfulledit['reason']);

	$originaltext = $parser->parse_message($viewfulledit['originaltext'], $fulltext_parser);
	$dateline = my_date($mybb->settings['dateformat'], $viewfulledit['dateline']).", ".my_date($mybb->settings['timeformat'], $viewfulledit['dateline']);
	$viewfulledit['username'] = build_profile_link($viewfulledit['user_name'], $viewfulledit['uid']);

	eval("\$viewfull = \"".$templates->get("edithistory_viewfull")."\";");
	output_page($viewfull);
}

// Show the edit history for this post.
if(!$mybb->input['action'])
{
	$lang->edit_history = $lang->sprintf($lang->edit_history, htmlspecialchars_uni($post['subject']));

	// Get edit history
	$edit_history = '';

	if(!$mybb->settings['editsperpages'])
	{
		$mybb->settings['editsperpages'] = 10;
	}

	// Figure out if we need to display multiple pages.
	$perpage = $mybb->settings['editsperpages'];
	$page = intval($mybb->input['page']);

	$query = $db->simple_select("edithistory", "COUNT(eid) AS history_count", "pid='{$pid}'");
	$history_count = $db->fetch_field($query, "history_count");

	$pages = ceil($history_count/$perpage);

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

	$multipage = multipage($history_count, $perpage, $page, "edithistory.php?pid={$pid}");

	$query = $db->query("
		SELECT e.*, u.username AS user_name
		FROM ".TABLE_PREFIX."edithistory e
		LEFT JOIN ".TABLE_PREFIX."posts p ON (e.pid=p.pid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (e.uid=u.uid)
		WHERE e.pid='{$pid}'
		ORDER BY e.dateline DESC
		LIMIT {$start}, {$perpage}
	");

	while($history = $db->fetch_array($query))
	{
		$alt_bg = alt_trow();
		if(!$history['reason'])
		{
			$history['reason'] = $lang->na;
		}
		$history['reason'] = htmlspecialchars_uni($history['reason']);

		$history['username'] = build_profile_link($history['user_name'], $history['uid']);
		$dateline = my_date($mybb->settings['dateformat'], $history['dateline']).", ".my_date($mybb->settings['timeformat'], $history['dateline']);

		// Sanitize post
		$history['originaltext'] = htmlspecialchars_uni($history['originaltext']);

		if($mybb->settings['edithistorychar'] > 0 && my_strlen($history['originaltext']) > $mybb->settings['edithistorychar'])
		{
			$originaltext = my_substr($history['originaltext'], 0, $mybb->settings['edithistorychar']) . "... <span class=\"smalltext\">[<a href=\"javascript:MyBB.popupWindow('edithistory.php?action=viewfull&eid={$history['eid']}', 'viewfull', '400', '500') \">{$lang->view_full_post}</a>]<span>";
		}
		else
		$originaltext = $history['originaltext'];

		eval("\$edit_history .= \"".$templates->get("edithistory_item")."\";");
	}

	if(!$edit_history)
	{
		eval("\$edit_history = \"".$templates->get("edithistory_nohistory")."\";");
	}

	eval("\$edithistory = \"".$templates->get("edithistory")."\";");
	output_page($edithistory);
}

?>
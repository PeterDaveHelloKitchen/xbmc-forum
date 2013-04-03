<?php
/**
 * Copyright 2012 Team XBMC, All Rights Reserved
 *
 * Website: http://xbmc.org
 * Author: da-anda
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// register hooks
$plugins->add_hook("global_start", "xbmc_InitializeStart");
$plugins->add_hook("pre_output_page", "xbmc_AddToFooter");
$plugins->add_hook("private_send_end", "xbmc_SendPrivateMessageFormEnd");
$plugins->add_hook("postbit", "xbmc_RenderPost");
$plugins->add_hook("pre_output_page", "xbmc_PreOutputPage");
$plugins->add_hook("forumdisplay_start", "xbmc_ForumDisplayStart");

$plugins->add_hook("showteam_start", "xbmc_DenyAccessToSectionIfNoValidUser");
$plugins->add_hook("memberlist_start", "xbmc_DenyAccessToSectionIfNoValidUser");

$plugins->add_hook("text_parse_message", "xbmc_ConvertToPlaintext");
$plugins->add_hook("editpost_action_start", "xbmc_CleanupIncomingMessage");
$plugins->add_hook("newreply_do_newreply_start", "xbmc_CleanupIncomingMessage");
$plugins->add_hook("newreply_start", "xbmc_CleanupIncomingMessage");
$plugins->add_hook("newthread_do_newthread_start", "xbmc_CleanupIncomingMessage");
# cleanup user signatures
$plugins->add_hook("usercp_editsig_start", "xbmc_CleanupSignature");
$plugins->add_hook("usercp_do_editsig_start", "xbmc_CleanupSignature");
$plugins->add_hook("modcp_editprofile_start", "xbmc_CleanupSignature");
$plugins->add_hook("modcp_do_editprofile_start", "xbmc_CleanupSignature");

$plugins->add_hook("datahandler_post_insert_post", "xbmc_CleanupPostBeforeInsert");
$plugins->add_hook("datahandler_post_update", "xbmc_CleanupPostBeforeInsert");
$plugins->add_hook("datahandler_post_insert_thread_post", "xbmc_CleanupPostBeforeInsert");

$plugins->add_hook("mycode_add_codebuttons", "xbmc_AddMybbCodeButtons");
$plugins->add_hook("upload_avatar_end", "xbmc_fixAvatarUploadPath");


/**
 * Returns the meta information about this plugin
 *
 * @return array
 */
function xbmc_info()
{
	/**
	 * Array of information about the plugin.
	 * name: The name of the plugin
	 * description: Description of what the plugin does
	 * website: The website the plugin is maintained at (Optional)
	 * author: The name of the author of the plugin
	 * authorsite: The URL to the website of the author (Optional)
	 * version: The version number of the plugin
	 * guid: Unique ID issued by the MyBB Mods site for version checking
	 * compatibility: A CSV list of MyBB versions supported. Ex, "121,123", "12*". Wildcards supported.
	 */
	return array(
		"name"			=> "XBMC addons",
		"description"	=> "This plugins contains XBMC specific changes and features for this forum",
		"website"		=> "http://xbmc.org",
		"author"			=> "Team XBMC",
		"authorsite"	=> "http://xbmc.or",
		"version"		=> "0.41",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}

/**
 * ADDITIONAL PLUGIN INSTALL/UNINSTALL ROUTINES
 *
 * _install():
 *   Called whenever a plugin is installed by clicking the "Install" button in the plugin manager.
 *   If no install routine exists, the install button is not shown and it assumed any work will be
 *   performed in the _activate() routine.
 *
 * function hello_install()
 * {
 * }
 *
 * _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
 *
 * function hello_is_installed()
 * {
 *		global $db;
 *		if($db->table_exists("hello_world"))
 *  	{
 *  		return true;
 *		}
 *		return false;
 * }
 *
 * _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
 *
 * function hello_uninstall()
 * {
 * }
 *
 * _activate():
 *    Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin
 *    "visible" by adding templates/template changes, language changes etc.
 *
 * function hello_activate()
 * {
 * }
 *
 * _deactivate():
 *    Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view
 *    by removing templates/template changes etc. It should not, however, remove any information
 *    such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is
 *    uninstalled, this routine will also be called before _uninstall() if the plugin is active.
 *
 * function hello_deactivate()
 * {
 * }
 */


/**
 * Activate routine
 *
 *@return void
 */
function xbmc_activate() {
	global $db, $mybb, $lang;
	
	// settings groupID of "user registration and profile options"
	$groupId = 9;

	// add signatureuploadpath to settings
	$query = $db->simple_select('settings', 'name', 'name="signatureuploadpath"');
	if (!$query || !$db->fetch_field($query, 'name')) {
		$insertarray = array(
			'name' => 'signatureuploadpath',
			'title' => 'XBMC: Signature upload path',
			'description' => 'Path where signatures are stored',
			'optionscode' => 'text',
			'value' => '',
			'disporder' => 0,
			'gid' => $groupId
		);
		$db->insert_query("settings", $insertarray);
	}

	// add avatardisplaypath to settings
	$query = $db->simple_select('settings', 'name', 'name="avatardisplaypath"');
	if (!$query || !$db->fetch_field($query, 'name')) {
		$insertarray = array(
			'name' => 'avatardisplaypath',
			'title' => 'XBMC: Avatar display path',
			'description' => 'If set, this path is used to create the display URL for avatars. This is useful if your upload path is outside of the website root but you use an server alias to grant read access for it.',
			'optionscode' => 'text',
			'value' => './uploads/avatars',
			'disporder' => 30,
			'gid' => $groupId
		);
		$db->insert_query("settings", $insertarray);
	}
}

/**
 * Is triggered after basic myBB initialization and prepares and injects
 * custom variables, template adjustments and language labels
 *
 * @return void
 */
function xbmc_InitializeStart() {
	global $mybb, $templates, $templatelist, $lang;

	// initialize xbmc namespace
	$mybb->xbmc = array();
	$mybb->xbmc['isLoginUser'] = $mybb->user['uid'] != 0 ? TRUE : FALSE;

	// load XBMC labels
	$lang->load('xbmc');

	// set debug mode for templates during development
	$mybb->dev_mode = 0;

	$mybb->settings['xbmc_url'] = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$mybb->settings['xbmc_referer'] = (strpos($_SERVER['HTTP_REFERER'], $mybb->settings['bburl']) ? $_SERVER['HTTP_REFERER'] : '');

	// override the template loader with our custom implementation
	require_once(MYBB_ROOT . 'xbmc/xclass/class_templates.php');
	$templates = new xbmc_templates;
	$templates->setTemplateFile(MYBB_ROOT . 'xbmc/theme/xbmc_theme.xml');
	$templates->setTheme(3); //ID of theme to override
	$templates->setTemplateSet(2); // ID of template-set to adjust/update/override
	
	// add custom templates that should be globally available
	$templatelist .= (strlen($templatelist) ? ',' : '') . 'headerbit_links_' . ($mybb->xbmc['isLoginUser'] ? 'user' : 'guest');
}

/**
 * Is triggered by the single forum view
 *
 * @return void
 */
function xbmc_ForumDisplayStart() {
	// override some language strings with custom translations
	// without altering myBB core files
	xbmc_OverrideLanguage();
}

/**
 * Adds additional fields to the post object for use in the post templates
 *
 * @param array	$post		The post to be rendered
 * @return array The updated post
 */
function xbmc_RenderPost(array $post) {
	global $thread;

	// if subject is empty, copy it from the thread
	if (!$post['subject'] && $post['icon']) {
		$post['subject'] = $thread['subject'];
	} else if (!$post['icon'] && $post['subject']) {
		$post['subject'] = '';	
	}
	if ($post['fid2']) $post['fid2'] = 'Location: ' . $post['fid2'];
	return $post;
}

/**
 * Adjusts the PM form
 *
 * @return void
 */
function xbmc_SendPrivateMessageFormEnd() {
	global $options, $optionschecked;
	// convert the "checked=checked" flag to a boolean value in order to be used as hidden field in the templates
	$options['readreceipt'] = $optionschecked['readreceipt'] ? 1 : 0;
}


/**
 * This method allows to manipulate the page output
 *
 * @param string	$content		The prerendered website
 * @return string	The adjusted website html
 */
function xbmc_PreOutputPage($content) {
	global $lang, $templates, $mybb, $privatemessage_text, $theme, $modcplink, $admincplink;

	// send correct headers if forum is turned off
	if ($mybb->settings['boardclosed']) {
		header('Status: 503 Service Unavailable');
		header('HTTP/1.0 503 Service Unavailable');
		header('Retry-After: 3600');
	}

	// add conditional HTML5 compaint <head>-Tag
	$tagAttributes = '';
	$replaceTag = '<html';

	if ($lang->settings['htmllang']) {
		$replaceTag .= ' xml:lang="' . $lang->settings['htmllang'] . '"';
		$tagAttributes .= ' lang="' . $lang->settings['htmllang'] . '"';	
	}
	if ($lang->settings['rtl'] == 1) {
		$tagAttributes .= ' dir="rtl"';
	}
	$replaceTag .= $tagAttributes . ' xmlns="http://www.w3.org/1999/xhtml">';
	
	$htmlTag = '<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7"' . $tagAttributes . '> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8 ie7"' . $tagAttributes . '> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9 ie8"' . $tagAttributes . '> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"' . $tagAttributes . '> <!--<![endif]-->';

	$content = str_replace($replaceTag, $htmlTag, $content);
	//--end <head>-Tag



	// Add custom user menu items to top bar
	if ($mybb->xbmc['isLoginUser']) {
		if (isset($privatemessage_text) && strlen($privatemessage_text)) {
			$mybb->xbmc['user']['pmStatus'] = 'new';
		} else {
			$mybb->xbmc['user']['pmStatus'] = $mybb->user['pms_unread'] ? 'unread' : 'read';
		}
		eval("\$headerlinks = \"".$templates->get("headerbit_links_user")."\";");
	} else {
		eval("\$headerlinks = \"".$templates->get("headerbit_links_guest")."\";");
	}
	$content = str_replace('###HEADERLINKS###', $headerlinks, $content);



	return $content;	
}

/**
 * Converts parts of the passed message to plaintext according to custom rules
 *
 * @param string	$message		The incoming message/post/signature
 * @return string	The modified/adjusted message
 */
function xbmc_ConvertToPlaintext($message) {
	global $mybb, $forum;

	//fix/drop sizes from old forum
	$message = preg_replace('!\[size=[a-z_-]+\](.+?)\[/size\]!is', '$1', $message);
	// strip unwanted tags from signatures
	$message = preg_replace('!\[imgur\](.*)\[/imgur\]!is', 'http://imgur.com/a/$1', $message);
	
	return $message;
}

/**
 * allows to parse a Message with custom parsing rules (e.g. converting BBCode to HTML)
 *
 * @param string	$message		The incoming message/post/signature
 * @return string	The modified/adjusted message
 */
function xbmc_ParseMessage($message) {
	global $mybb, $forum;

	// no [imgur] tags outside skin sections - thus convert em regular links if found in messages
	/**
	 * This is disabled as we for now allow imgur galleries globally. If it's abused to much, enable this section again
	if (isset($forum) && $forum['fid']) {
		$parents = explode(',', get_parent_list($forum['fid']));
		if (!in_array(12, $parents) && !in_array(3, $parents)&& !in_array(67, $parents)) {
			$message = preg_replace('!\[imgur\](.*)\[/imgur\]!is', 'http://imgur.com/a/$1 (no galleries allowed here)', $message);
		}
	}
	*/

	return $message;
}

/**
 * The method cleans up new posts/threads and their previews.
 * Unfortunately xbmc_ParseMessage isn't called in every occasion it seems :(
 *
 * @return void
 */
function xbmc_CleanupIncomingMessage() {
	global $mybb, $fid;
	if (isset($mybb->input['message'])) {
		$mybb->input['message'] = xbmc_ParseMessage($mybb->input['message'], TRUE);
	}
}

/**
 * The method cleans up signatures when saved
 *
 * @return void
 */
function xbmc_CleanupSignature() {
	global $mybb, $fid;
	if (isset($mybb->input['signature']) && $mybb->input['signature']) {
		$signature = $mybb->input['signature'];
		// no direct links in signatures
		$signature = preg_replace('!\(https?:\/\/[^[:space:]]*\)!i', '', $signature);
		$signature = xbmc_ConvertToPlaintext($signature);
		$mybb->input['signature'] = $signature;
	}
}

/**
 * Cleans up the incoming post before it's written to the DB.
 * This is the last chance to alter the post. We use those hooks
 * in order to also cleanup posts sent by tapatalk (hopefully)
 *
 * @param PostDataHandler $postHandler
 * @return void
 */
function xbmc_CleanupPostBeforeInsert(&$postHandler) {
	global $mybb, $fid, $message ;

	$dataStorages = array(
		'data',
		'post_insert_data',
		'post_update_data',
#		'thread_insert_data',
#		'thread_update_data'
	);

	foreach ($dataStorages as $dataKey) {
		$dataSet = &$postHandler->$dataKey;
		if (count($dataSet) && isset($dataSet['message'])) {
			$dataSet['message'] = $mybb->input['message'] = xbmc_ParseMessage($dataSet['message'], TRUE);
			
		}
	}
	// $message is used in xmlhttp request and it's the only way to manipulate the return 
	// value without using 'parse_message' hook that would add extra load on realtime parsing of the forum
	if (isset($message)) {
		$message = xbmc_ParseMessage($message, TRUE);
	}
}

/**
 * Adds custom mybb code buttons
 *
 * @param array $languageStrings
 * @return array The languageStrings
 */
function xbmc_AddMybbCodeButtons(array $languageStrings) {
	$customStrings = array(
		'editor_title_imgur',
		'editor_enter_imgur'
	);
	return array_merge($languageStrings, $customStrings);
}

/**
 * Fixes the file path of uploaded avatars
 * 
 * @param array $avatarSettings
 * @return array The modified avatar settings
 */
function xbmc_fixAvatarUploadPath(array $avatarSettings) {
	global $mybb;
	if ($avatarSettings['avatar'] && $mybb->settings['avatardisplaypath']) {
		$avatarSettings['avatar'] = str_replace($mybb->settings['avataruploadpath'], $mybb->settings['avatardisplaypath'], $avatarSettings['avatar']);
	}
	return $avatarSettings;
}

/**
 * Adds additional HTML to the website footer
 *
 * @param string	$page		The prerendered website
 * @return string	The altered website HTML
 */
function xbmc_AddToFooter($page) {

	// add analytics code
	$page = str_replace('</body>', "
<script type=\"text/javascript\">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-3066672-3']);
  _gaq.push(['_setDomainName', '.xbmc.org']);
  _gaq.push(['_gat._anonymizeIp']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</body>", $page);

	return $page;
}

/**
 * Block access to certain forum pages if no user is logged in
 * to prevent crawlers from indexing/accessing sensible pages
 *
 * @return void
 */
function xbmc_DenyAccessToSectionIfNoValidUser() {
	global $mybb;
	if (!$mybb->user || $mybb->user['uid'] <= 0) {
		header('HTTP/1.1 403 Forbidden');
		error('Sorry, only logged in users are allowed to access this page.');
		exit;
	}
}

/**
 * Override some language strings with custom translations
 * without altering myBB core files
 *
 * @return void
 */
function xbmc_OverrideLanguage() {
	global $lang;
	$lfile = $lang->path."/".$lang->language."/xbmc.lang.php";
	include($lfile);

	if (is_array($l)) {
		foreach ($l as $k => $v) {
			$lang->$k = $v;	
		}
	}
}

/**
 * Renders some debug output ONLY if the admin user is logged in
 *
 * @param string	$message	The debug message
 * @param string	$title	The optional title/headline
 * @return void
 */
function xbmc_debug($message, $title='') {
	global $mybb;
	if ($mybb->user['uid'] == 49419) {
		echo '<pre>';
		var_dump($message);
		echo '</pre>';
	}
}

?>
<?php

if(!defined('IN_MYBB')) {
	die('This file cannot be accessed directly.');
}

if(defined('IN_ADMINCP')) {
    $plugins->add_hook("admin_forum_menu", "ficons_sub_menu");
    $plugins->add_hook("admin_forum_action_handler", "ficons_action");
} else {
	$plugins->add_hook("global_start", "ficons_templates");
    $plugins->add_hook("build_forumbits_forum", "ficons_show");
}

function ficons_info() {
    return array(
        "name" => "Forum Icons",
        "description" => "Add unique icons to each forum",
        "website" => "",
        "author" => "chack1172",
        "authorsite" => "http://chack1172.altervista.org/",
        "version" => "5.1",
        "compatibility" => "18*",
        "codename" => "ficons"
    );
}

function ficons_install() {
    global $db;

    $collation = $db->build_create_table_collation();

	if(!$db->table_exists("forum_icons")) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."forum_icons` (
			`id` int(10) UNSIGNED NOT NULL auto_increment,
			`fid` int(10) UNSIGNED NOT NULL,
            `image` varchar(500) NOT NULL default '',
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM{$collation}");
	}

    $template = '<img src="{$forum_icon}" alt="" style="float: left; max-width: 200px; max-height: 150px;padding-right: 10px">';

    $insert_array = array(
        'title' => 'forum_icons',
        'template' => $db->escape_string($template),
        'sid' => '-1',
        'version' => '',
        'dateline' => time()
    );

    $db->insert_query('templates', $insert_array);

    $setting_group = array(
        'name' => 'ficons',
        'title' => 'Forum Icons Settings',
        'description' => 'Here you can change settings of plugin Forum Icons',
        'disporder' => 5,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'ficons_visible' => array(
            'title' => 'Show icons?',
            'description' => 'Would you show icons in forum list?',
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 1
        ),
    );


    foreach($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    rebuild_settings();

	// Create datacache entry
	ficons_update_cache([]);
}

function ficons_is_installed() {
    global $db;

    return $db->table_exists("forum_icons");
}

function ficons_uninstall() {
    global $db, $cache;

    $db->drop_table("forum_icons");
    $db->delete_query("templates", "title = 'forum_icons'");

    $db->delete_query('settings', "name = 'ficons_visible'");
    $db->delete_query('settinggroups', "name = 'ficons'");

    rebuild_settings();

    $cache->delete('forum_icons');
}

function ficons_activate() {
    global $mybb;

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

    find_replace_templatesets(
        "forumbit_depth2_forum",
        "#" . preg_quote('{$forum[\'name\']}') . "#i",
        '{$forum[\'icon\']}{$forum[\'name\']}'
    );

    find_replace_templatesets(
        "forumbit_depth2_cat",
        "#" . preg_quote('{$forum[\'name\']}') . "#i",
        '{$forum[\'icon\']}{$forum[\'name\']}'
    );
}

function ficons_deactivate() {
    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

    find_replace_templatesets(
        "forumbit_depth2_forum",
        "#" . preg_quote('{$forum[\'icon\']}') . "#i",
        ''
    );

    find_replace_templatesets(
        "forumbit_depth2_cat",
        "#" . preg_quote('{$forum[\'icon\']}') . "#i",
        ''
    );
}

function ficons_sub_menu(&$sub_menu) {
    global $lang;

    $lang->load("forum_icons");

    $sub_menu[] = array("id" => "icons", "title" => $lang->ficons_title, "link" => "index.php?module=forum-icons");
}

function ficons_action(&$actions) {
    $actions['icons'] = array('active' => 'icons', 'file' => 'icons.php');
}

function ficons_show(&$forum) {
    global $templates, $forum_url, $mybb;

	$forum['icon'] = '';
    if($mybb->settings['ficons_visible'] == 1) {
        $forum_icon = ficons_get_icon($forum['fid']);
        if(!empty($forum_icon)) {
            eval("\$forum['icon'] = \"".$templates->get("forum_icons")."\";");
        }
    }
}

function ficons_get_icon($fid) {
    global $cache;
	$iconsCache = $cache->read('forum_icons');
    if (!is_array($iconsCache)) {
        $iconsCache = ficons_update_cache();
    }
    if (isset($iconsCache[$fid])) {
        return $iconsCache[$fid];
    } else {
        return '';
    }
}

function ficons_update_cache($values = null)
{
	global $cache, $db;
	if ($values === null) {
		$query = $db->simple_select('forum_icons', 'fid, image');
		$values = [];
		while ($ficon = $db->fetch_array($query)) {
            $values[$ficon['fid']] = $ficon['image'];
        }
	}
	$cache->update('forum_icons', $values);

	return $values;
}

function ficons_templates()
{
	global $templatelist;

	if (defined('THIS_SCRIPT') && in_array(THIS_SCRIPT, ['index.php', 'forumdisplay.php'])) {
		if(isset($templatelist)) {
			$templatelist .= ',';
		} else {
			$templatelist = '';
		}
		$templatelist .= 'forum_icons';
	}
}

function reload_forum_icons()
{
	ficons_update_cache();
}

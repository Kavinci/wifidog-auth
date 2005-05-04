<?php


// $Id$
/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file index.php Displays the portal page
 * @author Copyright (C) 2004 Benoit Gr�goire et Philippe April
 */

define('BASEPATH', '../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/MainUI.php';

if (CONF_USE_CRON_FOR_DB_CLEANUP == false)
{
	garbage_collect();
}

$node = null;
if (!empty ($_REQUEST['gw_id']))
	$node = Node :: getObject($_REQUEST['gw_id']);

if ($node == null)
{
	$smarty->display("templates/message_unknown_hotspot.html");
	exit;
}

$node_id = $node->getId();
$portal_template = $node_id.".html";
Node :: setCurrentNode($node);

$ui = new MainUI();
if (isset ($session))
{
    if(!empty($_REQUEST['gw_id']))
        $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);
	$smarty->assign("original_url_requested", $session->get(SESS_ORIGINAL_URL_VAR));
}

$tool_html = '';

//TODO: Write this ?
/*
$tool_html .= "<h1>"._("At this Hotspot")."</h1>"."\n";
$tool_html .= '<p class="indent">'."\n";
$tool_html .= "Local content..."."\n";
$tool_html .= "</p>"."\n";*/

$tool_html .= "<h1>"._("Online users")."</h1>"."\n";
$tool_html .= '<p class="indent">'."\n";
$current_node = Node :: getCurrentNode();
if ($current_node != null)
{
	$current_node_id = $current_node->getId();
	$online_users = $current_node->getOnlineUsers();
	$num_online_users = count($online_users);
	if ($num_online_users > 0)
	{
		//$tool_html .= $num_online_users.' '._("other users online at this hotspot...");
        $tool_html .= "<ul class='users_list'>\n";
        foreach($online_users as $online_user)
            $tool_html .= "<li>{$online_user->getUsername()}</li>\n";
        $tool_html .= "</ul>\n";
	}
	else
	{
		$tool_html .= _("Nobody is online at this hotspot...");
	}
}
else
{
	$current_node_id = null;
	$tool_html .= _("You are not currently at a hotspot...");
}
$tool_html .= "</p>"."\n";

$tool_html .= '<p class="indent">'."\n";

$tool_html .= "<a href='/content/?gw_id={$current_node_id}' target='_blank.right'><img src='/images/start.gif'></a>"."\n";
$tool_html .= "</p>"."\n";
$ui->setToolContent($tool_html);

$hotspot_network_name = HOTSPOT_NETWORK_NAME;
$hotspot_network_url = HOTSPOT_NETWORK_URL;
$network_logo_url = COMMON_CONTENT_URL.NETWORK_LOGO_NAME;
$network_logo_banner_url = COMMON_CONTENT_URL.NETWORK_LOGO_BANNER_NAME;

$hotspot_logo_url = find_local_content_url(HOTSPOT_LOGO_NAME);
$hotspot_logo_banner_url = find_local_content_url(HOTSPOT_LOGO_BANNER_NAME);

$html = '';
$html .= "<div id='portal_container'>\n";

// Get the current user
$current_user = User :: getCurrentUser();

/* Network section */

// This table ( width 100% ) force each section to display on top of each other, even though the content will float
// Until we find a better solution CSS only...
$html .= "<table><tr><td>";
$html .= "<div class='portal_network_section'>\n";
$html .= "<a href='{$hotspot_network_url}'><img class='portal_section_logo' alt='{$hotspot_network_name} logo' src='{$network_logo_banner_url}' border='0'></a>\n";
// Get all network content and EXCLUDE user subscribed content
if($current_user)
    $contents = Network :: getCurrentNetwork()->getAllContent(true, $current_user);
else
    $contents = Network :: getCurrentNetwork()->getAllContent();
if ($contents)
{
	foreach ($contents as $content)
	{
		if ($content->isDisplayableAt($node))
		{
			$html .= "<div class='portal_content'>\n";
			$html .= $content->getUserUI();
			$html .= "</div>\n";
		}
	}
}
$html .= "</div>\n";
$html .= "</td></tr></table>";

/* Node section */
// Get all node content and EXCLUDE user subscribed content
if($current_user)
    $contents = $node->getAllContent(true, $current_user);
else
    $contents = $node->getAllContent();
if($contents)
{
    $html .= "<table><tr><td>";
    $html .= "<div class='portal_node_section'>\n";
    $html .= "<img class='portal_section_logo' src='{$hotspot_logo_url}' alt=''>\n";
    $html .= "<span class='portal_section_title'>"._("Content from:")." ";
    $node_homepage = $node->getHomePageURL();
    if (!empty ($node_homepage))
    {
    	$html .= "<a href='$node_homepage'>";
    }
    $html .= $node->getName();
    if (!empty ($node_homepage))
    {
    	$html .= "</a>\n";
    }
    $html .= "</span>";
    foreach ($contents as $content)
    {
    	if ($content->isDisplayableAt($node))
    	{
    		$html .= "<div class='portal_content'>\n";
    		$html .= $content->getUserUI();
    		$html .= "</div>\n";
    	}
    }
    $html .= "</div>\n";
    $html .= "</td></tr></table>";
}

/* User section */
if($current_user)
{
    $contents = User :: getCurrentUser()->getAllContent();
    if($contents)
    {
        $html .= "<table><tr><td>";
        $html .= "<div class='portal_user_section'>\n";
        $html .= "<h1>"._("My content")."</h1>\n";
        foreach ($contents as $content)
        {
        	$html .= "<div class='portal_content'>\n";
        	$html .= $content->getUserUI();
        	$html .= "</div>\n";
        }
        $html .= "</div>\n";
        $html .= "</td></tr></table>";
    }
}

// Hyperlinks to full content display page
$html .= "<a href='/content/?gw_id={$current_node_id}'>"._("Show all available contents for this hotspot")."</a>"."\n";

$html .= "<div style='clear:both;'></div>";
$html .= "</div>\n";

$ui->setMainContent($html);
$ui->display();
?>
<?php

/***************************************************************************
 *
 *   OUGC Post Character Count Enhancement plugin (/inc/plugins/ougc_pochcoen.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2013 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Strips HTML/MyCode/Quotes from being counted in the minimum/maximum characters per post verification.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Tell MyBB when to run the hook
if(!defined('IN_ADMINCP'))
{
	$plugins->add_hook('datahandler_post_validate_post', 'ougc_pochcoen');
	$plugins->add_hook('datahandler_post_validate_thread', 'ougc_pochcoen');
}

// Plugin API
function ougc_pochcoen_info()
{
	global $lang;

	return array(
		'name'			=> 'OUGC Post Character Count Enhancement',
		'description'	=> 'Strips HTML/MyCode/Quotes from being counted in the minimum/maximum characters per post verification.',
		'website'		=> 'http://mods.mybb.com/view/ougc-post-character-count-enhancement',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.0',
		'guid' 			=> '2b70c8cd879291b5d65a6aacb9ee0473',
		'compatibility' => '16*'
	);
}

function ougc_pochcoen(&$dh)
{
	global $settings;

	$msgcount = ougc_pochcoen_countchars($dh->data['message']);
	$minchars = (int)$settings['minmessagelength'];

	if($msgcount < $minchars && $minchars > 0 && !is_moderator($dh->data['fid'], '', $dh->data['uid']))
	{
		$dh->set_error('message_too_short', array($minchars));
	}
}

/*
* Shorts a message to look like a preview.
*
* @param string Message to short.
* @param int Maximum characters to show.
* @param bool Strip MyCode Quotes from message.
* @param bool Strip MyCode from message.
*/
function ougc_pochcoen_countchars($message)
{
	// Attempt to remove any quotes
		$message = preg_replace(array(
			'#\[quote=([\"\']|&quot;|)(.*?)(?:\\1)(.*?)(?:[\"\']|&quot;)?\](.*?)\[/quote\](\r\n?|\n?)#esi',
			'#\[quote\](.*?)\[\/quote\](\r\n?|\n?)#si',
			'#\[quote\]#si',
			'#\[\/quote\]#si'
		), '', $message);

	// Attempt to remove any MyCode
		global $parser;
		if(!is_object($parser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';
			$parser = new postParser;
		}

		$message = $parser->parse_message($message, array(
			'allow_html'		=>	0,
			'allow_mycode'		=>	1,
			'allow_smilies'		=>	0,
			'allow_imgcode'		=>	1,
			'filter_badwords'	=>	1,
			'nl2br'				=>	0
		));

		// before stripping tags, try converting some into spaces
		$message = preg_replace(array(
			'~\<(?:img|hr).*?/\>~si',
			'~\<li\>(.*?)\</li\>~si'
		), array(' ', "\n* $1"), $message);

		$message = unhtmlentities(strip_tags($message));

	// Remove all spaces?
		$message = trim_blank_chrs($message);
		$message = preg_replace('/\s+/', '', $message);

	// convert \xA0 to spaces (reverse &nbsp;)
		$message = trim(preg_replace(array('~ {2,}~', "~\n{2,}~"), array(' ', "\n"), strtr($message, array("\xA0" => ' ', "\r" => '', "\t" => ' '))));

	// newline fix for browsers which don't support them
		$message = preg_replace("~ ?\n ?~", " \n", $message);

	return (int)my_strlen($message);
}
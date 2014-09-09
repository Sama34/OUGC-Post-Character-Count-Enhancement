<?php

/***************************************************************************
 *
 *	OUGC Post Character Count Enhancement plugin (/inc/plugins/ougc_pochcoen.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2013-2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Strips HTML/MyCode/Quotes from being counted in the minimum/maximum characters per post verification process.
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

// Run/Add Hooks
if(!defined('IN_ADMINCP'))
{
	$function = create_function('&$dh', '
		// Message is not being edited
		if(!isset($dh->data[\'message\']))
		{
			return;
		}

		global $settings;

		$msgcount = ougc_countchars($dh->data[\'message\']);
		$minchars = (int)$settings[\'minmessagelength\'];

		if($msgcount < $minchars && $minchars > 0 && !is_moderator($dh->data[\'fid\'], \'\', $dh->data[\'uid\']))
		{
			$dh->set_error(\'message_too_short\', array($minchars));
		}
	');

	$plugins->add_hook('datahandler_post_validate_post', $function);
	$plugins->add_hook('datahandler_post_validate_thread', $function);
	unset($function);
}

// Plugin API
function ougc_pochcoen_info()
{
	return array(
		'name'			=> 'OUGC Post Character Count Enhancement',
		'description'	=> 'Strips HTML/MyCode/Quotes from being counted in the minimum/maximum characters per post verification process.',
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8.0',
		'versioncode'	=> '1800',
		'compatibility'	=> '16*,18*',
		'guid' 			=> '2b70c8cd879291b5d65a6aacb9ee0473'
	);
}

// _activate() routine
function ougc_pochcoen_activate()
{
	global $cache;

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_pochcoen_info();

	if(!isset($plugins['pochcoen']))
	{
		$plugins['pochcoen'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['pochcoen'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _is_installed() routine
function ougc_pochcoen_is_installed()
{
	global $cache;

	$plugins = (array)$cache->read('ougc_plugins');

	return !empty($plugins['pochcoen']);
}

// _uninstall() routine
function ougc_pochcoen_uninstall()
{
	global $cache;

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['pochcoen']))
	{
		unset($plugins['pochcoen']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	elseif(method_exists($cache, 'delete'))
	{
		$cache->delete('ougc_plugins');
	}
	else
	{
		global $db;

		!is_object($cache->handler) or $cache->handler->delete('ougc_plugins');
		$db->delete_query('datacache', 'title=\'ougc_plugins\'');
	}
}

if(!function_exists('ougc_countchars'))
{
	/**
	 * Counts a message's characters removing special code.
	 * Based off Zinga Burga's "Thread Tooltip Preview" plugin threadtooltip_getpreview() function.
	 *
	 * @param string Message to count.
	 * @return integer Characters count
	**/
	function ougc_countchars($message)
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
}
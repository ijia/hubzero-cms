<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

//----------------------------------------------------------

class TagMacro extends WikiMacro 
{
	public function description() 
	{
		$txt = array();
		$txt['wiki'] = 'This macro will generate a link to a Tag.';
		$txt['html'] = '<p>This macro will generate a link to a Tag.</p>';
		return $txt['html'];
	}
	
	//-----------
	
	public function render() 
	{
		$tag = $this->args;
		
		if ($tag) {
			// Perform query
			$this->_db->setQuery( "SELECT raw_tag FROM #__tags WHERE tag='".$tag."' OR alias='".$tag."' LIMIT 1" );
			$a = $this->_db->loadResult();

			// Did we get a result from the database?
			if ($a) {
				// Build and return the link
				return '<a href="'.JRoute::_( 'index.php?option=com_tags&tag='.$tag ).'">'.stripslashes($a).'</a>';
				//return '['.JRoute::_( 'index.php?option=com_tags&amp;tag='.$tag ).' '.stripslashes($a).']';
			} else {
				// Return error message
				return '('.$tag.' not found)';
			}
		} else {
			return '';
		}
	}
}
?>
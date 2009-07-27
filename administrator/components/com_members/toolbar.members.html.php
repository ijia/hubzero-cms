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
// Class for toolbar generation
//----------------------------------------------------------

class MembersToolbar
{
	public function _CANCEL() 
	{
		JToolBarHelper::title( JText::_( 'MEMBER' ).': <small><small>[ New ]</small></small>', 'user.png' );
		JToolBarHelper::cancel();
	}
	
	//-----------
	
	public function _EDIT($edit) 
	{
		$text = ( $edit ? JText::_( 'EDIT' ) : JText::_( 'NEW' ) );
		
		JToolBarHelper::title( JText::_( 'MEMBER' ).': <small><small>[ '. $text.' ]</small></small>', 'user.png' );
		JToolBarHelper::apply();
		JToolBarHelper::save();
		JToolBarHelper::cancel();
	}
	
	//-----------
	
	public function _DEFAULT() 
	{
		$juser = & JFactory::getUser();
		
		JToolBarHelper::title( JText::_( 'MEMBERS' ), 'user.png' );
		JToolBarHelper::preferences('com_members', '550');
		if ($juser->authorize( 'com_members', 'admin' )) {
			JToolBarHelper::addNew();
		}
		JToolBarHelper::editList();
		if ($juser->authorize( 'com_members', 'admin' )) {
			JToolBarHelper::deleteList();
		}
	}
}
?>
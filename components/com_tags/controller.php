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

class TagsController extends JObject
{	
	private $_name  = NULL;
	private $_data  = array();
	private $_task  = NULL;

	//-----------
	
	public function __construct( $config=array() )
	{
		$this->_redirect = NULL;
		$this->_message = NULL;
		$this->_messageType = 'message';
		
		// Set the controller name
		if (empty( $this->_name )) {
			if (isset($config['name'])) {
				$this->_name = $config['name'];
			} else {
				$r = null;
				if (!preg_match('/(.*)Controller/i', get_class($this), $r)) {
					echo "Controller::__construct() : Can't get or parse class name.";
				}
				$this->_name = strtolower( $r[1] );
			}
		}
		
		// Set the component name
		$this->_option = 'com_'.$this->_name;
	}

	//-----------

	public function __set($property, $value)
	{
		$this->_data[$property] = $value;
	}
	
	//-----------
	
	public function __get($property)
	{
		if (isset($this->_data[$property])) {
			return $this->_data[$property];
		}
	}
	
	//-----------
	
	private function getTask()
	{
		$task = strtolower(JRequest::getVar('task', ''));
		$this->_task = $task;
		return $task;
	}
	
	//-----------
	
	public function execute()
	{	
		switch ( $this->getTask() ) 
		{
			case 'autocomplete': $this->autocomplete(); break;
			case 'cancel': $this->cancel(); break;
			case 'save':   $this->save();   break;
			case 'edit':   $this->edit();   break;
			case 'create': $this->create(); break;
			case 'delete': $this->delete(); break;
			case 'view':   $this->view();   break;
			case 'browse': $this->browse(); break;
			case 'cloud':  $this->cloud();  break;
			case 'feed':   $this->feed();   break;
			case 'feed.rss': $this->feed(); break;

			default: $this->cloud(); break;
		}
	}
	
	//-----------

	public function redirect()
	{
		if ($this->_redirect != NULL) {
			$app =& JFactory::getApplication();
			$app->redirect( $this->_redirect, $this->_message, $this->_messageType );
		}
	}

	//-----------
	
	private function getStyles() 
	{
		ximport('xdocument');
		XDocument::addComponentStylesheet($this->_option);
	}

	//-----------
	
	private function getScripts()
	{
		$document =& JFactory::getDocument();
		if (is_file(JPATH_ROOT.DS.'components'.DS.$this->_option.DS.$this->_name.'.js')) {
			$document->addScript('components'.DS.$this->_option.DS.$this->_name.'.js');
		}
	}

	//----------------------------------------------------------
	// Views
	//----------------------------------------------------------
	
	/*protected function intro()
	{
		// Build the page title
		$title = JText::_(strtoupper($this->_name));
		
		// Write title
		$document =& JFactory::getDocument();
		$document->setTitle( $title );

		// Set the pathway
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}

		// Push some styles to the template
		$this->getStyles();
		
		$database =& JFactory::getDBO();
		

		jimport( 'joomla.application.component.view');

		// Output HTML
		$view = new JView( array('name'=>'intro') );
		$view->title = $title;
		$view->groups = $popular;
		$view->config = $this->config;
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		$view->display();
	}*/
	
	//-----------
	
	protected function cloud() 
	{
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( JText::_(strtoupper($this->_name)) );
		
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}
		
		// Push some styles to the template
		$this->getStyles();
		
		// Set some variables
		$min_font_size = 1;
		$max_font_size = 1.8;
		
		// Check authorization
		$authorized = $this->_authorize();
		
		// Find all tags
		$database =& JFactory::getDBO();
		$t = new TagsTag( $database );
		//$tags = $t->getAllTags( $authorized );
		$tags = $t->getTopTags( 100 );
		
		$newtags = $t->getRecentTags( 25 );
		
		// Load plugins
		JPluginHelper::importPlugin('tags');
		$dispatcher =& JDispatcher::getInstance();

		// Trigger the functions that return the areas we'll be using
		$categories= array();
		$tagpile = array();
		/*$areas = $dispatcher->trigger( 'onTagAreas' );
		foreach ($areas as $a) 
		{
			$tagpile[key($a)] = $t->getTopTags( 25, key($a) );
			$categories[key($a)] = (is_array(end($a))) ? ucfirst(key($a)) : end($a);
		}*/
		
		$retarr = array();
		if ($tags) {
			foreach ($tags as $tag)
			{
				$retarr[$tag->raw_tag] = $tag->tcount;
			}
			ksort($retarr);

			$max_qty = max(array_values($retarr));  // Get the max qty of tagged objects in the set
			$min_qty = min(array_values($retarr));  // Get the min qty of tagged objects in the set
		} else {
			$max_qty = 0;
			$min_qty = 0;
		}

		// For ever additional tagged object from min to max, we add $step to the font size.
		$spread = $max_qty - $min_qty;
		if (0 == $spread) { // Divide by zero
			$spread = 1;
		}
		$step = ($max_font_size - $min_font_size)/($spread);

		// Output HTML
		//echo TagsHtml::cloud($this->_option, $tags, 0, $min_font_size, $max_font_size, $min_qty, $step, $tagpile, $categories, $newtags);
		jimport( 'joomla.application.component.view');

		// Output HTML
		$view = new JView( array('name'=>'intro') );
		$view->title = JText::_(strtoupper($this->_name));
		$view->tags = $tags;
		$view->showsizes = 0;
		$view->min_font_size = $min_font_size;
		$view->max_font_size = $max_font_size;
		$view->min_qty = $min_qty;
		$view->step = $step;
		$view->tagpile = $tagpile;
		$view->categories = $categories;
		$view->newtags = $newtags;
		$view->config = $this->config;
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		$view->display();
	}

	//-----------

	protected function view()
	{
		$database =& JFactory::getDBO();
		
		$title = JText::_(strtoupper($this->_name));
		
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}
		
		// Incoming
		$tagstring = trim(JRequest::getVar('tag', '', 'request', 'none', 2));
		
		$addtag = trim(JRequest::getVar('addtag', ''));
		
		// Ensure we were passed a tag
		if (!$tagstring && !$addtag) {
			$document =& JFactory::getDocument();
			$document->setTitle( $title );
			
			echo TagsHtml::div( TagsHtml::hed( 2, $title ), 'full', 'content-header' );
			echo TagsHtml::div( TagsHtml::error( JText::_('TAGS_NO_TAG') ), 'main section' );
			return;
		}
		
		if ($tagstring) {
			// Break the string into individual tags
			$tgs = explode(' ', $tagstring);
			$tgs = array_map('trim',$tgs);
		} else {
			$tgs = array();
		}
		
		// See if we're adding any tags to the search list
		if ($addtag && !in_array($addtag,$tgs)) {
			$tgs[] = $addtag;
		}

		// Sanitize the tag
		$t = new Tags( $database );
		
		$tags = array();
		$added = array();
		foreach ($tgs as $tag) 
		{
			$tag = $t->normalize_tag( $tag );
			
			if (in_array($tag,$added)) {
				continue;
			}
			
			$added[] = $tag;
			
			// Load the tag
			$tagobj = new TagsTag( $database );
			$tagobj->loadTag( $tag );

			// Ensure we loaded the tag's info from the database
			if ($tagobj->id) {
				$tags[] = $tagobj;
			}
		}

		// Ensure we loaded the tag's info from the database
		if (empty($tags)) {
			$document =& JFactory::getDocument();
			$document->setTitle( $title );
			
			echo TagsHtml::div( TagsHtml::hed( 2, $title ), 'full', 'content-header' );
			echo TagsHtml::div( TagsHtml::error( JText::_('TAGS_NOT_FOUND') ), 'main section' );
			return;
		}
	
		// Does the user have edit permissions?
		$authorized = $this->_authorize();
		
		// Set the page title
		$title .= ': ';
		$tagname = array();
		$tagstring = array();
		for ($i=0, $n=count( $tags ); $i < $n; $i++) 
		{
			if ($i > 0) {
				$title .= '+ ';
			}
			$tagname[] = $tags[$i]->raw_tag;
			$tagstring[] = $tags[$i]->tag;
			$title .= $tags[$i]->raw_tag.' ';
		}
		$document =& JFactory::getDocument();
		$document->setTitle( $title );
		
		$pathway->addItem(implode(' + ',$tagname),'index.php?option='.$this->_option.a.'tag='.implode('+',$tagstring));
		
		// Push some styles to the template
		$this->getStyles();
		
		// Load plugins
		JPluginHelper::importPlugin('tags');
		$dispatcher =& JDispatcher::getInstance();

		// Incoming paging vars
		$limit = JRequest::getInt( 'limit', 25 );
		$limitstart = JRequest::getInt( 'limitstart', 0 );
		$sort = JRequest::getVar( 'sort', '' );

		// Trigger the functions that return the areas we'll be using
		$areas = array();
		$searchareas = $dispatcher->trigger( 'onTagAreas' );
		foreach ($searchareas as $area) 
		{
			$areas = array_merge( $areas, $area );
		}

		// Get the active category
		$area = JRequest::getVar( 'area', '' );
		if ($area) {
			$activeareas = array($area);
		} else {
			$limit = 5;
			$activeareas = $areas;
		}

		// Get the search result totals
		$totals = $dispatcher->trigger( 'onTagView', array(
				$tags,
				0,
				0,
				$sort,
				$activeareas)
			);

		$limit = ($limit == 0) ? 'all' : $limit;

		// Get the search results
		$results = $dispatcher->trigger( 'onTagView', array(
				$tags,
				$limit,
				$limitstart,
				$sort,
				$activeareas)
			);
		
		// Get the total results found (sum of all categories)
		$i = 0;
		$total = 0;

		foreach ($areas as $c=>$t) 
		{
			$cats[$i]['category'] = $c;

			// Do sub-categories exist?
			if (is_array($t) && !empty($t)) {
				// They do - do some processing
				$cats[$i]['title'] = ucfirst($c);
				$cats[$i]['total'] = 0;
				$cats[$i]['_sub'] = array();
				$z = 0;
				// Loop through each sub-category
				foreach ($t as $s=>$st) 
				{
					// Ensure a matching array of totals exist
					if (is_array($totals[$i]) && !empty($totals[$i]) && isset($totals[$i][$z])) {
						// Add to the parent category's total
						$cats[$i]['total'] = $cats[$i]['total'] + $totals[$i][$z];
						// Get some info for each sub-category
						$cats[$i]['_sub'][$z]['category'] = $s;
						$cats[$i]['_sub'][$z]['title'] = $st;
						$cats[$i]['_sub'][$z]['total'] = $totals[$i][$z];
					}
					$z++;
				}
			} else {
				// No sub-categories - this should be easy
				$cats[$i]['title'] = $t;
				$cats[$i]['total'] = (!is_array($totals[$i])) ? $totals[$i] : 0;
			}

			// Add to the overall total
			$total = $total + intval($cats[$i]['total']);
			$i++;
		}

		// Do we have an active area?
		if (count($activeareas) == 1 && isset($activeareas[0])) {
			$active = $activeareas[0];
		} else {
			$active = '';
		}
		
		$related = null;
		if (count($tags) == 1) {
			//$related = $tags[0]->getRelatedTags();
		}
		
		// Output HTML
		echo TagsHtml::view( $title, $authorized, $tags, $this->_option, $totals, $results, $cats, $active, $limitstart, $limit, $sort, $total, $related );
	}

	//-----------

	protected function autocomplete() 
	{
		$database =& JFactory::getDBO();
		
		$filters = array();
		$filters['limit']  = 20;
		$filters['start']  = 0;
		$filters['search'] = trim(JRequest::getString( 'value', '' ));
		
		// Create a Tag object
		$obj = new TagsTag( $database );
		
		// Fetch results
		$rows = $obj->getAutocomplete( $filters );

		// Output search results in JSON format
		$json = array();
		if (count($rows) > 0) {
			foreach ($rows as $row) 
			{
				$json[] = '"'.$row->raw_tag.'"';
			}
		}
		
		echo '['.implode(',',$json).']';
	}

	//-----------

	protected function feed() 
	{
		include_once( JPATH_ROOT.DS.'libraries'.DS.'joomla'.DS.'document'.DS.'feed'.DS.'feed.php');
		
		global $mainframe;
		$database =& JFactory::getDBO();
		
		// Set the mime encoding for the document
		$jdoc =& JFactory::getDocument();
		$jdoc->setMimeEncoding('application/rss+xml');

		// Start a new feed object
		$doc = new JDocumentFeed;
		$params =& $mainframe->getParams();
		$doc->link = JRoute::_('index.php?option='.$this->_option);

		// Incoming
		$tagstring = trim(JRequest::getVar('tag', '', 'request', 'none', 2));
		
		// Ensure we were passed a tag
		if (!$tagstring) {
			echo TagsHtml::error( JText::_('TAGS_NO_TAG') );
			return;
		}
		
		// Break the string into individual tags
		$tgs = explode(' ', $tagstring);
		
		// Sanitize the tag
		$t = new Tags( $database );
		
		$tags = array();
		foreach ($tgs as $tag) 
		{
			$tag = $t->normalize_tag( $tag );
			
			// Load the tag
			$tagobj = new TagsTag( $database );
			$tagobj->loadTag( $tag );

			// Ensure we loaded the tag's info from the database
			if ($tagobj->id) {
				$tags[] = $tagobj;
			}
		}
		
		// Paging variables
		$limitstart = JRequest::getInt( 'limitstart', 0 );
		$limit = JRequest::getInt( 'limit', 25 );

		// Load plugins
		JPluginHelper::importPlugin( 'tags' );
		$dispatcher =& JDispatcher::getInstance();

		$areas = array();
		$searchareas = $dispatcher->trigger( 'onTagAreas' );
		foreach ($searchareas as $area) 
		{
			$areas = array_merge( $areas, $area );
		}

		// Get the active category
		$area = JRequest::getVar( 'area', '' );

		if (!$area) {
			$t = array();
			foreach ($tags as $tag) 
			{
				$t[] = $tag->tag;
			}
			$url = JRoute::_('index.php?option='.$this->_option.a.'tag='.implode('+',$t));
			$this->_redirect = str_replace('%20','+',$url);
			return;
		}

		// Get the active category
		$activeareas = array($area);
	
		// Get the search results
		$results = $dispatcher->trigger( 'onTagView', array(
				$tags,
				$limit,
				$limitstart,
				$activeareas)
			);
		
		$jconfig =& JFactory::getConfig();
		
		// Run through the array of arrays returned from plugins and find the one that returned results
		$rows = array();
		if ($results) {
			foreach ($results as $result) 
			{
				if (is_array($result) && !empty($result)) {
					$rows = $result;
					break;
				}
			}
		}

		// Build some basic RSS document information
		$title = JText::_(strtoupper($this->_name)) .': ';
		for ($i=0, $n=count( $tags ); $i < $n; $i++) 
		{
			if ($i > 0) {
				$title .= '+ ';
			}
			$title .= $tags[$i]->raw_tag.' ';
		}
		$title = trim($title);
		$title .= ': '.$area;
		
		$doc->title = $jconfig->getValue('config.sitename').' - '.$title;
		$doc->description = JText::sprintf('TAGS_RSS_DESCRIPTION',$jconfig->getValue('config.sitename'), $title);
		$doc->copyright = JText::sprintf('TAGS_RSS_COPYRIGHT', date("Y"), $jconfig->getValue('config.sitename'));
		$doc->category = JText::_('TAGS_RSS_CATEGORY');

		// Start outputing results if any found
		if (count($rows) > 0) {
			include_once( JPATH_ROOT.DS.'components'.DS.'com_resources'.DS.'resources.extended.php' );
			
			foreach ($rows as $row)
			{
				// Prepare the title
				$title = strip_tags($row->title);
				$title = html_entity_decode($title);

				// URL link to article
				//$link = JRoute::_($row->href);

				// Strip html from feed item description text
				$description = html_entity_decode(TagsHtml::cleanText(stripslashes($row->text)));
				$author = '';
				@$date = ( $row->publis_up ? date( 'r', strtotime($row->publish_up) ) : '' );

				if (isset($row->ranking) || isset($row->rating)) {
					$resourceEx = new ResourceExtended($row->id, $database);
					$resourceEx->getCitationsCount();
					$resourceEx->getLastCitationDate();
					$resourceEx->getContributors();

					$author = strip_tags($resourceEx->contributors);
				}

				// Load individual item creator class
				$item = new JFeedItem();
				$item->title       = $title;
				$item->link        = $row->href;
				$item->description = $description;
				$item->date        = $date;
				$item->category    = (isset($row->typetitle)) ? $row->typetitle : '';
				$item->author      = $author;

				// Loads item info into rss array
				$doc->addItem( $item );
			}
		}
		
		// Output the feed
		echo $doc->render();
	}

	//----------------------------------------------------------
	//  Administrative Views
	//----------------------------------------------------------

	protected function browse()
	{
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( JText::_(strtoupper($this->_name)) .': '. JText::_(strtoupper($this->_task)) );
		
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}
		$pathway->addItem(JText::_(strtoupper($this->_task)),'index.php?option='.$this->_option.a.'task='.$this->_task);
		
		// Push some styles to the template
		$this->getStyles();
		
		// Check that the user is authorized
		/*if (!$this->_authorize()) {
			JError::raiseWarning( 404, JText::_('ALERTNOTAUTH') );
			return;
		}*/
		$authorized = $this->_authorize();
		
		// Get configuration
		$config = JFactory::getConfig();
		
		// Incoming
		$filters = array();
		$filters['start']  = JRequest::getInt('limitstart', 0);
		$filters['search'] = urldecode(JRequest::getString('search'));
		
		$database =& JFactory::getDBO();
		$t = new TagsTag( $database );

		// Record count
		$total = $t->getCount( $filters );
		
		$filters['limit']  = JRequest::getInt('limit', $config->getValue('config.list_limit'));
		
		// Get records
		$rows = $t->getRecords( $filters );
		
		// Initiate paging
		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $filters['start'], $filters['limit'] );

		// Output HTML
		echo TagsHtml::browse( $rows, $pageNav, $this->_option, $total, $filters, $authorized );
	}

	//-----------
	
	protected function create() 
	{
		$this->edit();
	}

	//-----------

	protected function edit($tag=NULL)
	{
		// Check that the user is authorized
		if (!$this->_authorize()) {
			JError::raiseWarning( 404, JText::_('ALERTNOTAUTH') );
			return;
		}
		
		// Incoming
		$id = JRequest::getInt( 'id', 0, 'request' );
		
		// Load a tag object if one doesn't already exist
		if (!$tag) {
			$database =& JFactory::getDBO();
			
			$tag = new TagsTag( $database );
			$tag->load( $id );
		}
		
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( JText::_(strtoupper($this->_name)).': '.JText::_(strtoupper($this->_task)) );
		
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}
		$pathway->addItem(JText::_(strtoupper($this->_task)),'index.php?option='.$this->_option.a.'task='.$this->_task);
		
		// Push some styles to the template
		$this->getStyles();
		
		// Output HTML
		echo TagsHtml::edit( JText::_(ucfirst($this->_task)), $tag, $this->_option, $this->getError() );
	}

	//-----------

	protected function cancel()
	{
		$return = JRequest::getVar('return', 'index.php?option='.$this->_option.a.'task=browse', 'get');
		$this->_redirect = JRoute::_( $return );
	}

	//-----------

	protected function save()
	{
		$database =& JFactory::getDBO();

		$row = new TagsTag( $database );
		if (!$row->bind( $_POST )) {
			$this->setError( $row->getError() );
			$this->edit($row);
			return;
		}
		
		$row->raw_tag = trim($row->raw_tag);
		
		$t = new Tags();
		$row->tag = $t->normalize_tag($row->raw_tag);

		// Check content
		if (!$row->check()) {
			$this->setError( $row->getError() );
			$this->edit($row);
			return;
		}

		// Make sure the tag doesn't already exist
		if (!$row->id) {
			if ($row->checkExistence()) {
				$this->setError( JText::_( 'TAG_EXIST' ) );
				$this->edit($row);
				return;
			}
		}

		// Store new content
		if (!$row->store()) {
			$this->setError( $row->getError() );
			$this->edit($row);
			return;
		}
	
		$this->_redirect = JRoute::_( 'index.php?option='.$this->_option.a.'task=browse' );
		//$this->_message = JText::_( 'TAG_SAVED' );
	}

	//-----------

	protected function delete()
	{
		// Check that the user is authorized
		if (!$this->_authorize()) {
			JError::raiseWarning( 404, JText::_('ALERTNOTAUTH') );
			return;
		}
		
		// Incoming
		$ids = JRequest::getVar('id', array());
		if (!is_array( $ids )) {
			$ids = array();
		}
		
		// Make sure we have an ID
		if (empty($ids)) {
			$this->_redirect = JRoute::_( 'index.php?option='.$this->_option.a.'task=browse' );
			return;
		}
		
		// Get Tags plugins
		JPluginHelper::importPlugin('tags');
		$dispatcher =& JDispatcher::getInstance();
		
		$database =& JFactory::getDBO();
		
		foreach ($ids as $id) 
		{
			// Remove references to the tag
			$dispatcher->trigger( 'onTagDelete', array($id) );
			
			// Remove the tag
			$tag = new TagsTag( $database );
			$tag->delete( $id );
		}
	
		$this->_redirect = JRoute::_( 'index.php?option='.$this->_option.a.'task=browse' );
		$this->_message = JText::_( 'TAG_REMOVED' );
	}
	
	//----------------------------------------------------------
	// Private functions
	//----------------------------------------------------------
	
	private function _authorize()
	{
		// Check if they are logged in
		$juser =& JFactory::getUser();
		if ($juser->get('guest')) {
			return false;
		}
		
		// Check if they're a site admin (from Joomla)
		if ($juser->authorize($this->_option, 'manage')) {
			return true;
		}
	
		// Check if they're a site admin (from LDAP)
		$xuser =& XFactory::getUser();
		if (is_object($xuser)) {
			$app =& JFactory::getApplication();
			if (in_array(strtolower($app->getCfg('sitename')), $xuser->get('admin'))) {
				return true;
			}
		}

		return false;
	}
}
?>
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

class MyhubController extends JObject
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
		$task = JRequest::getVar( 'task', '' );
		
		$juser =& JFactory::getUser();
		if ($juser->get('guest')) {
			$task = '';
		} else {
			$task = ($task) ? $task : 'view';
		}
		
		$this->_task = $task;
		return $task;
	}
	
	//-----------
	
	public function execute()
	{
		// Load the component config
		$config =& JComponentHelper::getParams( $this->_option );
		$this->config = $config;
		
		$this->num_default = 6;
		
		switch ($this->getTask()) 
		{
			case 'refresh':    $this->refresh();    break;
			case 'rebuild':    $this->rebuild();    break;
			case 'restore':    $this->restore();    break;
			case 'save':       $this->save();       break;
			case 'saveparams': $this->saveparams(); break;
			case 'addmodule':  $this->addmodule();  break;
			case 'view':       $this->view();       break;
	
			default: $this->restricted(); break;
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

	//----------------------------------------------------------
	// Views
	//----------------------------------------------------------
	
	// Display a login form
	protected function restricted()
	{
		ximport('xmodule');
		
		$jconfig =& JFactory::getConfig();
		$juser =& JFactory::getUser();
		
		// Set the pathway
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}
		
		// Output HTML
		echo MyhubHtml::writeTitle( $jconfig->getValue('config.sitename'), $juser->get('name'), 'full' );
		echo '<div class="main section">'.n;
		XModuleHelper::displayModules('force_mod');
		echo '</div><!-- / .main section -->'.n;
	}

	//-----------
	// Restore the "My HUB" page to default settings
	protected function restore()
	{
		// Load the user's info
		$juser =& JFactory::getUser();
		
		// Ensure we found a user
		if (!$juser->get('guest')) {
			$database =& JFactory::getDBO();

			// Instantiate object, assign default preferences, and save
			$myhub = new MyhubPrefs( $database );
			$myhub->load( $juser->get('id') );
			$myhub->prefs = $this->getDefaultModules();
			if (!$myhub->check()) {
				$this->setError( $myhub->getError() );
			}
			if (!$myhub->store()) {
				$this->setError( $myhub->getError() );
			}
		}
		
		$this->view();
	}

	//-----------
	// The main "My HUB" page
	protected function view()
	{
		// Add the CSS
		ximport('xdocument');
		XDocument::addComponentStylesheet($this->_option);
		
		// Add the Javascript if in "personalize" mode
		$act = JRequest::getVar( 'act', '' );
	    if ($act == 'customize') {
			$document =& JFactory::getDocument();
			$document->addScript('components'.DS.$this->_option.DS.'xsortables.js');
			$document->addScript('components'.DS.$this->_option.DS.'myhub.js');
		}
		
		// Set the pathway
		$app =& JFactory::getApplication();
		$pathway =& $app->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem(JText::_(strtoupper($this->_name)),'index.php?option='.$this->_option);
		}
		if ($act == 'customize') {
			$pathway->addItem(JText::_('PERSONALIZE'),'index.php?option='.$this->_option.a.'act=customize');
		}
		
		// Load the current logged-in user
		$juser =& JFactory::getUser();
		
		// Make sure we actually loaded someone
		if ($juser->get('guest')) {
			echo MyhubHtml::error( JText::_('ERROR_NO_USER') );
			return;
		}
		
		$database =& JFactory::getDBO();
		
		// Select user's list of modules from database
		$myhub = new MyhubPrefs( $database );
		$myhub->load( $juser->get('id') );
		
		// No preferences found
		if (trim($myhub->prefs) == '') {
			// Create a default set of preferences
			$myhub->uid = $juser->get('id');
			$myhub->prefs = $this->getDefaultModules();
			if ($act == 'customize' && $this->config->get('allow_customization') != 1) {
				if (!$myhub->check()) {
					$this->setError( $myhub->getError() );
				}
				if (!$myhub->create()) {
					$this->setError( $myhub->getError() );
				}
			}
		}
	
		// Splits string into columns
		$mymods = split(';',$myhub->prefs);
		// Save array of columns for later work
		$usermods = $mymods;
	
		// Splits each column into modules, listed by the order they should appear
		for ( $i = 0; $i < count($mymods); $i++ ) 
		{
			$mymods[$i] = split(',',$mymods[$i]);
		}

		// Build a list of all modules being used by this user 
		// so we know what to exclude from the list of modules they can still add
		$allmods = array();
		for ( $i = 0; $i < count($mymods); $i++ ) 
		{
			$allmods = array_merge($allmods, $mymods[$i]);
		}

		$jconfig =& JFactory::getConfig();

		// The number of columns
		$cols = 3;

		// Build HTML
		$html  = MyhubHtml::writeTitle( $jconfig->getValue('config.sitename'), $juser->get('name') );
		if (!$this->config->get('allow_customization')) {
			$html .= MyhubHtml::writeOptions( $this->_option, $act );
		}
		$html .= '<div class="main section">'.n;
		$html .= '<table id="droppables">'.n;
		$html .= t.'<tbody>'.n;
		$html .= t.t.'<tr>'.n;
		
		// Initialize customization abilities
		if ($act == 'customize' && $this->config->get('allow_customization') != 1) {
			$availmods = $this->getUnusedModules($allmods);
		
			// Get the control panel
			$html .= t.t.t.'<td id="modules-dock">'.n;
			$html .= MyhubHtml::controlpanel($this->_option, $availmods, $usermods, $juser->get('id'));
			$html .= t.t.t.'</td>'.n;
			//$html .= t.t.t.'<td>'.n;
		} else {
			$html .= '<input type="hidden" name="uid" id="uid" value="'.$juser->get('id').'" />'.n;
		}

		// Loop through each column and output modules assigned to each one
		for ( $c = 0; $c < $cols; $c++ ) 
		{
			$html .= t.t.t.'<td class="sortable" id="sortcol_'.$c.'">'.n;
			$html .= $this->output_modules($mymods[$c], $juser->get('id'), $act);
			$html .= t.t.t.'</td>'.n;
		}
		//$html .= '<div class="clear"></div>'.n;
	
		// Add the end of our table if in customize mode
		/*if ($act == 'customize') {
			$html .= t.t.t.'</td>'.n;
		}*/
		$html .= t.t.'</tr>'.n;
		$html .= t.'</tbody>'.n;
		$html .= '</table>'.n;
		
		$html .= '</div><!-- / .main section -->'.n;
		
		echo $html;
	}

	//----------------------------------------------------------
	// AJAX calls
	//----------------------------------------------------------
	
	// Rebuild the "available modules" list
	protected function rebuild()
	{
		$id  = $this->save(1);
		
		$ids = split(';',$id);
		for ( $i = 0; $i < count($ids); $i++ ) 
		{
			$ids[$i] = split(',',$ids[$i]);
		}
		
		$allmods = array();
		for ( $i = 0; $i < count($ids); $i++ ) 
		{
			$allmods = array_merge($allmods, $ids[$i]);
		}

		$modules = $this->getUnusedModules($allmods);
		
		echo MyhubHtml::moduleList( $modules );
	}

	//-----------
	// Save preferences (i.e., the list of modules 
	// to be displayed and their locations)
	protected function save( $rtrn=0 )
	{
		// Incoming
		$uid = JRequest::getInt( 'uid', 0 );
		$ids = JRequest::getVar( 'ids', '' );
	
		// Ensure we have a user ID ($uid)
		if (!$uid) {
			if ($rtrn) {
				return $ids;
			}
		}
		
		$database =& JFactory::getDBO();
		
		// Instantiate object, bind data, and save
		$myhub = new MyhubPrefs( $database );
		$myhub->load( $uid );
		$myhub->prefs = $ids;
		$myhub->modified = date( "Y-m-d H:i:s" );
		if (!$myhub->check()) {
			$this->setError( $myhub->getError() );
		}
		if (!$myhub->store()) {
			$this->setError( $myhub->getError() );
		}
		if ($rtrn) {
			return $ids;
		}
	}

	//-----------
	// Save the parameters for a module
	protected function saveparams()
	{
		$database =& JFactory::getDBO();
		
		// Incoming
		$uid = JRequest::getInt( 'uid', 0 );
		$mid = JRequest::getVar( 'id', '' );
		$params = JRequest::getVar( 'params', array() );
		
		// Process parameters
		$newparams = array();
		foreach ($params as $aKey => $aValue)
		{
			$newparams[] = $aKey.'='.$aValue;
		}

		// Instantiate object, bind data, and save
		$myparams = new MyhubParams( $database );
		$myparams->loadParams( $uid, $mid );
		if (!$myparams->params) {
			$myparams->uid = $uid;
			$myparams->mid = $mid;
			$new = true;
		} else {
			$new = false;
		}
		$myparams->params = implode($newparams,"\n");
		if (!$myparams->check()) {
			$this->setError( $myparams->getError() );
		}
		if (!$myparams->storeParams($new)) {
			$this->setError( $myparams->getError() );
		}
	}
	
	//-----------

	protected function getmodule( $extras=false, $act='' ) 
	{
		$database =& JFactory::getDBO();
		
		// Incoming
		$mid = JRequest::getInt( 'id', 0 );
		$uid = JRequest::getInt( 'uid', 0 );
		
		// Make sure we have a module ID to load
		if (!$mid) {
			echo MyhubHtml::error( JText::_('ERROR_NO_MOD_ID') );
			return;
		}
		
		// Get the module from the database
		$myparams = new MyhubParams( $database );
		$module = $myparams->loadModule( $uid, $mid );

		// If the user has special prefs, load them.
		// Otherwise, load default prefs
		if ($module->myparams != '') {
			$params =& new JParameter( $module->myparams );
		} else {
			$params =& new JParameter( $module->params );
		}
		
		if ($params) {
			$rendered = false; //$this->render( $params, $mainframe->getPath( 'mod0_xml', $module->module ), 'module' );
		}
		
		//$module->user = 0;

		// Is it a custom module (i.e., HTML)?
		echo MyhubHtml::moduleContainer( $module, $params, $rendered, false, $extras, $database, $this->_option, $this->config, $act );
	}
	
	//-----------
	// Reload the contents of a module
	protected function refresh()
	{
		$this->getmodule( false, '' );
	}
	
	//-----------
	// Builds the HTML for a module
	// NOTE: this is different from the method above in that
	// it also builds the title, parameters form, and container
	// for the module
	protected function addmodule()
	{
		$this->getmodule( true, 'customize' );
	}

	//----------------------------------------------------------
	// outputs a group of modules, each contained in a list item
	//----------------------------------------------------------

	protected function output_modules($mods, $uid, $act='') 
	{
		$database =& JFactory::getDBO();
		
		// Get the modules
		$modules = array();
		for ($i=0, $n=count( $mods ); $i < $n; $i++) 
		{
			$myparams = new MyhubParams( $database );
			$modules[] = $myparams->loadModule( $uid, $mods[$i] );
		}

		$html = '';

		// Loop through the modules and output
		foreach ($modules as $module) 
		{
			if (isset($module->published)) {
				if ($module->published != 1) {
					continue;
				}
				
				$rendered = false;
				// if the user has special prefs, load them. Otherwise, load default prefs
				if ($module->myparams != '') {
					$params =& new JParameter( $module->myparams );
					$module->params .= $module->myparams;
				} else {
					$params =& new JParameter( $module->params );
				}

				if ($params) {
					$rendered = false; //$this->render( $params, $mainframe->getPath( 'mod0_xml', $module->module ), 'module' );
				}

				$html .= MyhubHtml::moduleContainer( $module, $params, $rendered, true, true, $database, $this->_option, $this->config, $act );
			}
		}
		
		return $html;
	}

	//----------------------------------------------------------
	// Fetchers
	//----------------------------------------------------------

	private function getUnusedModules($mods)
	{
		$database =& JFactory::getDBO();
	
		//jimport('joomla.database.table.module');
		include_once( JPATH_ROOT.DS.'libraries'.DS.'joomla'.DS.'database'.DS.'table'.DS.'module.php' );
		$jmodule = new JTableModule( $database );
	
		$position = ($this->config->get('position')) ? $this->config->get('position') : 'myhub';
	
		$querym = '';
		$query = "SELECT id, title, module"
				. " FROM ".$jmodule->getTableName()." AS m"
				. " WHERE m.position='".$position."' AND m.published='1' AND m.client_id='0' AND (";
		for ($i=0, $n=count( $mods ); $i < $n; $i++) 
		{
			$querym .= " id!='".$mods[$i]."' AND";
		}
		$querym = substr($querym, 0, strlen($querym)-4);
		$query .= $querym;
		$query .= ") ORDER BY ordering";

		$database->setQuery( $query );
		$modules = $database->loadObjectList();
	
		return $modules;
	}

	//-----------

	private function getDefaultModules()
	{
		$string = '';
		
		if ($this->config->get('defaults')) {
			$string = $this->config->get('defaults');
		} else {
			$database =& JFactory::getDBO();
			
			$position = ($this->config->get('position')) ? $this->config->get('position') : 'myhub';
			
			include_once( JPATH_ROOT.DS.'libraries'.DS.'joomla'.DS.'database'.DS.'table'.DS.'module.php' );
			$jmodule = new JTableModule( $database );
			
			$query = "SELECT m.id 
						FROM ".$jmodule->getTableName()." AS m 
						WHERE m.position='".$position."' AND m.published='1' AND m.client_id='0' 
						ORDER BY m.ordering LIMIT ".$this->num_default;
			$database->setQuery( $query );
			$modules = $database->loadObjectList();

			if ($modules) {
				$i = 0;
				foreach ($modules as $module)
				{
					$i++;
					$string .= $module->id;
					if ($i == 2) {
						$i = 0;
						$string .= ';';
					} else {
						$string .= ',';
					}
				}
				$string = substr($string, 0, strlen($string)-1);
			}
		}
		
		return $string;
	}

	//----------------------------------------------------------
	// Functions for getting user options/parameters for modules
	// TODO: finish this
	//----------------------------------------------------------

	/*private function render( $params, $path='', $type='', $name='params' ) 
	{
		$xmlElem = NULL;
		if ($path) {
			if (!is_object( $xmlElem )) {
				require_once( JPATH_ROOT.DS.'includes/domit/xml_domit_lite_include.php' );

				$xmlDoc =& new DOMIT_Lite_Document();
				$xmlDoc->resolveErrors( true );
				if ($xmlDoc->loadXML( $path, false, true )) {
					$element =& $xmlDoc->documentElement;

					if ($element->getTagName() == 'install' && $element->getAttribute( "type" ) == $type) {
						if ($element =& $xmlDoc->getElementsByPath( 'params', 1 )) {
							$xmlElem =& $element;
						}
					}
				}
			}
		}
	
		$html = '';
		if (is_object( $xmlElem )) {
			$element =& $xmlElem;

			foreach ($element->childNodes as $param) 
			{
				$result = $this->param_render( $params, $param );
				if ($result) {
					if ($result[0] != '') {
						$html .= ' <tr>'.n;
						$html .= '  <td>' . $result[0] . '</td>'.n;
						$html .= '  <td>' . $result[1] . '</td>'.n;
						$html .= ' </tr>'.n;
					}
				}
			}
			if (count( $element->childNodes ) < 1) {
				$html .= '<tr><td colspan="2"><i>'. JText::_('NO_PARAMS') .'</i></td></tr>';
			}
			if ($html != '') {
				$html  = '<table class="paramlist">'.n.$html;
				$html .= '</table>'.n;
			}
		}
		return $html;
	}

	//-----------

	private function param_get( $params, $key, $default='' ) 
	{
		if (isset( $params->_params->$key )) {
			return $params->_params->$key === '' ? $default : $params->_params->$key;
		} else {
			return $default;
		}
	}

	//-----------

	private function param_render( $params, &$param ) 
	{
		$result = array();

		$name = $param->getAttribute( 'name' );
		if (substr($name,0,3) == 'my_') {
			$label = $param->getAttribute( 'label' );

			$default = $param->getAttribute( 'default' );
			$value = $this->param_get( $params, $name, $param->getAttribute( 'default' ) );

			$result[0] = $label ? $label : $name;
			if ( $result[0] == '@spacer' ) {
				$result[0] = '<hr />';
			} else if ( $result[0] ) {
				$result[0] .= ':';
			}

			$type = $param->getAttribute( 'type' );

			$param->_methods = get_class_methods( 'JParameter' );
		
			if (in_array( '_form_' . $type, $param->_methods )) {
				$result[1] = call_user_func( array( 'JParameter','_form_' . $type), $name, $value, $param );
			} else {
			    $result[1] = _HANDLER . ' = ' . $type;
			}
		}

		return $result;
	}*/
}
?>
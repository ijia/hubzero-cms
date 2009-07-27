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

//-----------

jimport( 'joomla.plugin.plugin' );
JPlugin::loadLanguage( 'plg_usage_tools' );

//-----------

class plgUsageTools extends JPlugin
{
	public function plgUsageTools(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// load plugin parameters
		$this->_plugin = JPluginHelper::getPlugin( 'usage', 'tools' );
		$this->_params = new JParameter( $this->_plugin->params );
	}

	//-----------

	public function onUsageAreas()
	{
		$areas = array(
			'tools' => JText::_('USAGE_TOOLs')
		);
		return $areas;
	}
	
	//-----------
	
	private function gettoplist($database, $period, $dthis, $s_top) 
	{
		$html = '';
		
		$sql = "SELECT * FROM #__stats_topvals WHERE top = '".$s_top."' AND datetime = '".$dthis."-00' AND period = '".$period."' ORDER BY rank";
		$database->setQuery( $sql );
		$results = $database->loadObjectList();
		
		$cls = 'even';
		$html .= t.'<thead>'.n;
		$html .= t.t.'<tr>'.n;
		$html .= t.t.t.'<th class="numerical-data">'.JText::_('#').'</th>'.n;
		$html .= t.t.t.'<th>'.JText::_('Tool').'</th>'.n;
		$html .= t.t.t.'<th class="numerical-data">'.JText::_('Value').'</th>'.n;
		$html .= t.t.t.'<th class="numerical-data">'.JText::_('Percent').'</th>'.n;
		$html .= t.t.'</tr>'.n;
		$html .= t.'</thead>'.n;
		
		if ($results) {
			foreach ($results as $row)
			{
				$cls = ($cls == 'even') ? 'odd' : 'even';
				if ($row->rank == '0') {
					$total = $row->value;
					if ($s_top == "6" || $s_top == "7" || $s_top == "8") {
						$value = $this->time_units($row->value);
					} else {
						$value = number_format($row->value);
					}
					$html .= t.'<tfoot>'.n;
					$html .= t.t.'<tr class="summary">'.n;
					$html .= t.t.t.'<th colspan="2" class="numerical-data">'.$row->name.'</th>'.n;
					$html .= t.t.t.'<th class="numerical-data">'.$value.'</th>'.n;
					$html .= t.t.t.'<th class="numerical-data">'.round((($row->value/$total)*100),2).'%</th>'.n;
					$html .= t.t.'</tr>'.n;
					$html .= t.'</tfoot>'.n;
					$html .= t.'<tbody>'.n;
				} else {
					$name = preg_split('/ ~ /',$row->name);
					if ($s_top == "6" || $s_top == "7" || $s_top == "8") {
						$value = $this->time_units($row->value);
					} else {
						$value = number_format($row->value);
					}
					$html .= t.t.'<tr class="'.$cls.'">'.n;
					$html .= t.t.t.'<td>'.$row->rank.'</td>'.n;
					$html .= t.t.t.'<td class="textual-data"><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task=tools'.a.'id='.$name[0].a.'period='.$period).'">'.$name[1].'</a></td>'.n;
					$html .= t.t.t.'<td>'.$value.'</td>'.n;
					$html .= t.t.t.'<td>'.round((($row->value/$total)*100),2).'%</td>'.n;
					$html .= t.t.'</tr>'.n;
				}
			}
		} else {
			$html .= t.'<tbody>'.n;
			$html .= t.t.'<tr class="odd">'.n;
			$html .= t.t.t.'<td colspan="4">Data being generated. Please check back soon.</td>'.n;
			$html .= t.t.'</tr>'.n;
		}
		$html .= t.'</tbody>'.n;
		
		return $html;
	}
	
	//-----------

	private function gettoprank_tools($database) 
	{
		$html = '';
		$count = 1;
		
		$sql = 'SELECT DISTINCT id, title, published, ranking FROM #__resources WHERE published = "1" AND standalone = "1" AND type = "7" AND access != "4" AND access != "1" ORDER BY ranking DESC';
		$database->setQuery( $sql );
		$results = $database->loadObjectList();
		
		if ($results) {
			$cls = 'even';
			$html .= t.'<thead>'.n;
			$html .= t.t.'<tr>'.n;
			$html .= t.t.t.'<th class="numerical-data">'.JText::_('#').'</th>'.n;
			$html .= t.t.t.'<th>'.JText::_('Tool').'</th>'.n;
			$html .= t.t.t.'<th class="numerical-data">'.JText::_('Ranking').'</th>'.n;
			$html .= t.t.'</tr>'.n;
			$html .= t.'</thead>'.n;
			$html .= t.'<tbody>'.n;
			foreach ($results as $row) 
			{
				$cls = ($cls == 'even') ? 'odd' : 'even';
				$ranking = round($row->ranking,2);
				if ($row->published == "1") {
					$html .= t.t.'<tr class="'.$cls.'">'.n;
					$html .= t.t.t.'<td>'.$count.'</td>'.n;
					$html .= t.t.t.'<td class="textual-data"><a href="'.JRoute::_('index.php?option=com_resources'.a.'id='.$row->id.a.'active=usage').'">'.$row->title.'</a></td>'.n;
					$html .= t.t.t.'<td>'.$ranking.'</td>'.n;
					$html .= t.t.'</tr>'.n;
				} else {
					$html .= t.t.'<tr class="'.$cls.'">'.n;
					$html .= t.t.t.'<td>'.$count.'</td>'.n;
					$html .= t.t.t.'<td class="textual-data">'.$row->title.'</td>'.n;
					$html .= t.t.t.'<td>'.$ranking.'</td>'.n;
					$html .= t.t.'</tr>'.n;
				}
				$count++;
			}
			$html .= t.'</tbody>'.n;
		}
		
		return $html;
	}

	//-----------

	private function gettopcited_tools($database) 
	{
		$html = '';
		
		$sql = 'SELECT COUNT(DISTINCT c.id) FROM #__resources r, #__citations c, #__citations_assoc ca WHERE r.id = ca.oid AND ca.cid = c.id AND ca.table = "resource" AND r.type = "7" AND standalone = "1"';
		$database->setQuery( $sql );
		$result = $database->loadResult();
		
		$html .= t.'<thead>'.n;
		$html .= t.t.'<tr>'.n;
		$html .= t.t.t.'<th class="numerical-data">'.JText::_('#').'</th>'.n;
		$html .= t.t.t.'<th>'.JText::_('Tool').'</th>'.n;
		$html .= t.t.t.'<th class="numerical-data">'.JText::_('Citations').'</th>'.n;
		$html .= t.t.'</tr>'.n;
		$html .= t.'</thead>'.n;
		
		if ($result) {
			$html .= t.'<tfoot>'.n;
			$html .= t.t.'<tr class="summary">'.n;
			$html .= t.t.t.'<th colspan="2" class="numerical-data">'.JText::_('Total Tools Citations').'</th>'.n;
			$html .= t.t.t.'<td class="numerical-data">'.$result.'</td>'.n;
			$html .= t.t.'</tr>'.n;
			$html .= t.'</tfoot>'.n;
		}
		
		$count = 1;
		$sql = 'SELECT DISTINCT r.id, r.title, r.published, COUNT(c.id) AS citations FROM #__resources r, #__citations c, #__citations_assoc ca WHERE r.id = ca.oid AND ca.cid = c.id AND ca.table = "resource" AND r.type = "7" AND standalone = "1" GROUP BY r.id ORDER BY citations DESC';
		$database->setQuery( $sql );
		$results = $database->loadObjectList();
		
		if ($results) {
			$cls = 'even';
			$html .= t.'<tbody>'.n;
			foreach ($results as $row) 
			{
				$cls = ($cls == 'even') ? 'odd' : 'even';
				
				if ($row->published == "1") {
					$html .= t.t.'<tr class="'.$cls.'">'.n;
					$html .= t.t.t.'<td>'.$count.'</td>'.n;
					$html .= t.t.t.'<td class="textual-data"><a href="'.JRoute::_('index.php?option=com_resources'.a.'id='.$row->id.a.'active=usage').'">'.stripslashes($row->title).'</a></td>';
					$html .= t.t.t.'<td>'.$row->citations.'</td>'.n;
					$html .= t.t.'</tr>'.n;
				} else {
					$html .= t.t.'<tr class="'.$cls.'">'.n;
					$html .= t.t.t.'<td>'.$count.'</td>'.n;
					$html .= t.t.t.'<td class="textual-data">'.stripslashes($row->title).'</td>';
					$html .= t.t.t.'<td>'.$row->citations.'</td>'.n;
					$html .= t.t.'</tr>'.n;
				}
				$count++;
	    	}
			$html .= t.'</tbody>'.n;
		}
		
		return $html;
	}
	
	//-----------
	
	private function time_units($time) 
	{
		if ($time < 60) {
			$data = $time." seconds";
		} else if ($time > 60 && $time < 3600) {
			$data = number_format(($time/60), 2).' minutes';
		} else if ($time >= 3600 && $time < 86400) {
			$data = number_format(($time/3600), 2).' hours';
		} else if ($time >= 86400) {
			$data = number_format(($time/86400),2).' days';
		}

		return $data;
	}
	
	//-----------

	private function drop_down_dates(&$db, $period, $s_top, $dthis) 
	{
		$months = array( "01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr", "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Aug", "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec");
		$monthsReverse = array_reverse($months, TRUE);
		$cur_year = floor(date("Y"));
		$cur_month = floor(date("n"));
		$year_data_start = 2000;

		$html = '<select name="dthis">'.n;
		switch ($period) 
		{
			case '3':
				$qtd_found = 0;
				foreach ($monthsReverse as $key => $month) 
				{
					$value = $cur_year . '-' . $key;
					if (!$qtd_found && $this->check_for_data($value, 3)) {
						$html .= '<option value="' . $value . '"';
						if ($value == $dthis) {
							$html .= ' selected="selected"';
						}
						$html .= '>';
						if ($key <= 3) {
							$key = 0;
							$html .= 'Jan';
						} elseif ($key <= 6) {
							$key = 3;
							$html .= 'Apr';
						} elseif ($key <= 9) {
							$key = 6;
							$html .= 'Jul';
						} else {
							$key = 9;
							$html .= 'Oct';
						}
						$html .= ' ' . $cur_year . ' - ' . $month . ' ' . $cur_year . '</option>'.n;
						$qtd_found = 1;
					}
				}
				for ($j = $cur_year; $j >= $year_data_start; $j--) 
				{
					for ($i = 12; $i > 0; $i = $i - 3) 
					{
						$value = $j . '-' . sprintf("%02d", $i);
						if ($this->check_for_data($value, 3)) {
							$html .= '<option value="' . $value . '"';
							if ($value == $dthis) {
								$html .= ' selected="selected"';
							}
							$html .= '>';
							switch ($i) 
							{
								case 3:  $html .= 'Jan'; break;
								case 6:  $html .= 'Apr'; break;
								case 9:  $html .= 'Jul'; break;
								default: $html .= 'Oct'; break;
							}
							$html .= ' ' . $j . ' - ';
							switch ($i) 
							{
								case 3:  $html .= 'Mar'; break;
								case 6:  $html .= 'Jun'; break;
								case 9:  $html .= 'Sep'; break;
								default: $html .= 'Dec'; break;
							}
							$html .= ' ' . $j . '</option>'.n;
						}
					}
				}
			break;
			
			case '12':
				$arrayMonths = array_values($months);
				for ($i = $cur_year; $i >= $year_data_start; $i--) 
				{
					foreach ($monthsReverse as $key => $month) 
					{
						if ($key == '12') {
							$nextmonth = 'Jan';
						} else {
							$nextmonth = $arrayMonths[floor(array_search($month, $arrayMonths))+1];
						}
						$value = $i . '-' . $key;
						if ($this->check_for_data($value, 12)) {
							$html .= '<option value="' . $value . '"';
							if ($value == $dthis) {
								$html .= ' selected="selected"';
							}
							$html .= '>' . $nextmonth . ' ';
							if ($key == 12) {
								$html .= $i;
							} else {
								$html .= $i - 1;
							}
						   	$html .= ' - ' . $month . ' ' . $i . '</option>'.n;
						}
					}
				}
			break;
			
			case '1':
			case '14':
				for ($i = $cur_year; $i >= $year_data_start; $i--) 
				{
					foreach ($monthsReverse as $key => $month) 
					{
						$value = $i . '-' . $key;
						if ($this->check_for_data($value, 1)) {
							$html .= '<option value="' . $value . '"';
							if ($value == $dthis) {
								$html .= ' selected="selected"';
							}
							$html .= '>' . $month . ' ' . $i . '</option>'.n;
						}
					}
				}
			break;
			
			case '0':
				$ytd_found = 0;
				foreach ($monthsReverse as $key => $month) 
				{
					$value = $cur_year . '-' . $key;
					if (!$ytd_found && $this->check_for_data($value, 0)) {
						$html .= '<option value="' . $value . '"';
						if ($value == $dthis) {
							$html .= ' selected="selected"';
						}
						$html .= '>Jan - ' . $month . ' ' . $cur_year . '</option>'.n;
						$ytd_found = 1;
					}
				}
				for ($i = $cur_year - 1; $i >= $year_data_start; $i--) 
				{
					$value = $i . '-12';
					if ($this->check_for_data($value, 0)) {
						$html .= '<option value="' . $value . '"';
						if ($value == $dthis) {
							$html .= ' selected="selected"';
						}
						$html .= '>Jan - Dec ' . $i . '</option>'.n;
					}
				}
			break;
			
			case '13':
				$ytd_found = 0;
				foreach ($monthsReverse as $key => $month) 
				{
					$value = $cur_year . '-' . $key;
					if (!$ytd_found && $this->check_for_data($value, 0)) {
						$html .= '<option value="' . $value . '"';
						if ($value == $dthis) {
							$html .= ' selected="selected"';
						}
						$html .= '>Oct ';
						if ($cur_month >= 9) {
							$html .= $cur_year;
							$full_year = $cur_year;
						} else {
							$html .= $cur_year - 1;
							$full_year = $cur_year - 1;
						}
						$html .= ' - ' . $month . ' ' . $cur_year . '</option>'.n;
						$ytd_found = 1;
					}
				}
				for ($i = $full_year; $i >= $year_data_start; $i--) 
				{
					$value = $i . '-09';
					if ($this->check_for_data($value, 0)) {
						$html .= '<option value="' . $value . '"';
						if ($value == $dthis) {
							$html .= ' selected="selected"';
						}
						$html .= '>Oct ';
						$html .= $i - 1;
						$html .= ' - Sep ' . $i . '</option>'.n;
					}
				}
			break;
		}
		$html .= '</select>'.n;
		
		return $html;
	}

	//-------------------------------------------------
	//  Returns TRUE if there is data in the database
	//  for the date passed to it, FALSE otherwise.
	//-------------------------------------------------
	
	private function check_for_data($yearmonth, $period) 
	{
		$database =& JFactory::getDBO();
		
	    $sql = "SELECT COUNT(datetime) FROM #__stats_topvals WHERE datetime LIKE '" . mysql_escape_string($yearmonth) . "-%' AND period = '" . mysql_escape_string($period) . "'";
		$database->setQuery( $sql );
		$result = $database->loadResult();
		
		if ($result && $result > 0) {
			return(true);
		}
	
		return(false);
	}

	//-----------

	private function navlinks($period='12',$top='') 
	{
		$html  = '<div id="sub-sub-menu">'.n;
		$html .= t.'<ul>'.n;
		$html .= t.t.'<li';    
		if ($period == 'prior12' || $period == '12') {
			$html .= ' class="active"';
		}
		$html .= '><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period=12'.a.'top='.$top).'"><span>'.JText::_('USAGE_PERIOD_PRIOR12').'</span></a></li>'.n;
		$html .= t.t.'<li';  
	    if ($period == 'month' || $period == '1') {
			$html .= ' class="active"';
		}
		$html .= '><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period=1'.a.'top='.$top).'"><span>'.JText::_('USAGE_PERIOD_MONTH').'</span></a></li>'.n;
		$html .= t.t.'<li';  
	    if ($period == 'qtr' || $period == '3') {
			$html .= ' class="active"';
		}
	    $html .= '><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period=3'.a.'top='.$top).'"><span>'.JText::_('USAGE_PERIOD_QTR').'</span></a></li>'.n;
		$html .= t.t.'<li';  
		if ($period == 'year' || $period == '0') {
			$html .= ' class="active"';
		}
		$html .= '><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period=0'.a.'top='.$top).'"><span>'.JText::_('USAGE_PERIOD_YEAR').'</span></a></li>'.n;
		$html .= t.t.'<li';  
		if ($period == 'fiscal' || $period == '13') {
			$html .= ' class="active"';
		}
		$html .= '><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period=13'.a.'top='.$top).'"><span>'.JText::_('USAGE_PERIOD_FISCAL').'</span></a></li>'.n;
		$html .= t.t.'<li';  
		if ($period == '14') {
			$html .= ' class="active"';
		}
		$html .= '><a href="'.JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period=14'.a.'top='.$top).'"><span>'.JText::_('USAGE_PERIOD_OVERALL').'</span></a></li>'.n;
		$html .= t.'</ul>'.n;
		$html .= '</div>'.n;

	    return $html;
	}
	
	//-----------

	public function onUsageDisplay( $option, $task, $db, $months, $monthsReverse, $enddate ) 
	{
		// Check if our task is the area we want to return results for
		if ($task) {
			if (!in_array( $task, $this->onUsageAreas() ) 
			 && !in_array( $task, array_keys( $this->onUsageAreas() ) )) {
				return '';
			}
		}
		
		$database =& JFactory::getDBO();
		
		// Ensure the database table(s) exist
		$tables = $database->getTableList();
		$table = $database->_table_prefix.'stats_tops';
		if (!in_array($table,$tables)) {
			return UsageHtml::error( JText::_('Error: Required database table not found.') );
		}
		
		// Set some vars
		$this->_option = $option;
		$this->_task = $task;
		
		// Incoming
		$period = JRequest::getVar( 'period', '12' );
		$dthis  = JRequest::getVar( 'dthis', date('Y').'-'.date('m') );
		$s_top  = JRequest::getVar( 'top', '1' );

		// Build the HTML
		$html  = $this->navlinks($period, $s_top);
		$html .= '<form method="post" action="'. JRoute::_('index.php?option='.$this->_option.a.'task='.$this->_task.a.'period='.$period) .'">'.n;
		$html .= t.'<fieldset class="filters">'.n;
		$html .= t.t.'<label>'.n;
		$html .= t.t.t.JText::_('USAGE_SHOW_DATA_FOR').': '.n;
		$html .= t.t.t.'<select name="top">'.n;

		$sql = "SELECT DISTINCT id, name FROM #__stats_tops ORDER BY id"; 
		$database->setQuery( $sql );
		$results = $database->loadObjectList();
		if ($results) {
			foreach ($results as $row) 
			{
				$top = $row->id;
				$data[$top]['id'] = $row->id;
				$data[$top]['name'] = $row->name;
				if ($s_top == $top) {
					$html .= t.t.t.t.'<option value="'. $data[$top]['id'].'" selected="selected">'. htmlentities($data[$top]['name']) .'</option>'.n;
				} else {
					$html .= t.t.t.t.'<option value="'. $data[$top]['id'].'">'. htmlentities($data[$top]['name']) .'</option>'.n;
				}
			}
		}
		
		$html .= t.t.t.'</select>'.n;
		$html .= t.t.'</label> '.n;
		$html .= $this->drop_down_dates($database, $period, $s_top, $dthis).' ';
		$html .= t.t.'<input type="submit" value="'.JText::_('USAGE_VIEW').'" />'.n;
		$html .= t.'</fieldset>'.n;
		$html .= '</form>'.n;

		if ($s_top) {
			$html .= '<table summary="'.$data[$s_top]['name'].'">'.n;
			$html .= t.'<caption>'.$data[$s_top]['name'].'</caption>'.n;
			if ($s_top == '9') {
				$html .= $this->gettopcited_tools($database);
			} else if ($s_top == "1") {
				$html .= $this->gettoprank_tools($database);
			} else {
				$html .= $this->gettoplist($database, $period, $dthis, $s_top);
			}
			$html .= '</table>'.n;
		} else {
			$html .= '<p>'.JText::_('Please make a selection to view data.').'</p>'.n;
		}
		
		return $html;
	}
}
?>
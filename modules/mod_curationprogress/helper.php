<?php
/**
* @version		$Id: helper.php 11668 2009-03-08 20:33:38Z willebil $
* @package		Joomla
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined('_JEXEC') or die('Restricted access');
require 'CurationProgress.php';
class modCurationProgressHelper{
	
	public function getCurationStatus()
	{
		$oCurationProgress = new CurationProgress();
		if(JRequest::getVar('view') == 'project')
			$curation_status = $oCurationProgress->getProjectCurationProgress();
		else if (JRequest::getVar('view') == 'experiment')
			$curation_status = $oCurationProgress->getExperimentCurationProgress();	
		return $curation_status;
	}
}

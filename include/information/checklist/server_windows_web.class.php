<?php

/**
 * include/information/*.class.php
 *
 * Extension leveraging the information repository
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 * @package   none
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

require_once "information/checklist/server_windows.class.php";

class Checklist_Server_Windows_Web	extends Checklist_Server_Windows
{
	public $type = "Checklist_Server_Windows_Web";

	public function reinitialize()
	{
		// Add more sub-type specific checklist items here!
		$TASKS = array(
			"Install IIS Service",
			"Make sure .net is Installed",
			"Create L: Drive and redirect logs",
			"Configure Logs Cleanup Script",
		);
		$this->addtasks($TASKS);
	}
}

?>
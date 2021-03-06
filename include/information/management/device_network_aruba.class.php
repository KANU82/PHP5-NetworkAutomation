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

require_once "information/management/device_network.class.php";

class Management_Device_Network_Aruba	extends Management_Device_Network
{
	public $type = "Management_Device_Network_Aruba";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"		,"stringfield0");
		$CHANGED += $this->customfield("ip"			,"stringfield1");
		$CHANGED += $this->customfield("protocol"	,"stringfield2");
		$CHANGED += $this->customfield("groups"		,"stringfield3");
		$CHANGED += $this->customfield("lastscan"	,"stringfield4");
		$CHANGED += $this->customfield("run"		,"stringfield5");	unset($this->data['stringfield5']);	// This gets rid of duplication for very large indexed fields!
		$CHANGED += $this->customfield("version"	,"stringfield6");	unset($this->data['stringfield6']);	// This gets rid of duplication for very large indexed fields!
		$CHANGED += $this->customfield("inventory"	,"stringfield7");	unset($this->data['stringfield7']);	// This gets rid of duplication for very large indexed fields!
		$CHANGED += $this->customfield("model"		,"stringfield8");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['name'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['ip'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['protocol'	]);
		$DB->bind("STRINGFIELD3"	,$this->data['groups'	]);
		$DB->bind("STRINGFIELD4"	,$this->data['lastscan'	]);
		$DB->bind("STRINGFIELD5"	,$this->data['run'		]);
		$DB->bind("STRINGFIELD6"	,$this->data['version'	]);
		$DB->bind("STRINGFIELD7"	,$this->data['inventory']);
		$DB->bind("STRINGFIELD8"	,$this->data['model'	]);
	}

	private function jprint($OUTPUT)
	{
//		print "$OUTPUT";
		if (php_sapi_name() != "cli") { print "$OUTPUT"; \metaclassing\Utility::flush(); }
	}

	public function rescan($TRY = 1)
	{
		$OUTPUT = "";
		$this->data['protocol'] = "none";	// Start every SCAN fresh with protocol discovery

		// Start with a ping test, see if we can ping the IP
		$PING = new \JJG\Ping($this->data['ip']);
		$LATENCY = $PING->ping("exec");
		if (!$LATENCY)
		{
			$this->jprint(" Could not ping, connection may fail.");
			$OUTPUT .= " Could not ping, connection may fail.";
	//		print "\n"; $OUTPUT .= "\n"; return $OUTPUT;
		}else{
			$this->jprint(" Latency: {$LATENCY}ms");
			$OUTPUT .= " Latency: {$LATENCY}ms";
		}
		unset($PING);

		// Then try to get the CLI via any means necessary
//		$COMMAND = new Command($this->data);
		// This is a filthy hack...
		$NEWDATA = array_merge($this->data, array(
												"username" => LDAP_USER,
												"password" => LDAP_PASS,
												)
							);
		$COMMAND = new Command($NEWDATA);
		$this->jprint(" Connection:");
		$OUTPUT .= " Connection:";
		$CLI = $COMMAND->getcli();
		if ($CLI)
		{
			$this->jprint(" {$CLI->service}");
			$OUTPUT .= " {$CLI->service}";
		}else{
			$this->jprint(" Could not connect!\n");
			$OUTPUT .= " Could not connect!\n";
			$this->update();
			return $OUTPUT;
		}

		// We are connected, lets get the prompt!
		$this->jprint(" Prompt:");
		$OUTPUT .= " Prompt:";
		$PROMPT = strtolower($CLI->prompt);
		if ($PROMPT != "")
		{
			$this->jprint(" {$PROMPT} ");
			$OUTPUT .= " {$PROMPT} ";
			$this->data['name'] = $PROMPT;
			$this->data['protocol'] = $CLI->service;
		}else{
			$this->jprint(" Could not get prompt! Aborting!\n");
			$OUTPUT .= " Could not get prompt! Aborting!\n";
			$this->update();
			return $OUTPUT;
		}

		// Make sure we know what we are connected to!
		$this->jprint("Firewall detection: ");
		$OUTPUT .= "Firewall detection: ";
		$FUNCTION = "";
//		$CLI->exec("terminal length 0");
		$CLI->exec("no paging");
		$SHOW_INVENTORY = $CLI->exec("show inventory");
		$MODEL = \metaclassing\Cisco::inventoryToModel($SHOW_INVENTORY);
		if ($MODEL == "Unknown")
		{
			$SHOW_VERSION = $CLI->exec("show version");
			$MODEL = \metaclassing\Cisco::versionToModel($SHOW_VERSION);
		}
		if ($MODEL == "Unknown")
		{
			$this->jprint(" Could not detect device type/model! Aborting!\n");
			$OUTPUT .= " Could not detect device type/model! Aborting!\n";
			$OUTPUT .= \metaclassing\Utility::dumperToString($SHOW_INVENTORY);
			$OUTPUT .= \metaclassing\Utility::dumperToString($SHOW_VERSION);
			$this->update();
			return $OUTPUT;
		}

		if (preg_match('/(ASA|FWM|PIX)/',$MODEL,$REG))
		{
			$this->jprint(" YES! Model: {$MODEL}");
			$OUTPUT .= " YES! Model: {$MODEL}";
			$FUNCTION = "Firewall";
		}else{
			$this->jprint(" NO! Model: {$MODEL}");
			$OUTPUT .= " NO! Model: {$MODEL}";
		}

		// Special handling in case we are in a firewall
		if($FUNCTION == "Firewall")
		{
			$this->jprint(" Firewall, sending enable");
			$OUTPUT .= " Firewall, sending enable";
			$COMMAND = "enable\n" . TACACS_ENABLE;	$ENABLE_OUTPUT .= $CLI->exec($COMMAND);			//	$this->jprint("\nCOMMAND: $COMMAND\nOUTPUT: $OUTPUT\n";
			sleep(4);
			$COMMAND = "no pager";					//$OUTPUT .= $CLI->exec($COMMAND);				//	$this->jprint("\nCOMMAND: $COMMAND\nOUTPUT: $OUTPUT\n";
			$COMMAND = "terminal pager 0";			$TERMINAL_PAGER_OUTPUT .= $CLI->exec($COMMAND);	//	$this->jprint("\nCOMMAND: $COMMAND\nOUTPUT: $OUTPUT\n";
			$this->jprint(" Pager disabled");
			$OUTPUT .= " Pager disabled";
			if (\metaclassing\Cisco::checkInputError($TERMINAL_PAGER_OUTPUT) && \metaclassing\Cisco::checkInputError($ENABLE_OUTPUT))
			{
				$this->jprint(" Enabled Successfully!");
				$OUTPUT .= " Enabled Successfully!";
			}else{
				$this->jprint(" Error Enabling! ");
				$OUTPUT .= " Error Enabling! ";
				$this->update();
				return $OUTPUT;
			}
		}else{
			$CLI->exec("terminal length 0");
		}

		// Capture the show command output!
		$this->jprint(" version:");
		$OUTPUT .= " version:";
		$SHOW_VERSION	= $CLI->exec("show version");	$LEN_VER = strlen($SHOW_VERSION);
		if (\metaclassing\Cisco::checkInputError($SHOW_VERSION) && $LEN_VER > 200)	// No errors and >200 bytes
		{
			$this->jprint(" OK({$LEN_VER})");
			$OUTPUT .= " OK({$LEN_VER})";
		}else{
			$SHOW_VERSION = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" inventory:");
		$OUTPUT .= " inventory:";
		$SHOW_INVENTORY	= $CLI->exec("show inventory");	$LEN_INV = strlen($SHOW_INVENTORY);
		if (\metaclassing\Cisco::checkInputError($SHOW_INVENTORY) && $LEN_INV > 100)	// No errors and >100 bytes
		{
			$this->jprint(" OK({$LEN_INV})");
			$OUTPUT .= " OK({$LEN_INV})";
		}else{
			$SHOW_INVENTORY = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" run:");
		$OUTPUT .= " run:";
		$SHOW_RUN	= $CLI->exec("show run");		$LEN_RUN = strlen($SHOW_RUN);
		if (\metaclassing\Cisco::checkInputError($SHOW_RUN) && $LEN_RUN > 1000)	// No errors and >1000 bytes
		{
			$this->jprint(" OK({$LEN_RUN})");
			$OUTPUT .= " OK({$LEN_RUN})";
		}else{
/*			global $DB;									$LOG = "Scan error 1 - ";
			if ( !\metaclassing\Cisco::checkInputError($SHOW_RUN) )	{ $LOG .= "input - "; }
			if ( $LEN_RUN < 1000 )						{ $LOG .= "output - "; }
			$LOG .= "'{$SHOW_RUN}'";					$DB->log($LOG,1);
			// We failed so try again!
			$this->jprint(" run retry:");
			$OUTPUT .= " run retry:";
			$SHOW_RUN	= $this->get_running_config();	$LEN_RUN = strlen($SHOW_RUN);
			if (\metaclassing\Cisco::checkInputError($SHOW_RUN) && $LEN_RUN > 1000)
			{
				$this->jprint(" OK({$LEN_RUN})"); $OUTPUT .= " OK({$LEN_RUN})";
			}else{
/**/				global $DB;									$LOG = "Scan error 2 - ";
				if ( !\metaclassing\Cisco::checkInputError($SHOW_RUN) )	{ $LOG .= "input - "; }
				if ( $LEN_RUN < 1000 )						{ $LOG .= "output - "; }
				$LOG .= "'{$SHOW_RUN}'";					//$DB->log($LOG,2);
				// We failed again so give up!
				$SHOW_RUN = "";
				$this->jprint(" NO!");
				$OUTPUT .= " NO!";
				$this->data['protocol'] = "none";
//			}
		}

		$this->jprint(" diag:");
		$OUTPUT .= " diag:";
		$SHOW_DIAG	= $CLI->exec("show diag");		$LEN_DIAG = strlen($SHOW_DIAG);
		if (\metaclassing\Cisco::checkInputError($SHOW_DIAG) && $LEN_DIAG > 100)	// No errors and >100 bytes
		{
			$this->jprint(" OK({$LEN_DIAG})");
			$OUTPUT .= " OK({$LEN_DIAG})";
		}else{
			$SHOW_DIAG = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" module:");
		$OUTPUT .= " module:";
		$SHOW_MOD	= $CLI->exec("show module");	$LEN_MOD = strlen($SHOW_MOD);
		if (\metaclassing\Cisco::checkInputError($SHOW_MOD) && $LEN_MOD > 100)	// No errors and >100 bytes
		{
			$this->jprint(" OK({$LEN_MOD})");
			$OUTPUT .= " OK({$LEN_MOD})";
		}else{
			$SHOW_MOD = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" interface:");
		$OUTPUT .= " interface:";
		$SHOW_INT	= $CLI->exec("show interface");	$LEN_INT = strlen($SHOW_INT);
		if (\metaclassing\Cisco::checkInputError($SHOW_INT) && $LEN_INT > 200)	// No errors and >200 bytes
		{
			$this->jprint(" OK({$LEN_INT})");
			$OUTPUT .= " OK({$LEN_INT})";
		}else{
			$SHOW_INT = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->data['lastscan'] = date("Y-m-d H:i:s"); // Need to test the shit out of this one lol
		$this->data['version']	= $SHOW_VERSION;
		$this->data['inventory']= $SHOW_INVENTORY;
		$this->data['run']		= $SHOW_RUN;
		$this->data['diag']		= $SHOW_DIAG;
		$this->data['module']	= $SHOW_MOD;
		$this->data['interface']= $SHOW_INT;
		$this->data['model']	= $MODEL;
		$this->data['pattern']	= sprintf($CLI->pattern['match'],$CLI->prompt);

		$this->update();
		$this->jprint(" Scan complete, database updated!\n");
		$OUTPUT .= " Scan complete, database updated!\n";
		return $OUTPUT;
	}

	public function get_running_config($TRY = 1)
	{
		$COMMAND = new Command($this->data);	// Start every attempt with a fresh connection!
		$CLI = $COMMAND->getcli();					sleep(1);
		$CLI->exec("terminal length 0");
		$SHOW_RUN   = $CLI->exec("show run");       $LEN_RUN = strlen($SHOW_RUN);
		if ($TRY > 3) { return $SHOW_RUN; }		// Give up after 3 tries
		if (\metaclassing\Cisco::checkInputError($SHOW_RUN) && $LEN_RUN > 1000)  // No errors and >1000 bytes
		{
			return $SHOW_RUN;					// We got a good running config
		}else{
			global $DB;
			$DB->log("get_running_config error, try $TRY",1);
			return $this->get_running_config(++$TRY);	// Try harder next time
        }
	}

	public function push($PUSH_LINES)
	{
		$DEVICE = $this;
		$LOG = "PUSH DEVICE ID {$DEVICE->data['id']} PROMPT {$DEVICE->data["name"]} IP {$DEVICE->data['ip']}\n";
		global $DB; $DB->log($LOG,2); $this->jprint($LOG);

		if (count($PUSH_LINES) < 1) { $this->jprint("DONE, NO LINES TO PUSH!\n"); return; }

		// Start with a ping test, see if we can ping the IP
		$PING = new \JJG\Ping($DEVICE->data['ip']);
		$LATENCY = $PING->ping("exec");
		if (!$LATENCY)
		{
			$this->jprint(" Could not ping, connection may fail.");
		}else{
			$this->jprint(" Latency: {$LATENCY}ms");
		}
		unset($PING);

		// Then try to get the CLI
		$COMMAND = new Command($DEVICE->data);
		$this->jprint(" Connection:");
		$CLI = $COMMAND->getcli();
		if ($CLI)
		{
			$this->jprint(" {$CLI->service}");
		}else{
			$this->jprint(" Could not connect! Aborting!\n"); return;
		}

		$this->jprint(" Prompt:");
		$PROMPT = strtolower($CLI->prompt);
		if ($PROMPT != "")
		{
			$this->jprint(" {$PROMPT} ");
			$PROTO = $CLI->service;
		}else{
			$this->jprint(" Could not get prompt! Aborting!\n"); return;
		}

		// Make sure we know what we are connected to!
		$this->jprint("Firewall detection: ");
		$FUNCTION = "";
		$CLI->exec("terminal length 0");
		$SHOW_INVENTORY = $CLI->exec("show inventory | I PID");
		$MODEL = \metaclassing\Cisco::inventoryToModel($SHOW_INVENTORY);
		if ($MODEL == "Unknown")
		{
			$SHOW_VERSION = $CLI->exec("show version | I C");
			$MODEL = \metaclassing\Cisco::versionToModel($SHOW_VERSION);
		}
		if ($MODEL == "Unknown")
		{
			$this->jprint(" Could not detect device type/model! Aborting!\n"); return;
		}

		if (preg_match('/(ASA|FWM|PIX)/',$MODEL,$REG))
		{
			$FUNCTION = "Firewall";
			$this->jprint(" YES! Model: {$MODEL}");
		}else{
			$this->jprint(" NO! Model: {$MODEL}");
		}

		// Special handling in case we are in a firewall
		if($FUNCTION == "Firewall")
		{
			$this->jprint(" Firewall, sending enable");
			$COMMAND = "enable\n" . TACACS_ENABLE;	$OUTPUT = $CLI->exec($COMMAND);
			sleep(4);
			$COMMAND = "no pager";					$OUTPUT = $CLI->exec($COMMAND);
			$COMMAND = "terminal pager 0";			$OUTPUT = $CLI->exec($COMMAND);
			$TERMINAL_PAGER_OUTPUT = $OUTPUT;
			$this->jprint(" Pager disabled");
			if (\metaclassing\Cisco::checkInputError($TERMINAL_PAGER_OUTPUT))
			{
				$this->jprint(" Enabled Successfully!");
			}else{
				$this->jprint(" Error Enabling! Aborting!"); return;
			}
		}else{
			$CLI->exec("terminal length 0");
			$CLI->exec("terminal width 500");
		}

		// Build the final configuration for this device to push
		$PUSH = array();
		array_push($PUSH,"config t"					);
		foreach($PUSH_LINES as $LINE) { array_push($PUSH,$LINE); }
		array_push($PUSH,"end"						);

		// Debugging before actually running this to make live config changes
		//\metaclassing\Utility::dumper($PUSH);	die("CROAK!\n");	// Comment me out

		// Perform the config push
		foreach($PUSH as $COMMAND)
		{
			$COMMAND = trim($COMMAND);
			$this->jprint("Running: {$COMMAND}\n");
			$OUTPUT = trim($CLI->exec($COMMAND));
			$this->jprint("{$OUTPUT}\n\n");
		}

		// Do a config save and rescan the device
		$this->jprint(" PUSH COMPLETE, saving config and running scan!\n");
		$COMMAND = "timeout 1m php ".BASEDIR."/bin/save-config.php --id={$DEVICE->data['id']} > /dev/null 2>/dev/null &";
		$this->jprint("running $COMMAND\n");
		exec($COMMAND);
		$COMMAND = "timeout 1m php ".BASEDIR."/bin/scan-device.php --id={$DEVICE->data['id']} > /dev/null 2>/dev/null &";
		$this->jprint("running $COMMAND\n");
		exec($COMMAND);
		$this->jprint("Work Complete!\n");
	}

}

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

class IPPlan_Block	extends Information
{
	public $data;
	public $category = "IPPlan";
	public $type = "IPPlan_Block";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("linked"	,"stringfield0");
		$CHANGED += $this->customfield("prefix"	,"stringfield1");
		$CHANGED += $this->customfield("length"	,"stringfield2");
		$CHANGED += $this->customfield("version","stringfield3");
		$CHANGED += $this->customfield("name"	,"stringfield4");
		$this->data['length'] = intval($this->data['length']);
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function validate($NEWDATA)
	{
		if (!intval($this->data['parent']))
		{
			$this->data['error'] .= "ERROR: Could not validate parent object\n";
			return 0;
		}
		$NETWORK = $NEWDATA['prefix'] . "/" . $NEWDATA['length'];
		$NET = Net_IPv4::parseAddress($NETWORK);
		if(!$NET)
		{
			$this->data['error'] .= "ERROR: Could not validate $NETWORK is a valid network!\n";
			return 0;
		}
		if ($NET->network != $NEWDATA['prefix'])
		{
			$this->data['error'] .= "ERROR: $NETWORK is NOT a valid network address of {$NET->network}/{$NET->bitmask}!\n";
			return 0;
		}
		$PARENT = $this->parent();
		$PARENTNETWORK = $PARENT->data['prefix'] . "/" . $PARENT->data['length'];
		if (!Net_IPv4::ipInNetwork($NEWDATA['prefix'], $PARENTNETWORK))
		{
			$this->data['error'] .= "ERROR: Could not validate IP {$NEWDATA['prefix']} falls within parent network {$PARENTNETWORK}!\n";
			return 0;
		}
		$SIBLINGS = $PARENT->children();
		foreach ($SIBLINGS as $SIBLING)
		{
			$SIBLINGNETWORK = $SIBLING->data['prefix'] . "/" . $SIBLING->data['length'];
			if (Net_IPv4::ipInNetwork($NEWDATA['prefix'], $SIBLINGNETWORK) && $SIBLING->data['id'] != $this->data['id'])
			{
				$this->data['error'] .= "ERROR: $NETWORK overlaps with sibling network {$SIBLINGNETWORK} ID {$SIBLING->data['id']}!\n";
				return 0;
			}
		}

		return 1;
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['linked'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['prefix'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['length'		]);
		$DB->bind("STRINGFIELD3"	,$this->data['version'		]);
		$DB->bind("STRINGFIELD4"	,$this->data['name'			]);
	}

	public function children($ID = 0, $TYPE = "", $CATEGORY = "")
	{
		if ($ID == 0) { $ID = $this->data['id']; }
		$QUERY = "select id from information where parent = :ID";
		if ($TYPE != "") { $QUERY .= " and type like :TYPE"; }
		if ($CATEGORY != "") { $QUERY .= " and category like :CATEGORY"; }
		$QUERY .= " order by INET_ATON(stringfield1),stringfield2";

		global $DB;
		$DB->query($QUERY);
		try {
			$DB->bind("ID",$ID);
			if ($TYPE 		!= "") { $DB->bind("TYPE"		,$TYPE);	}
			if ($CATEGORY	!= "") { $DB->bind("CATEGORY"	,$CATEGORY);}
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$CHILDREN = array();
		foreach ($RESULTS as $CHILD)
		{
			array_push($CHILDREN, Information::retrieve($CHILD['id']));
		}
		return $CHILDREN;
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 and stringfield1 = '0.0.0.0' and stringfield2 = '0'";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE",$this->data['type']);
			$DB->bind("CATEGORY",$this->data['category']);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		return $RESULTS;
	}

	public function html_list_header()
	{
		$OUTPUT = "";

		$WIDTH = array();	$i = 1;
		$WIDTH[$i++] = 35;	// ID
		$WIDTH[$i++] = 50;	// Type
		$WIDTH[$i++] = 130;	// Prefix
		$WIDTH[$i++] = 250;	// Name
		$WIDTH[$i++] = 200;	// Linked Information
		$WIDTH[0] = array_sum($WIDTH);

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$WIDTH[0]}">
			<caption class="report">IPPlan Child Blocks & Networks</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">Type</th>
					<th class="report" width="{$WIDTH[$i++]}">Prefix</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Linked Information</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$rowclass = "row".(($i % 2)+1);

		$WIDTH = array();	$i = 1;
		$WIDTH[$i++] = 35;	// ID
		$WIDTH[$i++] = 50;	// Type
		$WIDTH[$i++] = 130;	// Prefix
		$WIDTH[$i++] = 250;	// Name
		$WIDTH[$i++] = 200;	// Linked Information
		$WIDTH[0] = array_sum($WIDTH);

		$columns = count($WIDTH)-1;	$i = 1;
		$datadump = \metaclassing\Utility::dumperToString($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['type']}</td>
					<td class="report" width="{$WIDTH[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['prefix']}/{$this->data['length']}</a></td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['name']}</td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['linked']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$WIDTH = array();	$i = 1;
		$WIDTH[$i++] = 35;	// ID
		$WIDTH[$i++] = 50;	// Type
		$WIDTH[$i++] = 130;	// Prefix
		$WIDTH[$i++] = 250;	// Name
		$WIDTH[$i++] = 200;	// Linked Information
		$WIDTH[0] = array_sum($WIDTH);

		// Pre-information table links to edit or perform some action
		$OUTPUT .= <<<END
		<table width="{$WIDTH[0]}" border="0" cellspacing="0" cellpadding="1">
			<tr>
				<td align="right">
					<ul class="object-tools">
						<li>
							<a href="/information/information-edit.php?id={$this->data['id']}" class="viewsitelink">Edit Information</a>
						</li>
					</ul>
				</td>
			</tr>
		</table>
END;

		// Information table itself
		$columns = count($WIDTH)-1;
		$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$WIDTH[0]}">
			<caption class="report">This IPPlan {$this->data['type']}</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">Type</th>
					<th class="report" width="{$WIDTH[$i++]}">Prefix</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Linked Information</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);
		$rowclass = "row".(($i % 2)+1);
		$datadump = \metaclassing\Utility::dumperToString($this->data);
		if ($_SESSION["DEBUG"] == 3)
		{
			$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$columns}">
						{$datadump}
					</td>
				</tr>
END;
		}
		$OUTPUT .= $this->html_list_footer();

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
		if ($this->data['length'] <= 24)
		{
			array_push($CHILDTYPES,"Block");
		}
		if ($this->data['length'] >= 16)
		{
			array_push($CHILDTYPES,"Network");
		}
		$OUTPUT .= <<<END

			<table width="{$WIDTH[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
END;
		foreach ($CHILDTYPES as $CHILDTYPE)
		{
			$OUTPUT .= <<<END

						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
END;
		}
		$OUTPUT .= <<<END
					</td>
				</tr>
			</table>
END;

		$CHILDREN = $this->children($this->id,"","IPPlan");
		$i = 1;
		if (!empty($CHILDREN))
		{
			$CHILD = reset($CHILDREN);
			$OUTPUT .= $CHILD->html_list_header();
			foreach ($CHILDREN as $CHILD)
			{
				$OUTPUT .= $CHILD->html_list_row($i++);
			}
			$OUTPUT .= $CHILD->html_list_footer();
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		if (!intval($this->data['parent'])) { return "Error: No parent ID passed, please select a block or network with a valid parent!"; }
		$OUTPUT .= <<<END
			<div id="nosx_form">
			<form method="post" action="{$_SERVER['PHP_SELF']}">
			<table width="500" border="0" cellspacing="2" cellpadding="1">

				<tr><td>
					<strong>Prefix Name:</strong>
					<input type="text" name="name" size="20" value="{$this->data['name']}">
				</td></tr>

				<tr><td>
					<strong>Prefix Address:</strong>
					<input type="text" name="prefix" size="20" value="{$this->data['prefix']}">
				</td></tr>

				<tr><td>
					<strong>Prefix Length:</strong>
					<select name="length" size="1">
END;
		if ($this->data['length'])
		{
			$OUTPUT .= <<<END
					<option value="{$this->data['length']}">{$this->data['length']}</option>
END;
		}else{
			$PARENT = $this->parent();
			$PARENTLENGTH = intval($PARENT->data['length']);
			if ($PARENTLENGTH < 8)							{ $RANGE = array(8);	}
			if ($PARENTLENGTH >= 8 && $PARENTLENGTH < 16)	{ $RANGE = array(16);	}
			if ($PARENTLENGTH >=16)							{ $RANGE = range($PARENTLENGTH + 1,24);}
			foreach($RANGE as $length) { $OUTPUT .= "<option value=\"{$length}\">{$length}</option>"; }
			$OUTPUT .= "
				</td></tr>";
		}

//////////////////////////////////////////////////////END OF FIELDS//////////////////////////////////////////////////////////////
		if($this->data['id'])
		{
			$OUTPUT .= <<<END
				<tr><td>
					<input type="hidden" name="id"		value="{$this->data['id']}">
			        	<input type="submit"			value="Edit Information">
				</td></tr>
END;
		}else{
			$OUTPUT .= <<<END
				<tr><td>
					<input type="hidden" name="category"	value="{$this->data['category']}">
					<input type="hidden" name="type"	value="{$this->data['type']}">
					<input type="hidden" name="parent"	value="{$this->data['parent']}">
			        	<input type="submit"			value="Add Information">
				</td></tr>
END;
		}
		$OUTPUT .= <<<END
			</table>
			</form>
		</div>
END;

		return $OUTPUT;
	}

}

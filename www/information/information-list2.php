<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Information");

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['category']) && isset($_GET['type']))
{
	if (isset($_GET["parent"	]))	{ $PARENT	= $_GET["parent"	];	}else{ $PARENT = 0;				}
	if (isset($_GET["category"	]))	{ $CATEGORY	= $_GET["category"	];	}else{ $CATEGORY = "";			}
	if (isset($_GET["type"		]))	{ $TYPE		= $_GET["type"		];	}else{ $TYPE = "Information";	}
	if (isset($_GET["page"		]))	{ $PAGE		= $_GET["page"		];	}else{ $PAGE = 0;				}
	if (isset($_GET["filter"	])) { $FILTER	= $_GET["filter"	];	}else{ $FILTER = "";			}

	$SEARCH = array(			// Search for a category index!
			"category"	=> $CATEGORY,
			"type"		=> "Index",
		);
	$RESULTS = Information::search($SEARCH);
	$COUNT = count($RESULTS);
	if ($COUNT)
	{
		$INDEX_ID = reset($RESULTS);
		$INDEX = Information::retrieve($INDEX_ID);
		$HTML->breadcrumb("List","/information/information-list.php?category={$CATEGORY}&type=Index");
	}else{
		$HTML->breadcrumb("List");
	}

	print $HTML->header("List $CATEGORY / $TYPE Information");

	$INFOBJECT = Information::create($TYPE,$CATEGORY,$PARENT);
	$PERMISSION = "information";
	if (!empty($CATEGORY)) { $PERMISSION .= ".{$CATEGORY}"; }
	$PERMISSION .= ".{$TYPE}";
	$PERMISSION .= ".view";
	if(PERMISSION_CHECK($PERMISSION))
	{
		print $INFOBJECT->html_list_gearman($PAGE,$FILTER);
	}else{
		print "Error: You lack permission $PERMISSION to perform this action!\n";
	}
}else{
	$HTML->breadcrumb("List");
	print $HTML->header("List Information");
	print "Error: No information category/type passed!\n";
}

print $HTML->footer();

?>

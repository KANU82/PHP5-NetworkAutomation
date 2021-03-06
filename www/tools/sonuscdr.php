<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("Sonus CDR Parser",$HTML->thispage);
print $HTML->header("Sonus CDR Parser");

print <<<END
		<form name="sonus" method="post" action="{$_SERVER['PHP_SELF']}">
			Paste comma delimited sonus CDR log output:<br>
			<textarea name="sonuslog" rows="10" cols="60" wrap="off">{$_POST['sonuslog']}</textarea><br>
			<input type="submit" value="Parse!">
		</form><br>
END;

if (!isset($_POST['sonuslog']))
{
	die($HTML->footer());
}

$LOG = $_POST['sonuslog'];							// Get our logs as text lines from the browser
$LOGLINES = explode("\n", $LOG);					// Split the lines into individual records
$LOGPARSED = array();								// Make a destination to populate with parsed CSV arrays
foreach ($LOGLINES as $LINE)						// Loop through the raw CDR text lines
{
	if ($LINE != "")								// Skip Blank Lines!
	{
		array_push( $LOGPARSED , str_getcsv($LINE) );	// Parse each line as a set of comma separated values
	}
}

$CDRFIELDS = array();								// Define the fields for each TYPE of CDR!
$CDRFIELDS["START"]	= array(
	"Record Type",
	"Gateway Name",
	"Accounting ID",
	"Start Time(System Ticks)",
	"Node Time Zone",
	"Start Time (MM/DD/YYYY)",
	"Start Time (HH/MM/SS.s)",
	"Ticks from Setup Msg to Policy Response",
	"Ticks from Setup Msg to Alert/Proc/Prog",
	"Ticks from Setup Msg to Service Est",
	"Service Delivered ",
	"Call Direction ",
	"Service Provider",
	"Transit Network Selection Code (TNS)",
	"Calling Number",
	"Called Number",
	"Extra Called Address Digits",
	"Number of Called Num Translations",
	"Called Number Before Translation #1",
	"Translation Type #1",
	"Called Number Before Translation #2",
	"Translation Type #2",
	"Billing Number",
	"Route Label ",
	"Route Attempt Number",
	"Route Selected",
	"Egress Local Signaling IP Address",
	"Egress Remote Signaling IP Address",
	"Ingress Trunk Group Name",
	"Ingress PSTN Circuit End Point",
	"Ingress IP Circuit End Point",
	"Egress PSTN Circuit End Point",
	"Egress IP Circuit End Point",
	"Originating Line Information (OLIP)",
	"Jurisdiction Information Parameter (JIP)",
	"Carrier Code",
	"Call Group ID",
	"Ticks from Setup Msg to Rx of EXM",
	"Ticks from Setup Msg to Generation of EXM",
	"Calling Party Nature of Address",
	"Called Party Nature of Address",
	"Ingress Protocol Variant Specific Data",
	"Ingress Signaling Type",
	"Egress Signaling Type",
	"Ingress Far End Switch Type",
	"Egress Far End Switch Type",
	"Carrier Code of Carrier who Owns iTG Far End ",
	"Carrier Code of Carrier who Owns eTG Far End",
	"Calling Party Category",
	"Dialed Number",
	"Carrier Selection Information",
	"Called Number Numbering Plan",
	"Generic Address Parameter",
	"Egress Trunk Group Name",
	"Egress Protocol Variant Specific Data",
	"Incoming Calling Number",
	"AMA Call Type ",
	"Message Billing Indicator (MBI)",
	"LATA",
	"Route Index Used",
	"Calling Party Presentation Restriction",
	"Incoming ISUP Charge Number",
	"Incoming ISUP Nature Of Address",
	"Dialed Number Nature of Address",
	"Global Call ID (GCID)",
	"Charge Flag",
	"AMA slp ID",
	"AMA BAF Module",
	"AMA Set Hex AB Indication",
	"Service Feature ID",
	"FE Parameter",
	"Satellite Indicator",
	"PSX Billing Info",
	"Originating TDM Trunk Group Type",
	"Terminating TDM Trunk Group Type",
	"Ingress Trunk Member Number",
	"Egress Trunk Group ID",
	"Egress Switch ID",
	"Ingress Local ATM Address",
	"Ingress Remote ATM Address",
	"Egress Local ATM Address",
	"Egress Remote ATM Address",
	"PSX Call Type",
	"Outgoing Route Trunk Group ID",
	"Outgoing Route Message ID",
	"Incoming Route ID",
	"Calling Name",
	"Calling Name Type",
	"Incoming Calling Party Numbering Plan",
	"Outgoing Calling Party Numbering Plan",
	"Calling Party Business Group ID",
	"Called Party Business Group ID",
	"Calling Party PPDN",
	"Ticks from Setup Msg to Last Route Attempt",
	"Billing Number Nature of Address",
	"Incoming Calling Number Nature of Address",
	"Egress Trunk Member Number",
	"Selected Route Type",
	"Cumulative Route Index",
	"ISDN PRI Calling Party Subaddress",
	"Outgoing Trunk Group Number in EXM",
	"Ingress Local Signaling IP Address",
	"Ingress Remote Signaling IP Address",
	"Record Sequence Number",
	"Transmission Medium Requirement",
	"Information Transfer Rate",
	"USI User Info Layer 1",
	"Unrecognized Raw ISUP Calling Party Category",
	"FSD: Egress Release Link Trunking",
	"FSD: Two B-Channel Transfer",
	"Calling Party Business Unit",
	"Called Party Business Unit",
	"FSD: Redirecting",
	"FSD: Ingress Release Link Trunking",
	"PSX ID",
	"PSX Congestion Level",
	"PSX Processing Time (milliseconds)",
	"Script Name",
	"Ingress External Accounting Data",
	"Egress External Accounting Data",
	"Answer Supervision Type",
	"Ingress Sip Refer or Sip Replaces Feature Specific Data",
	"Egress Sip Refer or Sip Replaces Feature Specific Data",
	"Network Transfers Feature Specific Data ",
	"Call Condition",
	"Toll Indicator",
	"Generic Number (Number)",
	"Generic Number Presentation Restriction Indicator",
	"Generic Number Numbering Plan",
	"Generic Number Nature of Address",
	"Generic Number Type",
	"Originating Trunk Type",
	"Terminating Trunk Type",
	"VPN Calling Public Presence Number",
	"VPN Calling Private Presence Number ",
	"External Furnish Charging Info",
	"Announcement Id",
	"Network Data - Source Information",
	"Network Data - Partition ID",
	"Network Data - Network ID ",
	"Network Data - NCOS",
	"ISDN access Indicator",
	"Network Call Reference - Call Identity",
	"Network Call Reference - Signaling Point Code",
	"Ingress MIME Protocol Variant Specific Data",
	"Egress MIME Protocol Variant Specific Data",
	"Video Data - Video Bandwidth, Video Call Duration, Ingress/Egress IP video Endpoint",
	"SVS Customer",
	"SVS Vendor - Deprecated in 7.2.2 as part of Pcr1624",
	"Remote GSX Billing Indicator (PCR1216 - GSX 6.4 for KDDI special V3)",
	"Call To Test PSX",
	"PSX Overlap Route Requests",
	"Call Setup Delay",
	"Overload Status",
	"reserved1",
	"reserved2",
	"MLPP Precedence Level",
	"reserved3",
	"reserved4 ",
	"reserved5",
	"reserved6",
	"reserved7",
	"reserved8",
	"reserved9",
	"reserved10",
	"Global Charge Reference"
);
$CDRFIELDS["STOP"]	= array(
	"Record Type",
	"Gateway Name",
	"Accounting ID",
	"Start Time (system ticks)",
	"Node Time Zone ",
	"Start Time (MM/DD/YYYY)",
	"Start Time (HH/MM/SS.s)",
	"Ticks from Setup Msg to Policy Response",
	"Ticks from Setup Msg to Alert/Proc/Prog",
	"Ticks from Setup Msg to Service Est",
	"Disconnect Time (MM/DD/YYYY)",
	"Disconnect Time (HH:MM:SS.s)",
	"Ticks from Disconnect to Call Termination",
	"Call Service Duration",
	"Call Disconnect Reason",
	"Service Delivered",
	"Call Direction",
	"Service Provider",
	"Transit Network Selection Code (TNS)",
	"Calling Number",
	"Called Number",
	"Extra Called Address Digits",
	"Number of Called Num Translations",
	"Called Number Before Translation #1",
	"Translation Type #1",
	"Called Number Before Translation #2",
	"Translation Type #2",
	"Billing Number",
	"Route Label",
	"Route Attempt Number",
	"Route Selected",
	"Egress Local Signaling IP Address",
	"Egress Remote Signaling IP Address",
	"Ingress Trunk Group Name",
	"Ingress PSTN Circuit End Point",
	"Ingress IP Circuit End Point",
	"Egress PSTN Circuit End Point",
	"Egress IP Circuit End Point",
	"Ingress Number of Audio Bytes Sent",
	"Ingress Number of Audio Packets Sent",
	"Ingress Number of Audio Bytes Received",
	"Ingress Number of Audio Packets Received",
	"Originating Line Information (OLIP)",
	"Jurisdiction Information Parameter (JIP)",
	"Carrier Code",
	"Call Group ID",
	"Script Log Data",
	"Ticks from Setup Msg to Rx of EXM",
	"Ticks from Setup Msg to Generation of EXM",
	"Calling Party Nature of Address",
	"Called Party Nature of Address",
	"Ingress Protocol Variant Specific Data",
	"Ingress Signaling Type",
	"Egress Signaling Type",
	"Ingress Far End Switch Type",
	"Egress Far End Switch Type",
	"Carrier Code of Carrier who Owns iTG Far End",
	"Carrier Code of Carrier who Owns eTG Far End",
	"Calling Party Category",
	"Dialed Number",
	"Carrier Selection Information",
	"Called Number Numbering Plan",
	"Generic Address Parameter",
	"Disconnect Initiator",
	"Ingress Number of Packets Recorded as Lost",
	"Ingress Interarrival Packet Jitter",
	"Ingress Last Measurement for Latency",
	"Egress Trunk Group Name",
	"Egress Protocol Variant Specific Data",
	"Incoming Calling Number",
	"AMA Call Type",
	"Message Billing Indicator (MBI)",
	"LATA",
	"Route Index Used",
	"Calling Party Presentation Restriction",
	"Incoming ISUP Charge Number",
	"Incoming ISUP Nature Of Address",
	"Dialed Number Nature of Address",
	"Ingress Codec Info",
	"Egress Codec Info",
	"Ingress RTP Packetization Time",
	"Global Call ID (GCID)",
	"Originator Echo Cancellation",
	"Terminator Echo Cancellation",
	"Charge Flag",
	"AMA slp ID",
	"AMA BAF Module",
	"AMA Set Hex AB Indication",
	"Service Feature ID",
	"FE Parameter",
	"Satellite Indicator",
	"PSX Billing Info",
	"Originating TDM Trunk Group Type",
	"Terminating TDM Trunk Group Type",
	"Ingress Trunk Member Number",
	"Egress Trunk Group ID",
	"Egress Switch ID",
	"Ingress Local ATM Address",
	"Ingress Remote ATM Address",
	"Egress Local ATM Address",
	"Egress Remote ATM Address",
	"PSX Call Type",
	"Outgoing Route Trunk Group ID",
	"Outgoing Route Message ID",
	"Incoming Route ID",
	"Calling Name",
	"Calling Name Type",
	"Incoming Calling Party Numbering Plan",
	"Outgoing Calling Party Numbering Plan",
	"Calling Party Business Group ID",
	"Called Party Business Group ID",
	"Calling Party PPDN",
	"Ticks from Setup Msg to Last Route Attempt",
	"Billing Number Nature of Address",
	"Incoming Calling Number Nature of Address",
	"Egress Trunk Member Number",
	"Selected Route Type",
	"Telcordia Long Duration Record Type",
	"Ticks From Previous Record",
	"Cumulative Route Index",
	"Call Disconnect Reason Sent to Ingress",
	"Call Disconnect Reason Sent to Egress",
	"ISDN PRI Calling Party Subaddress",
	"Outgoing Trunk Group Number in EXM",
	"Ingress Local Signaling IP Address",
	"Ingress Remote Signaling IP Address",
	"Record Sequence Number",
	"Transmission Medium Requirement",
	"Information Transfer Rate",
	"USI User Info Layer 1",
	"Unrecognized Raw ISUP Calling Party Category",
	"FSD: Egress Release Link Trunking",
	"FSD: Two B-Channel Transfer",
	"Calling Party Business Unit",
	"Called Party Business Unit",
	"FSD: Redirecting",
	"FSD: Ingress Release Link Trunking",
	"PSX Index",
	"PSX Congestion Level",
	"PSX Processing Time (milliseconds)",
	"Script Name",
	"Ingress External Accounting Data",
	"Egress External Accounting Data",
	"Egress RTP Packetization Time",
	"Egress Number of Audio Bytes Sent",
	"Egress Number of Audio Packets Sent",
	"Egress Number of Audio Bytes Received",
	"Egress Number of Audio Packets Received",
	"Egress Number of Packets Recorded as Lost",
	"Egress Interarrival Packet Jitter",
	"Egress Last Measurement for Latency",
	"Ingress Maximum Packet Outage",
	"Egress Maximum Packet Outage",
	"Ingress Packet Playout Buffer Quality",
	"Egress Packet Playout Buffer Quality",
	"Answer Supervision Type",
	"Ingress Sip Refer or Sip Replaces Feature Specific Data",
	"Egress Sip Refer or Sip Replaces Feature Specific Data",
	"Network Transfers Feature Specific Data ",
	"Call Condition",
	"Toll Indicator ",
	"Generic Number ( Number )",
	"Generic Number Presentation Restriction Indicator",
	"Generic Number Numbering Plan",
	"Generic Number Nature of Address",
	"Generic Number Type",
	"Originating Trunk Type",
	"Terminating Trunk Type",
	"Remote GSX Billing Indicator",
	"VPN Calling Private Presence Number",
	"VPN Calling Public Presence Number",
	"External Furnish Charging Info",
	"Ingress Policing Discards",
	"Egress Policing Discards",
	"Announcement Id ",
	"Network Data - Source Information",
	"Network Data - Partition ID",
	"Network Data - Network ID",
	"Network Data - NCOS ",
	"Ingress SRTP (Secure RTP/RTCP",
	"Egress SRTP (Secure RTP/RTCP)",
	"ISDN access Indicator",
	"Call Disconnect Location ",
	"Call Disconnect Location Transmitted to Ingress",
	"Call Disconnect Location Transmitted to Egress",
	"Network Call Reference - Call Identity",
	"Network Call Reference - Signaling Point Code",
	"Ingress MIME Protocol Variant Specific Data",
	"Egress MIME Protocol Variant Specific Data",
	"Modem Tone Type",
	"Modem Tone Signal Level ",
	"Video Data - Video Bandwidth, Video Call Duration, Ingress/Egress IP Video End Point",
	"Video Statistics - Ingress/Egress Video Statistics.",
	"SVS Customer",
	"SVS Vendor - Deprecated in 7.2.2 as part of Pcr1624",
	"Call To Test PSX",
	"Psx Overlap Route Requests",
	"Call Setup Delay",
	"Overload Status",
	"reserved1",
	"reserved2",
	"Ingress DSP Data Bitmap",
	"Egress DSP Data Bitmap ",
	"Call Recorded Indicator",
	"Call Recorded RTP Tx Ip Address",
	"Call Recorded RTP Tx Port Number",
	"Call Recorded RTP Rv Ip Address",
	"Call Recorded RTP Rv Port Number",
	"Mlpp Precedence Level",
	"reserved3",
	"reserved4",
	"reserved5",
	"reserved6",
	"reserved7",
	"reserved8",
	"reserved9",
	"Global Charge Reference",
	"reserved10",
	"reserved11",
	"reserved12",
	"reserved13",
	"reserved14",
	"reserved15",
	"reserved16",
	"reserved17",
	"Ingress Inbound R-Factor",
	"Ingress Outbount R-Factor",
	"Egress Inbound R-Factor",
	"Egress Outbount R-Factor",
	"Media Stream Data ",
	"Media Stream Stats",
	"Transcoded Indicator",
	"HD Codec Rate",
	"Remote Ingress Audio RTCP Learned Metrics",
	"Remote Egress Audio RTCP Learned Metrics",
);
$CDRFIELDS["ATTEMPT"]	= array(
	"Record Type",
	"Gateway Name ",
	"Accounting ID",
	"Start Time (system ticks)",
	"Node Time Zone",
	"Start Time (MM/DD/YYYY)",
	"Start Time (HH/MM/SS.s)",
	"Ticks from Setup Msg to Policy Response",
	"Ticks from Setup Msg to Alert/Proc/Prog",
	"Disconnect Time (HH:MM:SS.s)",
	"Ticks from Disconnect to Call Termination",
	"Call Disconnect Reason",
	"Service Delivered",
	"Call Direction",
	"Service Provider",
	"Transit Network Selection Code (TNS)",
	"Calling Number",
	"Called Number",
	"Extra Called Address Digits",
	"Number of Called Num Translations",
	"Called Number Before Translation #1",
	"Translation Type #1 ",
	"Called Number Before Translation #2",
	"Translation Type #2",
	"Billing Number",
	"Route Label",
	"Route Attempt Number",
	"Route Selected",
	"Egress Local Signaling IP Address",
	"Egress Remote Signaling IP Address",
	"Ingress Trunk Group Name",
	"Ingress PSTN Circuit End Point",
	"Ingress IP Circuit End Point",
	"Egress PSTN Circuit End Point",
	"Egress IP Circuit End Point",
	"Originating Line Information (OLIP)",
	"Jurisdiction Information Parameter (JIP)",
	"Carrier Code",
	"Call Group ID",
	"Script Log Data",
	"Ticks from Setup Msg to Rx of EXM",
	"Ticks from Setup Msg to Generation of EXM",
	"Calling Party Nature of Address",
	"Called Party Nature of Address",
	"Ingress Protocol Variant Specific Data",
	"Ingress Signaling Type",
	"Egress Signaling Type",
	"Ingress Far End Switch Type",
	"Egress Far End Switch Type",
	"Carrier Code of Carrier who Owns iTG Far End",
	"Carrier Code of Carrier who Owns eTG Far End",
	"Calling Party Category",
	"Dialed Number",
	"Carrier Selection Information",
	"Called Number Numbering Plan",
	"Generic Address Parameter",
	"Disconnect Initiator",
	"Egress Trunk Group Name ",
	"Egress Protocol Variant Specific Data",
	"Incoming Calling Number",
	"AMA Call Type",
	"Message Billing Indicator (MBI)",
	"LATA",
	"Route Index Used",
	"Calling Party Presentation Restriction",
	"Incoming ISUP Charge Number",
	"Incoming ISUP Nature Of Address",
	"Dialed Number Nature of Address",
	"Ingress Codec Info",
	"Egress Codec Info",
	"Ingress RTP Packetization Time",
	"Global Call ID (GCID)",
	"Terminated With Script Execution",
	"Originator Echo Cancellation",
	"Terminator Echo Cancellation",
	"Charge Flag",
	"AMA slp ID",
	"AMA BAF Module",
	"AMA Set Hex AB Indication",
	"Service Feature ID",
	"FE Parameter",
	"Satellite Indicator",
	"PSX Billing Info",
	"Originating TDM Trunk Group Type",
	"Terminating TDM Trunk Group Type",
	"Ingress Trunk Member Number",
	"Egress Trunk Group ID",
	"Egress Switch ID",
	"Ingress Local ATM Address",
	"Ingress Remote ATM Address",
	"Egress Local ATM Address",
	"Egress Remote ATM Address",
	"PSX Call Type",
	"Outgoing Route Trunk Group ID",
	"Outgoing Route Message ID",
	"Incoming Route ID",
	"Calling Name",
	"Calling Name Type",
	"Incoming Calling Party Numbering Plan",
	"Outgoing Calling Party Numbering Plan",
	"Calling Party Business Group ID",
	"Called Party Business Group ID",
	"Calling Party PPDN",
	"Ticks from Setup Msg to Last Route Attempt",
	"Disconnect Time (MM/DD/YYYY)",
	"Billing Number Nature of Address",
	"Incoming Calling Number Nature of Address",
	"Egress Trunk Member Number ",
	"Selected Route Type",
	"Cumulative Route Index",
	"Call Disconnect Reason Sent to Ingress",
	"Call Disconnect Reason Sent to Egress",
	"ISDN PRI Calling Party Subaddress",
	"Outgoing Trunk Group Number in EXM",
	"Ingress Local Signaling IP Address",
	"Ingress Remote Signaling IP Address",
	"Record Sequence Number",
	"Transmission Medium Requirement",
	"Information Transfer Rate",
	"USI User Info Layer 1 ",
	"Unrecognized Raw ISUP Calling Party Category",
	"FSD: Release Link Trunking",
	"FSD: Two B-Channel Transfer",
	"Calling Party Business Unit",
	"Called Party Business Unit",
	"FSD: Redirecting",
	"FSD: Ingress Release Link Trunking",
	"PSX Index ",
	"PSX Congestion Level",
	"PSX Processing Time (milliseconds)",
	"Script Name",
	"Ingress External Accounting Data",
	"Egress External Accounting Data",
	"Egress RTP Packetization Time",
	"Answer Supervision Type",
	"Ingress Sip Refer & Replaces Feature Specific Data",
	"Egress Sip Refer Feature Specific Data",
	"Network Transfers Feature Specific Data",
	"Call Condition",
	"Toll Indicator",
	"Generic Number (Number)",
	"Generic Number Presentation Restriction Indicator",
	"Generic Number Numbering Plan",
	"Generic Number Nature of Address",
	"Generic Number Type",
	"Final Attempt Indicator",
	"Originating Trunk Type",
	"Terminating Trunk Type",
	"Remote GSX Billing Indicator",
	"Extra Disconnect Reason",
	"VPN Calling Private Presence Number",
	"VPN Calling Public Presence Number",
	"External Furnish Charging Info",
	"Announcement Id",
	"Network Data - Source Information",
	"Network Data - Partition ID ",
	"Network Data - Network ID",
	"Network Data - NCOS",
	"ISDN access Indicator",
	"Call Disconnect Location",
	"Call Disconnect Location Transmitted to Ingress",
	"Call Disconnect Location Transmitted to Egress",
	"Network Call Reference - Call Identity",
	"Network Call Reference - Signaling Point Code",
	"Ingress MIME Protocol Variant Specific Data",
	"Egress MIME Protocol Variant Specific Data",
	"Video Data - Video Bandwidth, Video Call Duration, Ingress/Egress IP video Endpoint",
	"SVS Customer",
	"SVS Vendor - Deprecated in 7.2.2 as part of Pcr1624",
	"Call To Test PSX",
	"Psx Overlap Route Requests",
	"Call Setup Delay",
	"Overload Status",
	"reserved1",
	"reserved2",
	"Mlpp Precedence Level",
	"reserved3",
	"reserved4",
	"reserved5",
	"reserved6",
	"reserved7",
	"reserved8",
	"reserved9",
	"reserved10",
	"Global Charge Reference",
);

$CDRS = array();								// Make a final list of fully field mapped CDR records
foreach ($LOGPARSED as $PARSED)					// Loop through parsed CSV data and map to the correct CDR fields
{
	$i = 0;										// Counter for field iteration through data
	$CDRTYPE = $PARSED[0];						// The first field type is always the CDR type
	if ( isset( $CDRFIELDS[$CDRTYPE] ) )		// If the CDR type is known:
	{
		$CDR = array();							// Container for our final CDR with mapped key,value pairs
		foreach($CDRFIELDS[$CDRTYPE] as $FIELD)	// Loop through CDR fields and associate them with known values
		{
			$VALUE = $PARSED[$i++];				// Get the value for this field
			if (strpos($VALUE,",") !== false)	// IF our final parsed value contains commas, it is a SUB RECORD!!!
			{
				$VALUE = \metaclassing\Utility::dumperToString(str_getcsv($VALUE) );	// FOR NOW! v1.0 just parse it into dumpered output for display!
			}
			$CDR[$FIELD] = $VALUE;				// assign the new CDR field a value and increment our position in the parsed array
		}
		array_push($CDRS,$CDR);					// Jam the newly created key,value'd CDR into the CDR array
	}else{
		print "<b>WARNING: DROPPED UNKNOWN CDR TYPE '{$PARSED[0]}'</b><br>\n";
	}
}

//\metaclassing\Utility::dumper($CDRS); die("CROAK!\n");	// Debugging

// Some basic html formatting for an output table
$WIDTH = array();	$i = 1;
$WIDTH[$i++]= 500;
$WIDTH[$i++]= 500;
$WIDTH[0]	= array_sum($WIDTH); $i = 0;

$RECORDCOUNT = count($CDRS);
$i = 0;
// Loop through CDR's and display them
foreach ($CDRS as $CDR)
{
	$i++; // Count the location in the CDR array
	// Print the CDR table header
	$j = 0;
	print <<<END
	<table class="report" border="0" cellpadding="1" cellspacing="0" width="{$WIDTH[$j++]}">
		<thead>
			<caption class="report">CDR Record ({$i} of {$RECORDCOUNT})</caption>
			<tr>
				<th class="report" width="{$WIDTH[$j++]}">CDR Field</th>
				<th class="report" width="{$WIDTH[$j++]}">CDR Value</th>
			</tr>
		</thead>
		<tbody>
END;
	// Loop through the fields in the CDR and print them out
	$j = 0;
	foreach ($CDR as $FIELD => $VALUE)
	{
		$ROWCLASS = "row".(($j++ % 2)+1);	// Formatting alternating colored lines
		print <<<END
			<tr class="{$ROWCLASS}">
				<td class="report">{$FIELD}</td>
				<td class="report">{$VALUE}</td>
			</tr>
END;
	}
	// Print the table footer
	print <<<END
		</tbody>
	</table>
	<br>
END;

}

print $HTML->footer();

?>

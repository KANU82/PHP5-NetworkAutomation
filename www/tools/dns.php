<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("DNS Record Generator",$HTML->thispage);
print $HTML->header("DNS Record Tool");

$SEARCH = array(
				"category"      => "management",
				"type"          => "device_network_cisco%",
				);
$RESULTS = Information::search($SEARCH);
$COUNT = count($RESULTS);

$i=0;
foreach ($RESULTS as $DEVICEID)
{
	$DEVICE = Information::retrieve($DEVICEID);

	if ($i++) { print "<hr size=1>\n"; }

	$SERVER = "knedcxiwp005";	// Our DNS server target
	$DEVICE->data["name"] = preg_replace("/\//","-",$DEVICE->data['name']); // Replace slashes in device names with hyphens!
	$HOSTNAME = $DEVICE->data["name"];
	$DOMAINNAME = "net.company.com";

	$RUN = $DEVICE->data["run"];
	$RUN_LINES = explode("\n",$RUN);

	$CISCO = new \metaclassing\Cisco();
	$CISCO->parse_config($RUN_LINES);

	$INTERFACES = $CISCO->config('interface');

	if (!$INTERFACES) {
		print "<p>Cannot get interfaces from device: {$HOSTNAME}</p>\n";
		continue;
	}

	print "<pre>rem $HOSTNAME.$DOMAINNAME ID $DEVICEID\n";
	$FIRSTINT = "";
	foreach ($INTERFACES as $iname => $idata)
	{
		$INTERFACE = $CISCO->iface($iname);
		$INTNAME = $CISCO->dnsabbreviate($iname);
		if ($FIRSTINT == "") { $FIRSTINT = $INTNAME; } // Find first interface name of a given device and save it for later...
		$IPV4ADDR = $INTERFACE['ipv4addr'];
		if ($HOSTNAME == "") { die("ERROR! HOSTNAME NOT SET FOR THIS DEVICE!\n"); }
		if ($INTNAME == "") { die("ERROR! INTERFACE {$iname} FAILED TO DNS ABBREVIATE!\n"); }

		if(preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/',$IPV4ADDR,$IP_OCTETS) && $INTNAME != "" && HOSTNAME != "")
		{
			// Add interface forward
			print "DNSCMD $SERVER /RecordDelete $DOMAINNAME $INTNAME.$HOSTNAME A /f\n";
			print "DNSCMD $SERVER /RecordAdd $DOMAINNAME $INTNAME.$HOSTNAME A $IP_OCTETS[1].$IP_OCTETS[2].$IP_OCTETS[3].$IP_OCTETS[4]\n";
			// Add interface reverse
			if ($IP_OCTETS[1] == 10 || $IP_OCTETS[1] == 172 || $IP_OCTETS[1] == 192)
			{
				print "DNSCMD $SERVER /RecordDelete $IP_OCTETS[1].in-addr.arpa $IP_OCTETS[4].$IP_OCTETS[3].$IP_OCTETS[2] PTR /f\n";
				print "DNSCMD $SERVER /RecordAdd $IP_OCTETS[1].in-addr.arpa $IP_OCTETS[4].$IP_OCTETS[3].$IP_OCTETS[2] PTR $INTNAME.$HOSTNAME.$DOMAINNAME\n";
			}
		}
	}
	$MGMTINT = $CISCO->dnsabbreviate(\metaclassing\Cisco::findManagementInterface($RUN_LINES));
	if ($MGMTINT == "") { $MGMTINT = $FIRSTINT; } // Last ditch effort, if we cant find a mgmt interface in the config, use first interface with an IP!

	$MGMTDEL = "DNSCMD $SERVER /RecordDelete $DOMAINNAME $HOSTNAME CNAME /f\n";
	$MGMTADD = "DNSCMD $SERVER /RecordAdd $DOMAINNAME $HOSTNAME CNAME $MGMTINT.$HOSTNAME.$DOMAINNAME\n";
	print "rem $HOSTNAME.$DOMAINNAME Management Interface May Be $MGMTINT\n";
	print $MGMTDEL;
	print $MGMTADD;
	print "</pre>\n";

	\metaclassing\Utility::flush();
	unset($DEVICE); // save memory!
}
print $HTML->footer();

?>

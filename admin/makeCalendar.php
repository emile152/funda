<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

$header[] = "BEGIN:VCALENDAR";
//$header[] = "PRODID:-//Google Inc//Google Calendar 70.9054//EN";
$header[] = "VERSION:2.0";
//$header[] = "CALSCALE:GREGORIAN";
//$header[] = "METHOD:PUBLISH";
$header[] = "X-WR-CALNAME:". $ScriptTitle;
//$header[] = "X-WR-TIMEZONE:Europe/Amsterdam";
$header[] = "X-WR-CALDESC:Kalender met daarin de tijdstippen waarop huizen van funda.nl open huis hebben.";
//$header[] = "BEGIN:VTIMEZONE";
//$header[] = "TZID:Europe/Amsterdam";
//$header[] = "X-LIC-LOCATION:Europe/Amsterdam";
//$header[] = "BEGIN:DAYLIGHT";
//$header[] = "TZOFFSETFROM:+0100";
//$header[] = "TZOFFSETTO:+0200";
//$header[] = "TZNAME:CEST";
//$header[] = "DTSTART:19700329T020000";
//$header[] = "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU";
//$header[] = "END:DAYLIGHT";
//$header[] = "BEGIN:STANDARD";
//$header[] = "TZOFFSETFROM:+0200";
//$header[] = "TZOFFSETTO:+0100";
//$header[] = "TZNAME:CET";
//$header[] = "DTSTART:19701025T030000";
//$header[] = "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU";
//$header[] = "END:STANDARD";
//$header[] = "END:VTIMEZONE";

$footer[] = "END:VCALENDAR";

if(!isset($_REQUEST['id'])) {
	# Kijken welke huizen uit de kalender-database gisteren open huis gehad hebben.
	# Deze huizen moeten klaar gemaakt worden zodat er weer een trigger komt mochten zij open huis hebben 
	$start	= mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
	$end		= mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));
	
	$sql		= "SELECT * FROM $TableCalendar	WHERE $CalendarEnd BETWEEN $start AND $end";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	do {
		removeOpenHuis($row[$CalendarHuis]);	
	} while($row = mysql_fetch_array($result));
}

$maandGeleden	= mktime(0, 0, 0, date('m')-1, date('d'), date('Y'));

if(isset($_REQUEST['id'])) {
	$Users = array(1);
} else {
	$Users = getUsers();
}

foreach($Users as $user) {
	if(isset($_REQUEST['id'])) {
		$sql		= "SELECT * FROM $TableCalendar WHERE $TableCalendar.$CalendarHuis = ". $_REQUEST['id'] ." AND $CalendarStart > $maandGeleden";
	} else {
		$UserData	= getMemberDetails($user);	
		$sql		= "SELECT * FROM $TableCalendar, $TableResultaat, $TableZoeken WHERE $TableCalendar.$CalendarHuis = $TableResultaat.$ResultaatID AND $TableResultaat.$ResultaatZoekID = $TableZoeken.$ZoekenKey AND $TableZoeken.$ZoekenUser = $user AND $TableCalendar.$CalendarStart > $maandGeleden";
	}
	
	$result = mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	do {
		$start		= $row[$CalendarStart];
		$einde		= $row[$CalendarEnd];
		$fundaID	= $row[$CalendarHuis];
		$data		= getFundaData($fundaID);
	
		$description	= array();
		$description[] = 'http://www.funda.nl/'. $fundaID;
				
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:FUNDA_OPEN_HUIS-". $fundaID .'-'. date("Ymd", $start);
		$ics[] = "DTSTART:". date("Ymd\THis", $start);
		$ics[] = "DTEND:". date("Ymd\THis", $einde);	
		$ics[] = "LAST-MODIFIED:". date("Ymd\THis\Z", time());
		$ics[] = "SUMMARY:Open Huis '". $data['adres'] ."'";
		$ics[] = "LOCATION:". $data['adres'] .", ". $data['plaats'];
		$ics[] = "DESCRIPTION:". implode('\n', $description);
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
	} while($row = mysql_fetch_array($result));
	
	if(isset($_REQUEST['id'])) {
		header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false); 
		header("Pragma: no-cache");
		header("Cache-control: private");
		header('Content-type: application/ics');
		header('Content-Disposition: attachment; filename="'. formatAddress($data['adres']) .'.ics"');
		echo implode("\r\n", $header);
		echo "\r\n";
		echo implode("\r\n", $ics);
		echo "\r\n";
		echo implode("\r\n", $footer);		
	} else {
		$filename = '../../../download/'. str_replace(' ', '-', $ScriptTitle) .'_Open-Huis_'. $UserData['naam'] .'.ics';
	
		$file = fopen($filename, 'w+');
		fwrite($file, implode("\r\n", $header));
		fwrite($file, "\r\n");
		fwrite($file, implode("\r\n", $ics));
		fwrite($file, "\r\n");
		fwrite($file, implode("\r\n", $footer));
		fclose ($file);
		
		echo "<a href='$filename'>". $UserData['naam'] ."</a>";
		echo "\n<p>\n";
	}
}

function formatAddress($string) {
	$string	= ucwords($string);
	$string = str_replace('Van ', 'van ', $string);
	$string = str_replace('De ', 'de ', $string);
	//$string = str_replace(' Van ', ' van ', $string);
	
	$string = str_replace(' ', '', $string);
	return $string;
}
?>
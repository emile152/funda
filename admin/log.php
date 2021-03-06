<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(!isset($_REQUEST['bDag']) OR !isset($_REQUEST['bMaand']) OR !isset($_REQUEST['bJaar']) OR !isset($_REQUEST['bUur']) OR !isset($_REQUEST['bMin'])) {
	$bMin = 0;
	$bUur = 0;
	$bDag = date('d');
	$bMaand = date('m');
	$bJaar = date('Y');	
} else {
	$bMin = $_REQUEST['bMin'];
	$bUur = $_REQUEST['bUur'];
	$bDag = $_REQUEST['bDag'];
	$bMaand = $_REQUEST['bMaand'];
	$bJaar = $_REQUEST['bJaar'];
}

if(!isset($_REQUEST['eDag']) OR !isset($_REQUEST['eMaand']) OR !isset($_REQUEST['eJaar']) OR !isset($_REQUEST['eUur']) OR !isset($_REQUEST['eMin'])) {
	$eMin = 55;
	$eUur = 23;
	$eDag = date('d');
	$eMaand = date('m');
	$eJaar = date('Y');	
} else {
	$eMin = $_REQUEST['eMin'];
	$eUur = $_REQUEST['eUur'];
	$eDag = $_REQUEST['eDag'];
	$eMaand = $_REQUEST['eMaand'];
	$eJaar = $_REQUEST['eJaar'];
}

if(isset($_REQUEST['selectie']) AND $_REQUEST['selectie'] != '') {
	$selectie	= $_REQUEST['selectie'];
	$opdracht = substr($selectie, 1);
}

if(isset($_REQUEST['huis']) AND $_REQUEST['huis'] != '') {
	$huis = $_REQUEST['huis'];
} else {
	$huis = null;
}

if(isset($_REQUEST['debug']) AND $_REQUEST['debug'] != '') {
	$debug = $_REQUEST['debug'];
} else {
	$debug = 'nee';
}

if((isset($_REQUEST['info']) AND $_REQUEST['info'] != '') OR !isset($_REQUEST['logSearch'])) {
	$info = 'ja';	
} else {
	$info = 'nee';
}

if((isset($_REQUEST['error']) AND $_REQUEST['error'] != '') OR !isset($_REQUEST['logSearch'])) {
	$error = 'ja';
} else {
	$error = 'nee';
}

if(isset($_REQUEST['string'])) {
	$string = $_REQUEST['string'];
} else {
	$string = '';
}

$begin	= mktime($bUur, $bMin, 0, $bMaand, $bDag, $bJaar);
$eind		= mktime($eUur, $eMin, 59, $eMaand, $eDag, $eJaar);

$sql		= "SELECT * FROM $TableLog WHERE $LogTime BETWEEN $begin AND $eind";
if($debug == 'ja')		$sql_OR[] = "$LogType = 'debug'";
if($info == 'ja')			$sql_OR[] = "$LogType = 'info'";
if($error == 'ja')		$sql_OR[] = "$LogType = 'error'";
if(is_array($sql_OR))	$sql .= " AND (". implode(" OR ", $sql_OR) .")";
if(isset($opdracht))	$sql .= " AND $LogOpdracht = '$opdracht'";
if(isset($huis))			$sql .= " AND $LogHuis = '$huis'";
if(isset($string))		$sql .= " AND $LogMessage like '%$string%'";

$result	= mysqli_query($db, $sql);
$aantal	= mysqli_num_rows($result);
$row		= mysqli_fetch_array($result);

$i = 0;
$selectie = '';
$deel_1 = $deel_2 = '';

do {
	$fundaData = getFundaData($row[$LogHuis]);
	$opdrachtData = getOpdrachtData($row[$LogOpdracht]);	
	$i++;
	
	$rij = "<tr>";
	$rij .= "	<td>". date("d-m H:i:s", $row[$LogTime]) ."</td>";
	$rij .= "	<td>&nbsp;</td>\n";
	$rij .= "	<td><a href='?". ($row[$LogOpdracht] != 0 ? 'selectie=Z'. $row[$LogOpdracht] .'&' : '') . ($row[$LogHuis] != 0 ? 'huis='. $row[$LogHuis] .'&' : '') ."bMin=$bMin&bUur=$bUur&bDag=$bDag&bMaand=$bMaand&bJaar=$bJaar&eMin=$eMin&eUur=$eUur&eDag=$eDag&eMaand=$eMaand&eJaar=$eJaar' title='". $opdrachtData['naam'] .'; '. $fundaData['adres'] ."'>". $row[$LogHuis] ."</a></td>";
	$rij .= "	<td>&nbsp;</td>\n";
	
	if($row[$LogType] == 'error') {
		$rij .= "	<td><b>". $row[$LogMessage] ."</b></td>";
	} elseif($row[$LogType] == 'debug') {
		$rij .= "	<td><i>". $row[$LogMessage] ."</i></td>";
	} else {
		$rij .= "	<td>". $row[$LogMessage] ."</td>";
	}
		
	$rij .= "</tr>";
	
	if($i > $aantal/2) {
		$deel_2 .= $rij;
	} else {
		$deel_1 .= $rij;
	}
} while($row = mysqli_fetch_array($result));

$dateSelection = makeDateSelection($bUur, $bMin, $bDag, $bMaand, $bJaar, $eUur, $eMin, $eDag, $eMaand, $eJaar);

$zoekScherm[] = "<form method='post' action='". $_SERVER['PHP_SELF'] ."'>";
$zoekScherm[] = "<input type='hidden' name='logSearch' value='ja'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Begindatum</b></td>";
$zoekScherm[] = "	<td rowspan='5'>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Zoekopdracht</b></td>";
$zoekScherm[] = "	<td rowspan='5'>&nbsp;</td>";
$zoekScherm[] = "	<td><b>String</b></td>";
//$zoekScherm[] = "	<td rowspan='5'><input type='submit' value='Zoeken' name='submit'></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>". $dateSelection[0] ."</td>";
$zoekScherm[] = "	<td>". makeSelectionSelection(true, true, $selectie) ."</td>";
$zoekScherm[] = "	<td><input type='text' name='string' value='$string' size=50></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Einddatum</b></td>";
$zoekScherm[] = "	<td><b>Huis</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>". $dateSelection[1] ."</td>";
$zoekScherm[] = "	<td><select name='huis'>";
$zoekScherm[] = "	<option value=''>Alle</option>";

$sql = "SELECT $TableHuizen.$HuizenID, $TableHuizen.$HuizenAdres FROM $TableHuizen, $TableLog WHERE $TableLog.$LogHuis = $TableHuizen.$HuizenID AND $TableLog.$LogTime BETWEEN $begin AND $eind GROUP BY $TableHuizen.$HuizenID ORDER BY $TableHuizen.$HuizenAdres";
$result	= mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);

do {
	$zoekScherm[] = "	<option value='". $row[$HuizenID] ."'". ($huis == $row[$HuizenID] ? ' selected' : '') .">". urldecode($row[$HuizenAdres]) ."</option>";
} while($row = mysqli_fetch_array($result));

$zoekScherm[] = "	</select></td>";
//$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td align='right'><input type='submit' value='Zoeken' name='submit'></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><input type='checkbox' name='error' value='ja'". ($error == 'ja' ? ' checked' : '') ."> Error&nbsp;&nbsp;&nbsp;";
$zoekScherm[] = "	<input type='checkbox' name='info' value='ja'". ($info == 'ja' ? ' checked' : '') ."> Info&nbsp;&nbsp;&nbsp;";
$zoekScherm[] = "	<input type='checkbox' name='debug' value='ja'". ($debug == 'ja' ? ' checked' : '') ."> Debug";
$zoekScherm[] = "	</td>";

if(isset($huis)) {
	$zoekScherm[] = "	<td align='right'>huis op <a href='http://funda.nl/$huis'>funda.nl</a> | <a href='edit.php?id=$huis'>lokaal</a></td>";
} else {
	$zoekScherm[] = "	<td>&nbsp;</td>";	
}

$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";
$zoekScherm[] = "</form>";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td valign='top' align='center' colspan=2>". showBlock(implode("\n", $zoekScherm)) ."</td>\n";
echo "</tr>\n";
echo "<tr>\n";

if($deel_1 != '') {
	echo "	<td width='50%' valign='top' align='center'>". showBlock("<table>". $deel_1 ."</table>") ."</td>\n";
	echo "	<td width='50%' valign='top' align='center'>". showBlock("<table>". $deel_2 ."</table>") ."</td>\n";
} else {
	echo "	<td width='100%' valign='top' align='center'>". showBlock("<table>". $deel_2 ."</table>") ."</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;

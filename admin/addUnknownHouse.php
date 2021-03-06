<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['urls'])) {
	if($_POST['lijstID'] == '') {
		$nieuwNaam = 'NieuweHuizen_'.time();
		$lijstID = saveUpdateList('', $_SESSION['UserID'], 1, $nieuwNaam);
	} else {
		$lijstID = $_POST['lijstID'];
	}
	
	$dataset = explode("\n", $_POST['urls']);
		
	if(count($dataset) > 0){		
		foreach($dataset as $huis) {
			$huis = str_replace('http://www.funda.nl', '', $huis);
			$huis = str_replace('https://www.funda.nl', '', $huis);
						
			$mappen = explode("/", $huis);						
			$delen = explode("-", $mappen[3]);
			
			$data['url'] = implode('/', array_slice($mappen, 0, 4));
			$data['id'] = $fundaID = $delen[1];
			$data['adres'] = ucwords(implode(' ', array_slice($delen, 2)));
			$data['plaats'] = ucwords($mappen[2]);
			
			$temp = splitStreetAndNumberFromAdress($data['adres']);
			$data = array_merge($data, $temp);
						
			if(!saveHouse($data, array())) {
				$deel_1 .= formatStreetAndNumber($fundaID). " aan dB toevoegen is mislukt<br>\n";
			} else {
				if(!addHouse2List($fundaID, $lijstID)) {
					$deel_1 .= formatStreetAndNumber($fundaID). " aan lijst $lijstID toevoegen is mislukt<br>\n";
				} else {					
					mark4Details($fundaID);
					addUpdateStreetDb($data['straat'], $data['plaats']);
					$deel_1 .= formatStreetAndNumber($fundaID). " toegevoegd<br>\n";
				}
			}
		}
	} else {
		$deel_1 = "<p>Selectie bevat geen huizen";
	}
} else {
	$Lijsten = getLijsten($_SESSION['UserID'], '');
	
	$deel_1 = "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";	
	$deel_1 .= "<table border=0>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>Voer de funda-url's (http://www.funda.nl/koop/...) in elk op een nieuwe regel</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td><textarea name='urls' cols='50' rows='5'></textarea></td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>Selecteer de lijst<br>";
	$deel_1 .= "	<select name='lijstID'>\n";
	$deel_1 .= "	<option value=''> * nieuwe lijst *</option>\n";
		
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		$deel_1 .= "		<option value='$LijstID'>". $LijstData['naam'] ."</option>\n";
	}
		
	$deel_1 .= "</select>\n";	
	$deel_1 .= "	</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>&nbsp;</td>\n";
	$deel_1 .= "</tr>\n";	
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td align='center'><input type='submit' name='toevoegen' value='Voeg toe'></td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "</table>\n";
	$deel_1 .= "</form>\n";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td width='25%'>&nbsp;</td>\n";
echo "	<td width='50%' valign='top' align='center'>". showBlock($deel_1) . "</td>\n";
echo "	<td width='25%'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
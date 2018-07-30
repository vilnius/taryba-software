﻿<?php

date_default_timezone_set('Europe/Vilnius');
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', TRUE);

$votingFile = 'voting.data';
$sessionFile = 'session.data';
$meetingFile = 'meeting.data';
$discussionFile = 'discussion.data';
$participantFile = 'participant.data';
$resultFile = 'result.txt';

$pirmininkas = 'Šimašius';
$pirmininkas_seat = 2;

$resultPath = 'result' . DIRECTORY_SEPARATOR;
if (is_dir($resultPath) === FALSE) {
	$old_umask = umask(0);
	mkdir($resultPath, 0777, true);
	umask($old_umask);
}

$dalyviaiFile = "dalyviai.txt";
/* Paima reikiamus option'us, kai pradedamas balsavimas */

function votingStarted($xmlString) {
	$voteStartArray = Array($xmlString->Voting["Id"],
		$xmlString->Voting->VotingData["Subject"],
		$xmlString->Voting->VotingData["Name"],
		$xmlString["TimeStamp"]);
	return $voteStartArray;
}

/* Paima reikiamus optionus, kai baigiamas balsavimas */

function votingStopped($xmlString, $voteStartArray) {
	$votesArray = Array();
	foreach ($xmlString->Voting->VotingResults->VotingTotalResults->VotingAnswerResults->VotingAnswerResultContainer as $VotingAnswerResultContainer) {
		array_push($votesArray, $VotingAnswerResultContainer["NumberOfCasts"]);
	}

	$votersArray = Array();
	foreach ($xmlString->Voting->VotingResults->IndividualResults->VotingIndividualResultContainer as $VotingIndividualResultContainer) {
		foreach ($VotingIndividualResultContainer->Participant as $Participant) {
			array_push($votersArray, array($Participant["Id"],
				$VotingIndividualResultContainer['AnswerId'],
				$Participant->ParticipantData["FirstName"],
				$Participant->ParticipantData["MiddleName"],
				$Participant->ParticipantData["LastName"]));
		}
	}

	// Viska surenkam i viena masyva
	$voteResult = Array($voteStartArray,
		Array($xmlString->Voting->VotingResults->VotingTotalResults["Approved"],
			$xmlString->Voting->VotingResults->VotingTotalResults["NumberOfAuthorizedPresentParticipantsWithoutVote"],
			$xmlString->Voting->VotingResults->VotingTotalResults["RequiredMajority"],
			$xmlString->Voting->VotingResults->VotingTotalResults["NumberOfAuthorizedPresentParticipants"]),
		$votesArray, $votersArray);

	return $voteResult;
}

/* Kai prasideda posedis */

function meetingStarted($xmlString) {
	// Info apie posedi
	$meeting = Array(
		$xmlString->Meeting["Id"],
		$xmlString->Meeting->MeetingData["Subject"],
		$xmlString->Meeting->MeetingData["DateTime"]
	);

	// Info apie posedzio klausimus
	$meetingList = Array();
	foreach ($xmlString->Meeting->Sessions->SessionContainer as $SessionContainer) {
		array_push($meetingList, array($SessionContainer["Id"],
			$SessionContainer->SessionData["Subject"],
			$SessionContainer->SessionData["Done"])
		);
	}

	// Surenkam visus galimus dalyvius
	$meetingParticipants = Array();
	foreach ($xmlString->Meeting->Participants->ParticipantContainer as $ParticipantContainer) {
		array_push($meetingParticipants, Array($ParticipantContainer["Id"],
			$ParticipantContainer->ParticipantData["Present"],
			$ParticipantContainer->ParticipantData["VotingAuthorisation"],
			$ParticipantContainer->ParticipantData["FirstName"],
			$ParticipantContainer->ParticipantData["LastName"])
		);
	}

	return Array($meeting, $meetingList, $meetingParticipants);
}

/* Gaunamas atnaujintas dalyvis */

// TODO: Naudojamas tik bendu_nutarimu.php ir jis praktiskai nieko nedaro. Ar vis dar jo reikia?
function bendruParticipant($xmlString) {
	$bendruParticipant = Array();
	foreach ($xmlString->Participant as $Participant) {
		$bendruParticipant = Array($Participant["Id"],
			$Participant->ParticipantData["Present"],
			$Participant->ParticipantData["VotingAuthorisation"],
			$Participant->ParticipantData["FirstName"],
			$Participant->ParticipantData["LastName"]
		);
	}
	return $bendruParticipant;
}

function bendruUpdatePresent($bendruParticipantList/* , $participantArray=null */) {
	/* Istrina tu paciu Participant ID pirmesnius irasus */
	foreach ($bendruParticipantList as $key => $participant) {
		foreach ($bendruParticipantList as $s_key => $s_participant) {
			if ($key != $s_key) {
				if ($participant[0] == $s_participant[0]) {
					unset($bendruParticipantList[$key]);
				}
			}
		}
	}
	$participantArray = resetArray($bendruParticipantList);
	return $participantArray;
}

function renderMeetingList($xmlString, $layout) {
	$meetingList = "";
	foreach ($xmlString[1] as $meeting) {
		switch ($layout) {
			case "ul":
				$meetingList .= "<li><span class='question r" . $meeting[0] . "'>" . $meeting[1] . "</span><div class='checkboxes'><input type='radio' name='r" . $meeting[0] . "' value='priimta'/><input type='radio' name='r" . $meeting[0] . "' value='nepriimta'/><input type='radio' name='r" . $meeting[0] . "' value='atmesta'/><input type='radio' name='r" . $meeting[0] . "' value='' style='display: none'/><img onclick='document.meetings.r" . $meeting[0] . "[3].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
				break;
			case "select":
				$meetingList .= "<option>" . $meeting[1];
				break;
		}
	}
	switch ($layout) {
		case "ul":
			$output = "<ul><span class='meeting'>" . $xmlString[0][1] . "</span>
		<div class='checkboxes checkboxes-desc'>
			<div class='vertical-text' style='color: #007935'>UŽ</div>
			<div class='vertical-text' style='color: #ff1515'>PRIEŠ</div>
			<div class='vertical-text' style='color: #ffea00'>ATMESTA</div>
		</div>
		<div style='clear: left'></div>" . $meetingList . "</ul>";
			// Mygtukas
			$output .= '<div class="submit"><input type="submit" value="Taikyti" name="apply-meeting"></div>';
			break;
		case "select":
			$output = "<SELECT NAME='posedis' id='posedis' onChange='naujasKlausimas()'>
				$meetingList
			   </select>";
			break;
	}
	return $output;
}

// Perrasytas ant JS faile bendru_nutarimu.php
/* function renderList($meetingArray) {
  $meetingList = "";
  for($i=1; $i<=45; $i++) {
  $meetingList .= "<li><span class='question r".$i."'>".$i."</span><div class='checkboxes'><input type='radio' name='r".$i."' value='priimta'/><input type='radio' name='r".$i."' value='nepriimta'/><input type='radio' name='r".$i."' value='atmesta'/><input type='radio' name='r".$i."' value='' style='display: none'/><img onclick='document.meetings.r".$i."[3].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
  }
  $output = "<ul><span class='meeting'>".$meetingArray[0][1]."</span>
  <div class='checkboxes checkboxes-desc'>
  <div class='vertical-text' style='color: #007935'>UŽ</div>
  <div class='vertical-text' style='color: #ff1515'>PRIEŠ</div>
  <div class='vertical-text' style='color: #ffea00'>ATMESTA</div>
  </div>
  <div style='clear: left'></div>".$meetingList."</ul>";
  return $output;
  } */

/* Paimamas sesijos pavadinimas */
function currentSession() {
	global $sessionFile;
	$status = 1;
	if (file_exists($sessionFile)) {
		$SessionOpen = fopen($sessionFile, 'r');
		while (!feof($SessionOpen)) {
			$xmlString = simplexml_load_string(fgets($SessionOpen));
			if ($xmlString == NULL) {
				break;
			}
			if ($xmlString["Type"] == "SessionStarted") {
				$sessionName = $xmlString->Session->SessionData["Subject"];
				$status = 1;
			}
			if ($xmlString["Type"] == "SessionStopped") {
				$sessionName = $xmlString->Session->SessionData["Subject"];
				$status = 0;
			}
		}
	}
	switch ($status) {
		case 1: return $sessionName;
			break;
		case 0: return;
			break;
	}
}

/* Isveda bendru balsavimo rezultatu html */

function renderTotalVotes($voteResult) {
	$i = 0;
	$voteInsert = '<font class="vote{4} countVotes" style="font-weight: bold; color: {1}; margin: 30px 25px 30px 0; {5}">{2} {3}</font>';
	while ($i != 3) {
	//foreach ($voteResult[2] as $VotingAnswerResultContainer) {
		if ($i == "0") {
			/**///			$color = "#007935"; $text="UŽ:"; $result=$VotingAnswerResultContainer[0];
			/**/ $color = "#007935";
			$text = "UŽ:";
			$result = 38;
			$voteStyleNR = "1";
			$voteStyle = "";
		} else if ($i == "1") {
			/**///			$color = "#ff1515"; $text="PRIEŠ:"; $result=$VotingAnswerResultContainer[1];
			/**/ $color = "#ff1515";
			$text = "PRIEŠ:";
			$result = 10;
			$voteStyleNR = "2";
			$voteStyle = "";
		} else if ($i == "2") {
			/**///			$color = "#ffea00"; $text="SUSILAIKĖ:"; $result=$VotingAnswerResultContainer[2];
			/**/ $color = "#ffea00";
			$text = "SUSILAIKĖ:";
			$result = 3;
			$voteStyleNR = "3";
			$voteStyle = "margin-right: 0;";
		}
		$i++;
		$printVote = str_replace("{1}", $color, $voteInsert);
		$printVote = str_replace("{2}", $text, $printVote);
		$printVote = str_replace("{3}", $result, $printVote);
		$printVote = str_replace("{4}", $voteStyleNR, $printVote);
		$printVote = str_replace("{5}", $voteStyle, $printVote);

		echo $printVote;
	}
}

// Rusiuoja daugiadimencini masyva
// $order turi buti arba "asc", arba "desc"
function sortmulti($array, $index, $order, $natsort = FALSE, $case_sensitive = FALSE) {
	if (is_array($array) && count($array) > 0) {
		foreach (array_keys($array) as $key)
			$temp[$key] = $array[$key][$index];
		if (!$natsort) {
			if ($order == 'asc')
				asort($temp);
			else
				arsort($temp);
		} else {
			if ($case_sensitive === true)
				natsort($temp);
			else
			/* natcasesort($temp); */
				uasort($temp, 'utf_8_lithunian::cmp');
			if ($order != 'asc')
				$temp = array_reverse($temp, TRUE);
		}
		foreach (array_keys($temp) as $key)
			if (is_numeric($key))
				$sorted[] = $array[$key];
			else
				$sorted[$key] = $array[$key];
		return $sorted;
	}
	return $sorted;
}

/* Naudojamas pakeisti tarybos nariu pavardziu lietuviskas raides */

function str_Array_replace($changeFrom, $changeTo, $Array) {
	for ($i = 0; $i < count($Array); $i++) {
		$Array[$i][4] = str_replace($changeFrom, $changeTo, $Array[$i][4]);
	}
	return $Array;
}

// Nustato masyvo indeksavima nuo 0
function resetArray($array) {
	$temp = Array();
	$index = 0;
	foreach ((array) $array as $element) {
		if (is_array($element)) {
			array_push($temp, $element);
		} else {
			$temp[$index] = $element;
			$index++;
		}
	}
	return $temp;
}

// tikrina ar masyve bent vienas elementas nera tuscias stringas
function isArrayNotEmpty($array) {
	$allElementsSet = true;
	foreach ($array as $element) {
		$allElementsSet = ($element == "") ? false : true;

		if (is_array($element)) {
			foreach ($element as $child_element) {
				$allElementsSet = ($child_element == "") ? false : true;

				if (is_array($element)) {
					foreach ($child_element as $baby_element) {
						$allElementsSet = ($baby_element == "") ? false : true;
						if (!$allElementsSet) {
							break;
						}
					}
				}
				if (!$allElementsSet) {
					break;
				}
			}
		}
		if (!$allElementsSet) {
			break;
		}
	}
	return $allElementsSet;
}

// Praejes laikas nuo balsavimo pabaigos iki dabar
function TimePassed() {
	global $resultFile;
	if (file_exists($resultFile)) {
		$resultOpen = file_get_contents($resultFile);
		$resultOpenArray = explode("\r\n", $resultOpen);
		$timeExplode = explode("T", $resultOpenArray[1]);
		$timeExplode2 = explode(".", $timeExplode[1]);

		$voteEndTime = $timeExplode[0] . " " . $timeExplode2[0];
		$voteEndOriginal = strtotime($voteEndTime);
		$nowTime = date("Y-m-d H:i:s");
		$nowOriginal = time($nowTime);
		$passed = $nowOriginal - $voteEndOriginal;
	}
	return $passed;
}

// TODO: Su juo laika skaiciuoja tik 3 viduriniamas. That's a BULL
function DeleteDiscussion($current, $inLine, $file) {
	// Jei dydis daugiau uz 200KB, laukiam galimybes istrint faila
	if (filesize($file) >= 204800) {
		if (count($current) == 0 && count($inLine) == 0) {
			/* < */ while (file_exists($file) || file_exists("Klijentas/" . $file)) {
				// TODO: Ar nereikia pirma trint klijento failo? Kadangi Klijentas sugeneruota faila kopijuoja prie PHP failu
				// TODO: Palikt duomenu failus tik prie klijento, papildomu kopiju prie PHP failu nereikia
				if (file_exists($file))
					unlink($file);
				if (file_exists("Klijentas/" . $file))
					unlink("Klijentas/" . $file);
			}
			/* > */ return 1;
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}

/* Sugeneruojama balsavimo eilute isvedimui i faila
 * 		changeNumber() turi but iskviesta pries sia funkcija,
 * 		kad balsavimo numeris butu pakeistas
 */

function generateOutput($voteResult, $kvorumas, $rezultatas) {
	$voteResult[0][1] = str_replace(Array("\n", "\r"), Array("", ""), $voteResult[0][1]);
	$voteResult[0][1] = trim($voteResult[0][1], "\n\r");
	$dalyvauja = $voteResult[1][3];
	$output = $voteResult[0][1] . "\n\nDalyvavo balsavime " . $dalyvauja . "\nUž " . $voteResult[2][0] . " Prieš " .
			$voteResult[2][1] . " Susilaikė " . $voteResult[2][2] . " Nebalsavo " . $voteResult[1][1] . "\n";

	if (!$kvorumas) {
		$output .= "Nėra kvorumo\n\n";
	} else {
		$output .= ucfirst(strtolower($rezultatas)) . "\n\n";
	}

	// Surenkam dalyvius pagal ju balsus
	$uz = "";
	$pries = "";
	$susilaike = "";
	$nebalsavo = "";
	foreach ($voteResult[3] as $balsas) {
		if ($balsas[1] == "1") {
			$uz .= $balsas[2] . " " . $balsas[4] . "\n";
		} elseif ($balsas[1] == "2") {
			$pries .= $balsas[2] . " " . $balsas[4] . "\n";
		} elseif ($balsas[1] == "3") {
			$susilaike .= $balsas[2] . " " . $balsas[4] . "\n";
		} elseif ($balsas[1] == "254") {
			$nebalsavo .= $balsas[2] . " " . $balsas[4] . "\n";
		}
	}
	$output .= "Už\n" . $uz . "\nPrieš\n" . $pries . "\nSusilaikė\n" . $susilaike . "\nAtidėta\n\nIšbraukta\n";

	$output = str_replace("\n", "\r\n", $output);

	return $output;
}

// Dalyvavusiu, bet nebalsavusiu sarasas irasimui i faila
function generateOutputNebalsavo($voteResult) {
	$output = "";
	foreach ($voteResult[3] as $balsas) {
		if ($balsas[1] == "254") {
			$output .= $balsas[2] . " " . $balsas[4] . "|";
		}
	}

	return $output;
}

/* Sugeneruojama balsavimo rezultatu eilute bendram nutarimui */

// TODO: Kam tas $Array?
function generateOutput_bendru($id, $result, $Array) {
	global $pirmininkas;

	// Bendru sutarimu turi tik viena dalyvi
	$dalyviai = $pirmininkas . "\n";

	$status_tag = "";
	switch (ucfirst($result)) {
		case "Priimta": $results = "Už\n$dalyviai\nPrieš\n\nSusilaikė\n\nAtidėta\n\nIšbraukta\n\nPerkelta\n";
			$count = array(1, 0, 0, 0, 0, 0);
			break;
		case "Nepriimta": $results = "Už\n\nPrieš\n$dalyviai\nSusilaikė\n\nAtidėta\n\nIšbraukta\n\nPerkelta\n";
			$count = array(0, 1, 0, 0, 0, 0);
			break;
		case "Atideta": $results = "Už\n\nPrieš\n\nSusilaikė\n\nAtidėta\n$dalyviai\nIšbraukta\n\nPerkelta\n";
			$count = array(0, 0, 0, 1, 0, 0);
			$status_tag = "<status>pertrauka svarstyme</status>";
			break;
		case "Isbraukta": $results = "Už\n\nPrieš\n\nSusilaikė\n\nAtidėta\n\nIšbraukta\n$dalyviai\nPerkelta\n";
			$count = array(0, 0, 0, 0, 1, 0);
			$status_tag = "<status>Išbraukta iš darbotvarkės</status>";
			break;
		case "Perkelta": $results = "Už\n\nPrieš\n\nSusilaikė\n\nAtidėta\n\nIšbraukta\n\nPerkelta\n$dalyviai";
			$count = array(0, 0, 0, 0, 0, 1);
			$status_tag = "<status>perkeltas į kitą posėdį</status>";
			break;
	}
	//$output = ($id + 2) . ". Bendru sutarimu\n";
	$output = $id  . ". Bendru sutarimu\n";
	$output .= ($status_tag != "") ? $status_tag . "\n\n" : "\n";
	$output .= "Dalyvavo balsavime 1\n";
	$output .= "Už " . $count[0] . " Prieš " . $count[1] . " Susilaikė " . $count[2] . " Atidėta " . $count[3] . " Išbraukta " . $count[4] . " Perkelta " . $count[5] . "\n";
	$output .= ucfirst($result) . " bendru sutarimu\n\n";
	$output .= $results;
	$output = str_replace("\n", "\r\n", $output);

	return $output;
}

/* Generuojam html'a slaptam balsavimui */

function generateHTML($result) {
	$output = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title> </title>
	</head><body>
			<div id="vote-results">';
	// Loopinam per visus slaptus balsavymus. Dažniausiai vistiek bus 1
	foreach ($result['teiginiai'] as $key => $value) {
		// Isrenkam rezultato pavadinima
		switch (true) {
			case ($result['teiginiai'][$key]['vote_result'] == 'priimta' || $result['teiginiai'][$key]['vote_result'] == 'nepriimta'):
				$vote_result = ucfirst($result['teiginiai'][$key]['vote_result']);
				break;
			case $result['teiginiai'][$key]['vote_result'] == 'nkvorumas':
				$vote_result = "Nėra kvorumo";
				break;
		}
		$output .= '<table style="width: 542px;">
				<tr>
					<td width="25%" class="short" valign="top">Būsena</td>
					<td width="75%" class="long" valign="top"><img src="http://www.vilnius.lt/pvstest/images/actions/aproved.gif"> ' . $vote_result . '</td>
				</tr>
				<tr>
					<td width="25%" class="short" valign="top">Narių balsavimo rezultatai</td>
					<td width="75%" class="long" valign="top"><table width="100%" cellspacing="8" cellpadding="0"><tbody>';

		$output .= '		<tr>
							<td align="left" style="width: 53px;">Balsas</td>
							<td align="left"> </td>
							<td align="right" style="width: 88px;">Balsų skaičius</td>
							<td align="right" style="width: 35px;">%</td>
						</tr>';

		// Balsu skaiciaus atvaizdavimo linijose ilgio skaiciavimui
		$viso_balsu = $result['teiginiai'][$key]['uz'] + $result['teiginiai'][$key]['pries'] + $result['teiginiai'][$key]['susilaike'];
		$max = max($result['teiginiai'][$key]['uz'], $result['teiginiai'][$key]['pries'], $result['teiginiai'][$key]['susilaike']);

		// 3 atsakymu variantai
		for ($i = 0; $i < 3; $i++) {
			// Isrenkam ka spauzdinsim
			switch (key($result['teiginiai'][$key])) {
				case 'uz':
					$text = "Už";
					$balsu = $result['teiginiai'][$key]['uz'];
					// Perasom tekstus pagal pasirinkta tipa (del atleidimo/pasitikejimo)
					switch ($result['type_id']) {
						case 1: $text = "Atleisti";
							break;
						case 2: $text = "Pasitikiu";
							break;
					}
					break;
				case 'pries':
					$text = "Prieš";
					$balsu = $result['teiginiai'][$key]['pries'];
					// Perasom tekstus pagal pasirinkta tipa (del atleidimo/pasitikejimo)
					switch ($result['type_id']) {
						case 1: $text = "Neatleisti";
							break;
						case 2: $text = "Nepasitikiu";
							break;
					}
					break;
				case 'susilaike':
					$text = "Susilaikė";
					$balsu = $result['teiginiai'][$key]['susilaike'];
					break;
			}
			// Isvedam balsus
			// TODO: Pakeist i tikrinima ar yra masyve
			if (key($result['teiginiai'][$key]) == 'uz' || key($result['teiginiai'][$key]) == 'pries' || key($result['teiginiai'][$key]) == 'susilaike') {
				$percents = 100 * $balsu / $viso_balsu;
				$percents = round($percents, 2);
				$width = 100 * current($result['teiginiai'][$key]) / $max;
				$output .= '<tr>
								<td align="left" style="width: 53px;">' . $text . '</td>
								<td align="left"><img src="http://www.vilnius.lt/pvstest/images/blueLine.gif" border="0" height="5" width="' . $width . '%"></td>
								<td align="right" style="width: 88px;">' . $balsu . '</td>
								<td align="right" style="width: 35px;">' . $percents . '</td>
							</tr>';
			}
			// Imam sekanti masyvo elementa
			next($result['teiginiai'][$key]);
		}
		$output .= '
				</tbody></table></td></tr>
		</table>';
	}
	$output .= '
	</div></body></html>';

	return $output;
}

/* Keičiam posėdžio pavadinima. Reikia pridėt 2 prie balsavimo numerio
 * 	$type - tipas
 * 		0 - posedžio pavadinimo formavimas
 * 		1 - nebalsavusiuju failo pavadinimo formavimas
 */

function changeNumber($title, $type) {
	// ieskom galimu skirtuku
	$pos_d = strpos($title, ".");
	$pos_s = strpos($title, " ");
	// naudojam ta skirtuka, kuris pirmesnis, arba nieko
	if (($pos_d < $pos_s && $pos_d != FALSE) || ($pos_s == FALSE && $pos_d != FALSE)) {
		$seperator_pos = $pos_d;
	} elseif (($pos_d > $pos_s && $pos_s != FALSE) || ($pos_d == FALSE && $pos_s != FALSE)) {
		$seperator_pos = $pos_s;
	} else {
		$seperator_pos = 0;
	}
	//isskaidom i numeri ir pavadinima

	$number = substr($title, 0, $seperator_pos);
	$title = substr($title, $seperator_pos);
	switch ($type) {
		case 0:
			//$title = (($seperator_pos != 0) ? ($number + 2) . "" . $title : $title);
			$title = (($seperator_pos != 0) ? $number  . "" . $title : $title);
			break;
		case 1: // Nebalsavusiuju failo pavadinimas
			$title = ($number) . "_nebalsavo.txt";
			break;
		default:
	}

	return $title;
}

/* Isvedamas rezultatu failas */

function writerResultFile($output, $filename) {
	global $resultPath;
	$filePath = $resultPath . date("Y-m-d") . DIRECTORY_SEPARATOR . $filename;
	//if(!is_dir($resultPath)) { mkdir($resultPath); }
	// jei neturim tokio failo, arba turim slapto balsavimo, kuri galima perrasyt
	if (!file_exists($filePath) || strstr($filename, 'secret')) {
		if (!is_dir($resultPath)) {
			mkdir($resultPath);
		}
		if (!is_dir($resultPath . date("Y-m-d"))) {
			mkdir($resultPath . date("Y-m-d"));
		}
		$fo = fopen($filePath, 'w') or die("Nepavyko atidaryti failo");
		fwrite($fo, $output);
		fclose($fo);

		return 1;
	}
	else
		return 0;
}

/* Ikelia rezultatu faila i FTP */

function uploadToFTP($filename, $ftp, $username, $password) {
	global $resultPath, $dalyviaiFile;
	$connection = ftp_connect($ftp) or die("Nerastas '$ftp' serveris");
	$login_result = ftp_login($connection, $username, $password) or die("Nepavyko prisijungti prie '$ftp' serverio");
	// Keiciam prisijungimo tipa
	ftp_pasv($connection, true);

	switch (isset($filename)) {
		case true:
			$local_file = $resultPath . date("Y-m-d") . DIRECTORY_SEPARATOR . $filename;
			$remote_file = $filename;
			break;
		case false:
			$local_file = $resultPath . date("Y-m-d") . DIRECTORY_SEPARATOR . $dalyviaiFile;
			$remote_file = $dalyviaiFile;
			// Jei dalyviu failas jau yra, istrinam ji
			if (ftp_size($connection, $remote_file) != -1) {
				ftp_delete($connection, $remote_file);
			}
			break;
	}
	$dir = date("Y-m-d");
	if (ftp_chdir($connection, $dir) == false) {
		ftp_mkdir($connection, $dir);
		ftp_chdir($connection, $dir);
	}
	// Jei negaunamas failo dydis, failo nera
	if (ftp_size($connection, $remote_file) == -1) {
		if (ftp_put($connection, $remote_file, $local_file, FTP_BINARY)) {

		} else {
			echo "Nepavyko įkelti $local_file failo\n";
			return -1;
		}
	}
	ftp_close($connection);

	return 1;
}

/* Papildom posėdžio dalyviu sarasa naujo balsavimo dalyviais */

function updateDalyviai($voteResult) {
	global $resultPath;
	$dalyviaiFile = $resultPath . date("Y-m-d") . DIRECTORY_SEPARATOR . "dalyviai.txt";
	$dalyviaiToFile = Array();
	$dalyviaiOutput = Array();
	// Surenkam ivykusio balsavimo dalyvius
	foreach ($voteResult[3] as $balsas) {
		array_push($dalyviaiOutput, $balsas[2] . " " . $balsas[4]);
	}

	// Jei jau turim dalyviu faila, paimam jo duomenis ir prijungiam prie turimų
	if (file_exists($dalyviaiFile)) {
		$dalyviai = Array();
		$readDalyviai = file_get_contents($dalyviaiFile);
		$savedDalyviai = explode("|", $readDalyviai);

		$tempDalyviai = Array();
		$tempDalyviai = array_diff($dalyviaiOutput, $savedDalyviai);
		// Sukeliam isvedimui dalyvius is failo
		foreach ($savedDalyviai as $dalyviai) {
			array_push($dalyviaiToFile, $dalyviai);
		}
		// Sukeliam dalyvius is dabar ivykusio balsavimo, kurie nesikartoja su dalyviais is failo
		foreach ($tempDalyviai as $dalyviai) {
			array_push($dalyviaiToFile, $dalyviai);
		}
	} else {
		foreach ($dalyviaiOutput as $dalyviai) {
			array_push($dalyviaiToFile, $dalyviai);
		}
	}
	$output = implode("|", $dalyviaiToFile);
	$fo = fopen($dalyviaiFile, "w");
	fwrite($fo, $output);
	fclose($fo);

	return 1;
}

/*
  function errorReport($error) {
  $file = "error.log";
  // TODO: Ar reikia failo sukurimo? jei appendinant neranda failo, tai ji sukuria?
  if(!file_exists($file)) {
  $foW = fopen($file, 'w');
  fclose($foW);
  }
  $foA = fopen($file, 'a');
  fwrite($foA, date("Y-m-d H:i:s").": ".$error."\n");
  fclose($foA);
  } */

/* Lietuviškų raidžių pakeitimas į HTML kodus */

function ltToHtmlEnteties($str) {
	$from = array('ą', 'č', 'ę', 'ė', 'į', 'š', 'ų', 'ū', 'ž', 'Ą', 'Č', 'Ę', 'Ė', 'Į', 'Š', 'Ų', 'Ū', 'Ž');
	$to = array('&#261;', '&#269;', '&#281;', '&#279;', '&#303;', '&#353;', '&#371;', '&#363;', '&#382;', '&#260;', '&#268;', '&#280;', '&#278;', '&#302;', '&#352;', '&#370;', '&#362;', '&#381;');
	return str_replace($from, $to, $str);
}

/* Sugeneruoja HTML'ą spauzdinimui */

function generatePrint($voteResult) {
	global $resultPath;
	$current_session = currentSession();
	$slug = new Slug();
	$filename = $slug->noDiacritics($current_session . ' - ' . $voteResult[0][1] . ' (' . date('Y-m-d H.i.s', strtotime($voteResult[0][3])) . ').html');

	$html_dir = $resultPath . 'html' . DIRECTORY_SEPARATOR . date('Y-m-d');
	$html_file = $html_dir . DIRECTORY_SEPARATOR . $filename;

	if (is_file($html_file) === FALSE/* || 1 == 1*/) {
		require_once('helper/template.php');
		$tmpl = new Template('tmpl/');

		$template_name = 'voting_result';
		$tmpl->set_file($template_name . '_file', 'voting_result.tpl');

		$vote_count_block = $template_name . "VoteCountBlock";
		$tmpl->set_block($template_name . "_file", "vote_count_block", $vote_count_block);
		$vote_person_block = $template_name . "VotePersonBlock";
		$tmpl->set_block($template_name . "_file", "vote_person_block", $vote_person_block);
		$vote_block = $template_name . "VoteBlock";
		$tmpl->set_block($template_name . "_file", "vote_block", $vote_block);

		$votes = array(
			array('name' => 'Už', 'votes' => $voteResult[2][0]),
			array('name' => 'Prieš', 'votes' => $voteResult[2][1]),
			array('name' => 'Susilaikė', 'votes' => $voteResult[2][2]),
			array('name' => 'Nebalsavo', 'votes' => $voteResult[1][1])
		);
		foreach ($votes as $i => $vote) {
			$tmpl->set_var(array(
				'vote' => $vote['name'],
				'vote_count' => $vote['votes']
			));
			$tmpl->parse($vote_count_block, 'vote_count_block', $i);
		}
		$start_datetime = date('Y-m-d H:i:s', strtotime(substr($voteResult[0][3], 0, 19)));
		$tmpl->set_var(array(
			'meeting_date' => $current_session,
			'question' =>  $voteResult[0][1],
			'question_start_datetime' => $start_datetime,
			'question_end_datetime' => date('Y-m-d H:i:s'),
			'participants_count' => $voteResult[1][3],
			'inactive_participants_count' => $voteResult[1][1],
			'must_vote_count' => $voteResult[1][2],
			'vote_count_for' => $voteResult[2][0],
			'vote_result' => $voteResult[1][0] ? 'Taip' : 'Ne'
		));

		$voters = array();
		foreach ($voteResult[3] as $key => $val) {
			$vote_id = (array) $val[1];
			$vote = array();
			foreach ($val as $k => $v) {
				$v = (array) $v;
				$vote[] = $v[0];
			}
			$voters[$vote_id[0]][] = $vote;
		}
		foreach ($voters as $vote_id => $votes) {
			$voters[$vote_id] = sortmulti($votes, 4, asc, TRUE, $case_sensitive = FALSE);
		}

		foreach ($voters as $vote_id => $votes) {
			if (count($votes)) {
				switch ($vote_id) {
					case 1:
						$vote_name = 'Už';
						break;
					case 2:
						$vote_name = 'Prieš';
						break;
					case 3:
						$vote_name = 'Susilaikė';
						break;
					case 254:
						$vote_name = 'Nebalsavo';
						break;
				}
				$tmpl->set_var('vote', $vote_name);
				foreach ($votes as $key => $vote) {
					$tmpl->set_var('person', $vote[2] . ' ' . $vote[4]);
					$tmpl->parse($vote_person_block, 'vote_person_block', $key);
				}
				$tmpl->parse($vote_block, 'vote_block', TRUE);
			} else {
				$tmpl->clear($vote_block);
			}
		}

		$tmpl->parse($template_name . '_file_out', $template_name . '_file');
		$output = $tmpl->get($template_name . $id . '_file_out');
		$output = ltToHtmlEnteties($output);
		$old_umask = umask(0);
		if (is_dir($resultPath . 'html') === FALSE) {
			mkdir($resultPath . 'html', 0777, true);
		}
		if (is_dir($html_dir) === FALSE) {
			mkdir($html_dir, 0777, true);
		}
		file_put_contents($html_file, $output);
		umask($old_umask);
		return $html_file;
	}
	return false;
}

/* funkcija testavimui */

function test() {
	return false;
	return array(
		'balsavimoStatusas' => 2,
		'voteSeassonArray' => array('Id' => '11'),
		'voteStoppedAt' => '2013-10-15 09:48:08',
		'voteResult' => array(
			array('11', "11. Klausimo pavadinimas", "agagdgsd", '2013-10-15 09:48:08'),
			// Priimta, nebalsavo, reikia, viso dalyvių
			array('true', '15', '26', '52'),
			array(0 => '28', 1 => '4', 2 => '5'),
			array(
				array('11', '1', 'G. Petras', '', 'zxc'),
				array('11', '2', 'G. Jonas', '', 'zxc'),
				array('11', '2', 'G. Jurgis', '', 'zxc'),
				array('11', '2', 'G. Zuokulas', '', 'zxc'),
				array('11', '2', 'G. Zulonis', '', 'zxc'),
				array('11', '1', 'G. Tarka', '', 'zxc'),
				array('11', '1', 'Parka', '', 'zxc'),
				array('11', '1', 'Nara', '', 'zxc'),
				array('11', '1', 'Šaras', '', 'zxc'),
				array('11', '1', 'Žyčius', '', 'zxc'),
				array('11', '3', 'Kulonis', '', 'zxc'),
				array('11', '3', 'Zalada', '', 'zxc'),
				array('11', '3', 'Trupa', '', 'zxc'),
				array('11', '3', 'Kurina', '', 'zxc'),
				array('11', '3', 'Zalėna', '', 'zxc'),
				array('11', '1', 'Rukščius', '', 'zxc'),
				array('11', '1', 'Romuvas', '', 'zxc'),
				array('11', '1', 'Urbonas', '', 'zxc'),
				array('11', '1', 'Jonelis', '', 'zxc'),
				array('11', '254', 'Klaidas', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonis', '', 'zxc'),
				array('11', '254', 'Mulonisas', '', 'zxc'),
				array('11', '1', 'Malūkas', '', 'zxc'),
				array('11', '1', 'Sereika', '', 'zxc'),
				array('11', '1', 'Širvilis', '', 'zxc'),
				array('11', '1', 'Paukštė', '', 'zxc'),
				array('11', '1', 'Martinėnienė', '', 'zxc'),
				array('11', '1', 'Čiulbytė', '', 'zxc'),
				array('11', '1', 'Jonaitytė', '', 'zxc'),
				array('11', '1', 'Zabukaitė', '', 'zxc'),
				array('11', '1', 'Garinaitė', '', 'zxc'),
				array('11', '1', 'Makedonietis', '', 'zxc'),
				array('11', '1', 'Urbonas', '', 'zxc'),
				array('11', '1', 'Sereika', '', 'zxc'),
				array('11', '1', 'Petras', '', 'zxc'),
				array('11', '1', 'Paukštė', '', 'zxc'),
				array('11', '1', 'Urbonas', '', 'zxc'),
				array('11', '1', 'Zalėna', '', 'zxc'),
				array('11', '1', 'Zalada', '', 'zxc'),
				array('11', '1', 'Sereika', '', 'zxc'),
			)
		)
	);
}

mb_internal_encoding("UTF-8");

class utf_8_lithunian {

	static $order = '0123456789AaĄąBbCcČčDdEeĘęĖėFfGgHhIiĮįJjKkLlMmNnOoPpRrSsŠšTtUuŲųŪūVvYyZz';
	static $char2order;

	static function cmp($a, $b) {
		if ($a == $b) {
			return 0;
		}

		// lazy init mapping
		if (empty(self::$char2order)) {
			$order = 1;
			$len = mb_strlen(self::$order);
			for ($order = 0; $order < $len; ++$order) {
				self::$char2order[mb_substr(self::$order, $order, 1)] = $order;
			}
		}

		$len_a = mb_strlen($a);
		$len_b = mb_strlen($b);
		$max = min($len_a, $len_b);
		for ($i = 0; $i < $max; ++$i) {
			$char_a = mb_substr($a, $i, 1);
			$char_b = mb_substr($b, $i, 1);

			if ($char_a == $char_b)
				continue;
			$order_a = (isset(self::$char2order[$char_a])) ? self::$char2order[$char_a] : 9999;
			$order_b = (isset(self::$char2order[$char_b])) ? self::$char2order[$char_b] : 9999;

			return ($order_a < $order_b) ? -1 : 1;
		}
		return ($len_a < $len_b) ? -1 : 1;
	}

}

class Slug {

	function my_str_split($string) {
      $slen=strlen($string);
      for($i=0; $i<$slen; $i++) {
         $sArray[$i]=$string{$i};
      }
      return $sArray;
   }

   function noDiacritics($string) {
      //cyrylic transcription
      $cyrylicFrom = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
      $cyrylicTo   = array('A', 'B', 'W', 'G', 'D', 'Ie', 'Io', 'Z', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'Ch', 'C', 'Tch', 'Sh', 'Shtch', '', 'Y', '', 'E', 'Iu', 'Ia', 'a', 'b', 'w', 'g', 'd', 'ie', 'io', 'z', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'ch', 'c', 'tch', 'sh', 'shtch', '', 'y', '', 'e', 'iu', 'ia');


      $from = array("Á", "À", "Â", "Ä", "Ă", "Ā", "Ã", "Å", "Ą", "Æ", "Ć", "Ċ", "Ĉ", "Č", "Ç", "Ď", "Đ", "Ð", "É", "È", "Ė", "Ê", "Ë", "Ě", "Ē", "Ę", "Ə", "Ġ", "Ĝ", "Ğ", "Ģ", "á", "à", "â", "ä", "ă", "ā", "ã", "å", "ą", "æ", "ć", "ċ", "ĉ", "č", "ç", "ď", "đ", "ð", "é", "è", "ė", "ê", "ë", "ě", "ē", "ę", "ə", "ġ", "ĝ", "ğ", "ģ", "Ĥ", "Ħ", "I", "Í", "Ì", "İ", "Î", "Ï", "Ī", "Į", "Ĳ", "Ĵ", "Ķ", "Ļ", "Ł", "Ń", "Ň", "Ñ", "Ņ", "Ó", "Ò", "Ô", "Ö", "Õ", "Ő", "Ø", "Ơ", "Œ", "ĥ", "ħ", "ı", "í", "ì", "i", "î", "ï", "ī", "į", "ĳ", "ĵ", "ķ", "ļ", "ł", "ń", "ň", "ñ", "ņ", "ó", "ò", "ô", "ö", "õ", "ő", "ø", "ơ", "œ", "Ŕ", "Ř", "Ś", "Ŝ", "Š", "Ş", "Ť", "Ţ", "Þ", "Ú", "Ù", "Û", "Ü", "Ŭ", "Ū", "Ů", "Ų", "Ű", "Ư", "Ŵ", "Ý", "Ŷ", "Ÿ", "Ź", "Ż", "Ž", "ŕ", "ř", "ś", "ŝ", "š", "ş", "ß", "ť", "ţ", "þ", "ú", "ù", "û", "ü", "ŭ", "ū", "ů", "ų", "ű", "ư", "ŵ", "ý", "ŷ", "ÿ", "ź", "ż", "ž");
      $to   = array("A", "A", "A", "A", "A", "A", "A", "A", "A", "AE", "C", "C", "C", "C", "C", "D", "D", "D", "E", "E", "E", "E", "E", "E", "E", "E", "G", "G", "G", "G", "G", "a", "a", "a", "a", "a", "a", "a", "a", "a", "ae", "c", "c", "c", "c", "c", "d", "d", "d", "e", "e", "e", "e", "e", "e", "e", "e", "g", "g", "g", "g", "g", "H", "H", "I", "I", "I", "I", "I", "I", "I", "I", "IJ", "J", "K", "L", "L", "N", "N", "N", "N", "O", "O", "O", "O", "O", "O", "O", "O", "CE", "h", "h", "i", "i", "i", "i", "i", "i", "i", "i", "ij", "j", "k", "l", "l", "n", "n", "n", "n", "o", "o", "o", "o", "o", "o", "o", "o", "o", "R", "R", "S", "S", "S", "S", "T", "T", "T", "U", "U", "U", "U", "U", "U", "U", "U", "U", "U", "W", "Y", "Y", "Y", "Z", "Z", "Z", "r", "r", "s", "s", "s", "s", "B", "t", "t", "b", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "w", "y", "y", "y", "z", "z", "z");


      $from = array_merge($from, $cyrylicFrom);
      $to   = array_merge($to, $cyrylicTo);

      $newstring = str_replace($from, $to, $string);

      return $newstring;
   }

   function makeSlugs($string, $maxlen=0) {
      $newStringTab=array();
      $string = strtolower($this->noDiacritics($string));
      if(function_exists('str_split')) {
         $stringTab=str_split($string);
      } else {
         $stringTab=$this->my_str_split($string);
      }

      $numbers=array("0","1","2","3","4","5","6","7","8","9","-","/");

      foreach($stringTab as $letter) {
         if(in_array($letter, range("a", "z")) || in_array($letter, $numbers)) {
            $newStringTab[]=$letter;
            //print($letter);
         }
         elseif($letter==" ") {
            $newStringTab[]="-";
         }
      }

      if(count($newStringTab)) {
         $newString=implode($newStringTab);
         if($maxlen>0) {
            $newString=substr($newString, 0, $maxlen);
         }

         $newString = $this->removeDuplicates('--', '-', $newString);
      } else {
         $newString='';
      }

      return $newString;
   }


   function checkSlug($sSlug) {
      if(ereg ("^[a-zA-Z0-9]+[a-zA-Z0-9\_\-]*$", $sSlug)) {
         return true;
      }

      return false;
   }

   function removeDuplicates($sSearch, $sReplace, $sSubject) {
      $i=0;
      do {

         $sSubject=str_replace($sSearch, $sReplace, $sSubject);
         $pos=strpos($sSubject, $sSearch);

         $i++;
         if($i>100) {
            die('removeDuplicates() loop error');
         }

      } while($pos!==false);

      return $sSubject;
   }

}
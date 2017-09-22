<?php

include 'helper/functions.php';
ini_set('display_errors', true);
if ($_GET['act']) {
	extract(test());
} else {
	if (file_exists($votingFile)) {
		$VotingOpen = fopen($votingFile, 'r');
		while (!feof($VotingOpen)) {
			$xmlString = simplexml_load_string(fgets($VotingOpen));
			if ($xmlString == NULL) {
				break;
			}
			if ($xmlString["Type"] == "VotingStarted") {
				$balsavimoStatusas = 1;
				/* new2 */ $voteStartArray = votingStarted($xmlString);
			}
			if ($xmlString["Type"] == "VotingStopped") {
				$balsavimoStatusas = 2;
				$voteSeassonArray = Array($xmlString->Voting["Id"]);
				$voteStoppedAt = $xmlString["TimeStamp"];

				/* new2 */ $voteResult = votingStopped($xmlString, $voteStartArray);
			}
		}
		fclose($VotingOpen);
	}
}

$changeFromArray = Array('Ą', 'ą', 'Č', 'č', 'Ę', 'ę', 'Ė', 'ė', 'Į', 'į', 'Š', 'š', 'Ų', 'ų', 'Ū', 'ū', 'Ž', 'ž');
$changeToArray = Array('Aa_', 'aa_', 'Ch_', 'ch_', 'Ee_', 'ee_', 'Eh_', 'eh_', 'Ii_', 'ii_', 'Sh_', 'sh_', 'Uu_', 'uu_', 'Uh_', 'uh_', 'Zh_', 'zh_');

$voteResult[3] = str_Array_replace($changeFromArray, $changeToArray, $voteResult[3]);

for ($voteID = 0; $voteID <= 3; $voteID++) {
	$voteResultSorted[$voteID] = Array();
}
foreach ((array) $voteResult[3] as $vote) {
	if ($vote[1] == "1") {
		array_push($voteResultSorted[0], $vote);
	} else if ($vote[1] == "2") {
		array_push($voteResultSorted[1], $vote);
	} else if ($vote[1] == "3") {
		array_push($voteResultSorted[2], $vote);
	} else {
		array_push($voteResultSorted[3], $vote);
	}
}

for ($voteIDs = 0; $voteIDs <= 3; $voteIDs++) {
	$voteResultSorted[$voteIDs] = sortmulti($voteResultSorted[$voteIDs], 4, asc, TRUE, $case_sensitive = FALSE);
}
$voteResult[3] = array_merge((array) $voteResultSorted[0], (array) $voteResultSorted[1], (array) $voteResultSorted[2], (array) $voteResultSorted[3]);

$voteResult[3] = str_Array_replace($changeToArray, $changeFromArray, $voteResult[3]);

/**///							if(trim($voteResult[2][0])==trim($voteResult[2][1]) && ($voteResult[2][0]+$voteResult[2][1]) == $dalyvauja) {
/**/ if (trim($voteResult[2][0]) == ((int) trim($voteResult[2][1]) + (int) trim($voteResult[2][2]) + (int) trim($nebalsavo))) {
	switch ($pirmininkasVote) {
		case "1": $rezultatas = "PRITARTA";
			$resultColor = "00fb47";
			$voteResult[1][0] = "true";
			break;
		case "2": $rezultatas = "NEPRITARTA";
			$resultColor = "ff1515";
			$voteResult[1][0] = "false";
			break;
		default: $rezultatas = "NEPRITARTA";
			$resultColor = "ff1515";
			$voteResult[1][0] = "false";
	}
}


if (strlen($voteResult[0][1]) >= 2) {
	$voteResult[0][1] = changeNumber($voteResult[0][1], 0);
} else {
	$voteResult[0][1] = changeNumber(currentSession(), 0);
}
$file = generatePrint($voteResult);
if ($file !== FALSE && is_file($file)) {
	$file = str_replace('\\', '/', $file);
	echo '<script type="text/javascript">newwindow=window.open("popup.php?file=' . $file . '","name","height=900,width=780");</script>';
}
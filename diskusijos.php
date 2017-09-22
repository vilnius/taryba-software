<?php
include 'helper/functions.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta http-equiv="imagetoolbar" CONTENT="no">
        <meta http-equiv=Refresh content="0.9">
		<style>
		body { position: relative; left: -50px; }
		</style>
        <title> </title>
    </head>
	<body style="background: #00133c; font-size: 55pt; color: white; font-family: 'Calibri'; margin:0; padding:0; margin: 0 auto; overflow: hidden;">
		<?php
		if (file_exists($discussionFile)) {
			$DiscussionOpen = file_get_contents($discussionFile);
			$DiscussionOpen = explode("\n", $DiscussionOpen);
			$discussionActiveH = Array();
			if ($DiscussionOpen)
				foreach ($DiscussionOpen as $xml_line) {

					$xmlString = simplexml_load_string($xml_line);
					if ($xmlString == NULL) {
						break;
					}
					if ($xmlString["Type"] == "RequestListUpdated") {
						$discussionQueued = Array();
						if ($xmlString->Discussion->RequestList->Participants->ParticipantContainer != null) {
							//	foreach((array)$xmlString->Discussion->RequestList->Participants->ParticipantContainer as $ParticipantContainer) {
							foreach ($xmlString->Discussion->RequestList->Participants->ParticipantContainer as $ParticipantContainer) {
								$toPush = Array($ParticipantContainer->ParticipantData["FirstName"],
									$ParticipantContainer->ParticipantData["MiddleName"],
									$ParticipantContainer->ParticipantData["LastName"]);
								array_push($discussionQueued, $toPush);
								unset($toPush);
							}
						}
					}

					if ($xmlString["Type"] == "SeatUpdated") {
						$toPush = Array(
							$xmlString["TimeStamp"],
							$xmlString->Seat->Participant["Id"],
							$xmlString->Seat->SeatData["MicrophoneActive"],
							$xmlString->Seat->Participant->ParticipantData["FirstName"],
							$xmlString->Seat->Participant->ParticipantData["MiddleName"],
							$xmlString->Seat->Participant->ParticipantData["LastName"],
							$xmlString->Seat["Id"]);
						array_push($discussionActiveH, $toPush);
						unset($toPush);
					}
				}

			$activeSpeakers = Array();
			$unactiveSpeakers = Array();
			$elementsToDeleteArray = Array();
			foreach ($discussionActiveH as $activeSpeaker) {
				if ($activeSpeaker[2] == "true") {
					array_push($activeSpeakers, $activeSpeaker);
				}
				if ($activeSpeaker[2] == "false") {
					array_push($unactiveSpeakers, $activeSpeaker);
				}
			}
			$index = 0;
			foreach ($activeSpeakers as $active) {
				$index2 = 0;
				$time = strtotime($active[0]);
				foreach ($unactiveSpeakers as $unactive) {
					$time2 = strtotime($unactive[0]);
					if ((trim($active[6]) == trim($unactive[6])) && $time2 >= $time) {
						unset($activeSpeakers[$index]);
						unset($unactiveSpeakers[$index2]);
						break;
					}
					$index2++;
				}
				$index++;
			}
		}

		if (file_exists('participants')) {
			$currentParticipantsArray = Array();
			$data = file_get_contents('participants');
			$data = explode('\n', $data);
			foreach ($data as $person) {
				if ($person != "") {
					$personDataArray = Array();
					$personData = explode('|', $person);
					foreach ($personData as $i) {
						array_push($personDataArray, $i);
					}
					array_push($currentParticipantsArray, (array) $personDataArray);
				}
			}
		}

		$activeSpeakersCheckArray = $activeSpeakers;

		// Atstatom masyvo raktus, kad jie eitu nuo 0
		$activeSpeakersCheckArray = resetArray($activeSpeakersCheckArray);

		// Pagal suolo ID atmetam tuos, kurie gali kalbeti bet kada.
		$dontCheckIDsArray = Array("2", "3", "4");
		$index = 0;
		foreach ($activeSpeakersCheckArray as $activeSpeakerCheck) {
			foreach ($dontCheckIDsArray as $toDelete) {
				if (trim($activeSpeakerCheck[6]) == trim($toDelete)) {
					unset($activeSpeakersCheckArray[$index]);
				}
			}
			$index++;
		}
		$index = 0;
		foreach ($currentParticipantsArray as $currentParticipant) {
			foreach ($dontCheckIDsArray as $toDelete) {
				if (trim($currentParticipant[4]) == trim($toDelete)) {
					unset($currentParticipantsArray[$index]);
				}
			}
			$index++;
		}

		$activeSpeakersCheckArray = resetArray($activeSpeakersCheckArray);
		$currentParticipantsArray = resetArray($currentParticipantsArray);

		$startDate = explode('T', $activeSpeakersCheckArray[0][0]);
		$thisTime = explode('+', $startDate[1]);
		$thisTime = explode('.', $thisTime[0]);
		$timeNow = time();
		$timeWas = strtotime($startDate[0] . " " . $thisTime[0]);
		$timePassed = $timeNow - $timeWas;

		// PHP.ini: jei "date.timezone = Europe/Vilnius"
		// tai "$timePassed += 0"
		//if($timeNow != $timeWas) {
		//	$timePassed += 3600;
		//}

		if ($timePassed > 0) {
			$mins = floor($timePassed / 60);
			$secs = $timePassed % 60;
		}
//	echo date("H:i:s")." ". $_SERVER['REQUEST_TIME']. " ". $timeNow;


		$activeSpeakers = resetArray($activeSpeakers);

		$index = 0;
		$at = 0;
		foreach ($activeSpeakers as $activeSpeaker) {
			if (array_search($activeSpeaker[6], $dontCheckIDsArray) == FALSE && /*$activeSpeaker[5] != $pirmininkas*/ $activeSpeaker[6] != $pirmininkas_seat) {
				$at = $index;
			}
			$index++;
		}
		?>


		<div class="page" style="margin: 0 auto; width: 1366px;">
			<div class="kalba" style="margin-left: 0px;"><i style="margin-left:30px; font-size:120%">Kalba:</i>

<?php for ($i = 0; $i < 3; $i++) { ?>
					<div class="kalba-'.$i.'" style="position: relative; top: -100px; left: 280px; font-size:150%; width: 1050px;">
					<?php
					/* +IE */ echo "<div><div style='float: left'>" . $activeSpeakers[$i][3] . " " . $activeSpeakers[$i][5] . "</div>"; //echo $activeSpeakers[$i][3]." ".$activeSpeakers[$i][5];
					if ($i == $at && $timePassed != 0) {
						/* +IE */ printf('<div style="float:right"><font class="time" style="background: green; width: 200px;"> %d:%02.0f </font></div></div>', $mins, $secs); //printf('<font class="time" style="float: right; background: green; width: 200px;"> %d:%02.0f </font>', $mins, $secs);
					}
					/* +IE */ else {
						echo"</div>";
					}
					?>
						<!--+IE-->			<div style='clear: both'></div>
					</div>
				<?php } ?>
			</div>
			<div class="eileje" style="position: relative; top:-50px; margin-left: 0px;  margin-top: 35px;"><i style="margin-left:30px;  font-size:120%;">Eilėje:</i>
				<div style="position: relative; top: -100px; left: 280px; font-size:150%;">
					<?php // for($i=0; $i<3; $i++) {?>
					<?php foreach ((array) $discussionQueued as $discussionQueuedSingle) { ?>
						<div class="eileje-'.$i.'">
							<?php
							echo $discussionQueuedSingle[0] . " " . $discussionQueuedSingle[2];
							?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</body>
</html>
<?php
/* Problema? */ DeleteDiscussion($activeSpeakers, $discussionQueued, $discussionFile);
$participantsOpen = fopen('participants', 'w');

foreach ($activeSpeakers as $activeSpeaker) {
	$toOutput .= $activeSpeaker[0] . '|' . $activeSpeaker[1] . '|' . $activeSpeaker[3] . '|' . $activeSpeaker[5] . '|' . $activeSpeaker[6] . '\n';
}
fwrite($participantsOpen, $toOutput);
fclose($participantsOpen);
?>
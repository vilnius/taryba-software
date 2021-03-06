<?php
include 'helper/functions.php';
include 'lib/config.php';
?>
<?php
// 0 - nera balsavimo; 1 - balsavimas vyksta; 2 - balsavimas baigtas; 3 - balsavimas pasirinktas;
$balsavimoStatusas = "0";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta http-equiv="imagetoolbar" CONTENT="no">
        <meta http-equiv=Refresh content="3">
		<style>
		body { position: relative; left: -50px; }
		</style>
        <title> </title>
    </head>
	<body style="background: #00133c; font-size: 55pt; color: white; font-family: 'Calibri'; margin:0; padding:0; overflow: hidden; text-align: center;">
		<div class="page" style="margin: 0 auto; width: 1366px; height: 768px;">

			<?php
			/*
			 * * $participantsArray masyvas
			 * * [] - dalyvio dimensija
			 * * [][0] - dayvio ID
			 * * [][1] - dalyvio vardas
			 * * [][2] - dalyvio tevavardis
			 * * [][3] - dalyvio pavarde
			 */

			if (file_exists($votingFile) || test() !== FALSE) {
				if(file_exists($votingFile) ) {
					$VotingOpen = fopen($votingFile, 'r');
					while (!feof($VotingOpen)) {
						$xmlString = simplexml_load_string(fgets($VotingOpen));
						if ($xmlString["Type"] == "VotingSelected") {
							$balsavimoStatusas = 3;
							// $title = $xmlString->Voting->VotingData["Subject"];
						}
						if ($xmlString["Type"] == "VotingStarted") {

							$balsavimoStatusas = 1;
							/* new2 */ $voteStartArray = votingStarted($xmlString);
						}
						if ($xmlString["Type"] == "VotingStopped") {
							$balsavimoStatusas = 2;
							$voteSeassonArray = Array($xmlString->Voting["Id"]);

							/* new2 */ $voteResult = votingStopped($xmlString, $voteStartArray);

							//$number = substr($voteResult[0][1], 0, strpos($voteResult[0][1], "."));
							//$title = substr($voteResult[0][1], strpos($voteResult[0][1], "."));
							//$voteResult[0][1] = ($number+2)."".$title;
							if (strlen($voteResult[0][1]) >= 2) {
								$voteResult[0][1] = changeNumber($voteResult[0][1], 0);
							} else {
								$voteResult[0][1] = changeNumber(currentSession(), 0);
							}
						}
					}
					fclose($VotingOpen);
				}
				/**/extract(test());
				$votedArray = Array();
				foreach ($voteResult[3] as $voters) {
					foreach ($participantsArray as $participant) {
						if (strcmp($participant[0][0], $voters[0]) == 0) {
							array_push($votedArray, $participant[0][0]);
						}
					}
				}
				$noVoteArray = $participantsArray;
				foreach ($votedArray as $voted) {
					for ($i = 0; $i < count($noVoteArray); $i++) {
						if (in_array($voted, $noVoteArray[$i])) {
							unset($noVoteArray[$i]);
						}
					}
				}

				$nebalsavo = $voteResult[1][1];
				$dalyvauja = $voteResult[1][3];
				$reikiaBalsu = 26;
				if (trim($dalyvauja) < trim($reikiaBalsu)) {
					$kvorumas = false;
					$kvorumasColor = "red";
				} else {
					$kvorumas = true;
					$kvorumasColor = "white";

					$resultOpen = file_get_contents($resultFile);
					$resultOpenArray = explode("\r\n", $resultOpen);
					$voteResult[1][0] = $resultOpenArray[0];
					$reikiaBalsu = $voteResult[1][2];
					switch ($voteResult[1][0]) {
						case "true": $rezultatas = "PRITARTA";
							$resultColor = "#008039";
							break;
						case "false": $rezultatas = "NEPRITARTA";
							$resultColor = "#b40000";
							break;
					}
				}
			}

			$nebalsavoText = "NEBALSAVO: ";
			if ($balsavimoStatusas != "2") {
				switch ($balsavimoStatusas) {
					case "0" || "3":
						$statusTXT = "NĖRA BALSAVIMO";
						$statusInsert = '<div class="vote-status" style="color: red;">' . $statusTXT . '</div>';
						break;
					case "1":
						$statusTXT = "VYKSTA BALSAVIMAS";
						$statusInsert = '<div class="vote-status" style="color: red">' . $statusTXT . '</div>';
						break;
					default: break;
				}
				echo '<div class="header" style="font-weight: normal; margin-top: 10px; border-top: 1px solid #7e90a7; border-bottom: 1px solid #647997;">
		' . $statusInsert . '
		</div>';
			} else {
				?>
				<div class="header" style="font-weight: normal; font-size: 90%; margin-top: 10px; border-top: 1px solid #7e90a7; border-bottom: 1px solid #647997;">
					BALSAVIMO REZULTATAI
				</div>
							  <?php if (file_exists($votingFile)) { ?>
					<div class="participate" style="margin: 30px 0;">
						<div class="participate-text" style="margin-bottom: 30px; font-size: 130%;">Dalyvauja:
							<font class="participate-result" style="color:
		<?php echo $kvorumasColor; ?>;">
						<?php echo $dalyvauja; ?>
							</font>
						</div>
					</div>
					<div class="votes" style="text-align: center; font-weight: bold; font-size: 140%;">
		<?php
		/* new2 */ renderTotalVotes($voteResult);
		?>
					</div>
					<div class="no-votes" style="">
						<div class="nebalsavo" style="color: #00c4d9; margin-top: 10px; font-size: 120%;">
						<?php echo $nebalsavoText . " " . $nebalsavo; ?>
						</div>

					</div>
					<div class="kvorumas" style="margin: 0px;">
						<?php if (!$kvorumas) { ?>
							<div class="nera" style="color: red; font-size: 90pt; padding-top: 30px;">
								Nėra kvorumo
							</div>
							<?php } else { ?>
							<div class="yra" style="padding-top: 20px; font-size: 120%;">
								Balsų kiekis kl. priėmimui:
								<?php
								echo $reikiaBalsu;
								?>
							</div>
							<div class="result-text" style="margin: 20px 0; font-size: 120%;">Rezultatas:
								<?php
								$pirmininkasVote = "";
								foreach ($voteResult[3] as $voter)
									if ($voter[2] == $pirmininkas || $voter[4] == $pirmininkas) {
										$pirmininkasVote = $voter[1];
									}
								/**///				if(trim($voteResult[2][0])==trim($voteResult[2][1]) && ($voteResult[2][0]+$voteResult[2][1]) == $dalyvauja) {
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
								?>
								<font class="result" style="font-weight: bold; color: <?php echo $resultColor; ?>;">
						<?php echo $rezultatas; ?>
								</font>
							</div>
					<?php } ?>
					</div>
				<?php
				}
				/* $number = substr($voteResult[0][1], 0, strpos($voteResult[0][1], "."));
				  $title = substr($voteResult[0][1], strpos($voteResult[0][1], "."));
				  $subject = ($number+2)."".$title; */
				$filename = $voteResult[0][3] . " " . $voteResult[0][1] . ".txt";

				//if(!file_exists($filename)) {
				//if(!file_exists($path)) {
				$changeFromArray = Array('Ą', 'ą', 'Č', 'č', 'Ę', 'ę', 'Ė', 'ė', 'Į', 'į', 'Š', 'š', 'Ų', 'ų', 'Ū', 'ū', 'Ž', 'ž', ':', "\n", "\r", "\"", "'");
				$changeToArray = Array('A', 'a', 'C', 'c', 'E', 'e', 'E', 'e', 'I', 'i', 'S', 's', 'U', 'u', 'U', 'u', 'Z', 'z', '.', '', '', '', '');
				$filename = str_replace($changeFromArray, $changeToArray, $filename);
				$path = $resultPath . date("Y-m-d")  . DIRECTORY_SEPARATOR . $filename;

				//if(($balsavimoStatusas == "2") && (!file_exists($filename))) {
				if (($balsavimoStatusas == "2") && (!file_exists($path))) {
					$output = generateOutput($voteResult, $kvorumas, $rezultatas);
					updateDalyviai($voteResult);
					//uploadDalyviai(FTP_host, FTP_username, FTP_password);
					uploadToFTP(null, FTP_host, FTP_username, FTP_password);
					if (writerResultFile($output, $filename) == 1) {
						uploadToFTP($filename, FTP_host, FTP_username, FTP_password);
					}
				}

				$NebalsavoOutput = generateOutputNebalsavo($voteResult);
				//$NebalsavoFilename = ($number+2)."_nebalsavo.txt";
				$NebalsavoFilename = changeNumber($voteResult[0][1], 1);
				if ((writerResultFile($NebalsavoOutput, $NebalsavoFilename) == 1)) {
					uploadToFTP($NebalsavoFilename, FTP_host, FTP_username, FTP_password) or die("nepavyko $NebalsavoFilename");
				}
				//}
				?>
<?php } ?>
		</div>
	</body>
</html>
<?php
include 'helper/functions.php';
include 'lib/config.php';
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="imagetoolbar" CONTENT="no">
		<meta http-equiv=Refresh content="3">
        <title> </title>
		<style type="text/css">
		</style>
		<script type="text/javascript">
			function Centering() {
				var winW = 512;
				if (document.body && document.body.offsetWidth) {
					winW = document.body.offsetWidth;
				}
				if (document.compatMode == 'CSS1Compat' &&
						document.documentElement &&
						document.documentElement.offsetWidth) {
					winW = document.documentElement.offsetWidth;
				}
				if (window.innerWidth) {
					winW = window.innerWidth;
				}
				var divArray = document.getElementById('centreFooter');
				footerW = winW / 2 - 600;
				divArray.style.left = footerW;
			}
		</script>
	</head>
	<body onLoad="Centering()" style="background: #001d57; color: #fff; font-size: 17pt; font-family: 'Calibri'; margin:0px auto; overflow: hidden;">
		<?php
		ini_set('memory_limit', '16M');

		// 0 - nera balsavimo; 1 - balsavimas vyksta; 2 - balsavimas baigtas;
		$balsavimoStatusas = "0";

		/* Jei buvo atliktas balsavimo veiksmas, ieskoma jo baigimo duomenu
		 * * $voteResult masyvas
		 * * [0][0] - balsavimo ID
		 * * [0][1] - balsavimo tem
		 * * [1][0] - balsavimo rezultatas	Bool
		 * * [1][1] - nebalsaviusiu kiekis
		 * * [3][] - balsu dimensija
		 * * [3][][0] - balsuotojo ID
		 * * [3][][1] - atsakymo ID
		 * * [3][][2] - balsuotojo vardas
		 * * [3][][3] - balsuotojo tevavardis
		 * * [3][][4] - balsuotojo pavarde
		 */
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
		/**/extract(test());
		/* new2 */ $sessionName = currentSession();
		$nebalsavo = $voteResult[1][1];
		$dalyvauja = $voteResult[1][3];
		//$nusisalino = 0;
		// if($voteResult[0][2]=="Skuba") {
		// $koeficientas = 0.6;
		// $add = 0;
		// } else {
		// $koeficientas = 0.5;
		// $add = 1;
		// }
		//$reikiaBalsu = ceil($dalyvauja*$koeficientas)+$add;
//|| (floor($dalyvauja*$koeficientas)+$add) > $voteResult[2][0]
		//$reikiaBalsu = $voteResult[1][2];
		$reikiaBalsu = 26;
		if (trim($dalyvauja) < trim($reikiaBalsu)) {
			$kvorumas = false;
			$kvorumasColor = "red";
		} else {
			$kvorumas = true;
			$kvorumasColor = "white";

			$reikiaBalsu = $voteResult[1][2];
			switch ($voteResult[1][0]) {
				case "true": $rezultatas = "PRITARTA";
					$resultColor = "00fb47";
					break;
				case "false": $rezultatas = "NEPRITARTA";
					$resultColor = "ff1515";
					break;
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

		$inCollumn = ceil($dalyvauja / 3);
		?>
		<div class="page" style="margin: 0 auto; width: 1280px; max-height: 720px;">

			<?php
			if ($balsavimoStatusas != "2") {
				switch ($balsavimoStatusas) {
					case "0":
						if ($sessionName != null) {
							$statusTXT = $sessionName;
							$statusInsert = '<div class="vote-status" style="color: #fff; border-bottom: 1px solid #647997; padding-bottom: 15px">'
									. $statusTXT . '</div><div class="herbas" style="background: url(images/herbas.png) no-repeat 50% 50%; margin: 10 auto; width: 530px; height: 578px;">
								</div>';
						} else {
							$statusTXT = "NĖRA BALSAVIMO";
							$statusInsert = '<div class="vote-status" style="color: red; font-size: 200%; border-bottom: 1px solid #647997;">' . $statusTXT . '</div><div class="herbas" style="background: url(images/herbas.png) no-repeat 50% 50%; margin: 10 auto; width: 530px; height: 578px;"></div>';
						}
						break;
					case "1":
						$statusTXT = "VYKSTA BALSAVIMAS";
						if ($voteStartArray[2] != '') {
							$voteType = "<div class=\"vote-name\" style=\"color: #00e4ff; font-size: 120%; position: relative; top: 150px;\">(" . $voteStartArray[2] . ")</div>";
						}
						$statusInsert = '<div class="alert" style="color: red; font-size: 150%">Dėmesio!</div>
						 <div class="vote-status" style="color: red; font-size: 200%; border-bottom: 1px solid #647997; ">' . $statusTXT . '</div>
						 <div class="vote-subject" style="font-size: 120%; position: relative; top: 120px;">' . $voteStartArray[1] . '</div>' . $voteType;
						break;
					default: break;
				}
				echo '<div class="header" style="text-align: center; padding: 10px 0; font-size: 190%; margin-top: 10px;  border-top: 1px solid #7e90a7; ">'
				. $statusInsert . '</div>';
			} else {

				$fR = fopen($resultFile, 'w');
				fwrite($fR, $voteResult[1][0] . "\r\n" . $voteStoppedAt);
				fclose($fR);
				?>

				<div class="header" style="text-align: center; padding: 10px 0; font-size: 130%">
						<?php if (strlen($voteResult[0][1]) >= 200) {
							echo substr($voteResult[0][1], 0, 140) . "...";
						} else {
							echo $voteResult[0][1];
						} ?>
				</div>
				<div class="voters" style="position: relative; left:70px; margin-top: 3px; margin: 0px auto;">
						<?php
						$from = 0;
						$pirmininkasVote = "";
						for ($i = 0; $i < 3; $i++) {
							?>
						<div class="collumn<?php echo $i; ?>" style="width: 379px; float:left;">
							<?php
							$margin = "margin-left: 3px;";
							if ($i > 0) {
								$margin = "margin-left: 15px;";
							}
							if (count($voteResult[3]) != 0) {
								for ($j = 0; $j < $inCollumn; $j++) {
									if ($voteResult[3][$from][1] == "1") {
										$color = '#007935';
									} else if ($voteResult[3][$from][1] == "2") {
										$color = '#ff1515';
									} else if ($voteResult[3][$from][1] == "3") {
										$color = '#ffea00';
									} else if ($voteResult[3][$from][1] == "254") {
										$color = '#00e4ff';
									} else {
										$color = '#fff';
									}
									if ($voteResult[3][$from][2] == $pirmininkas  || $voteResult[3][$from][4] == $pirmininkas) {
										$pirmininkasVote = $voteResult[3][$from][1];
									}
									if ($voteResult[3][$from][4] == NULL && $voteResult[3][$from][2] == NULL) {
										break;
									}

									echo '<div class="voter' . $from . '" style="background: #fff; color: #000; height: 22px; border:1px solid #c0c0c0; margin-bottom: 2px;' . $margin . '">
								<font class="voter' . $from . '-text" style="float: left; padding-left: 5px; font-size: 16pt;">' .
									$voteResult[3][$from][4] . ' ' . $voteResult[3][$from][2] . '</font>
								<div class="vote' . $from . '-vote" style="float: right; background: ' . $color . '; border: 1px solid #c7c7c7; width: 15px; height: 14px; margin: 3px;"></div>
							  </div>';
									$from++;
								}
							}
							?>
						</div>
	<?php } ?>
				</div>
				<div class="bottom" id="centreFooter" style="position: absolute; bottom: 0; clear: left; text-align: center; padding-top: 10px; font-size: 25pt;">
					<div class="total-votes" style="width: 1200px; font-weight: bold; border: 2px solid #7381a0; margin: 0px auto; padding-bottom:5px;" >
	<?php if (file_exists($votingFile)) { ?>
							<div class="inner" style="">
								<div class="participate" style="margin: 0px 0;">
									<div class="participate-text" style="margin-bottom: 0px; font-size: 120%">Dalyvauja:
										<font class="participate-result" style="margin-bottom: 0px; color: <?php echo $kvorumasColor; ?>;">
		<?php echo $dalyvauja; ?>
										</font>
									</div>
								</div>
							</div>
							<div class="votes" style="font-size: 140%">
		<?php
		/* new2 */ renderTotalVotes($voteResult);
		?>

								<font class="nebalsavo" style="color: #00e4ff; font-weight: bold; margin-left: 15px;"> NEBALSAVO: <?php echo $nebalsavo; ?></font>
							</div>
							<div class="kvorumas" style="margin: 0px 0;">
											<?php if (!$kvorumas) { ?>
									<div class="nera" style="color: red; padding-top: 0px; font-size: 190%">
										Nėra kvorumo
									</div>
											<?php } else { ?>
									<div class="yra" style="padding-top: 0px;">
										<div class="need-text" style="">
											Balsų kiekis kl. priėmimui:
											<font class="need-nr" style="">
			<?php
			echo $reikiaBalsu;
			?>
											</font>
										</div>
										<div class="result" style="">
											<div class="result-text" style="margin: 0px 0;">Rezultatas:
			<?php
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
			?>
												<font class="vote-result" style="color: <?php echo $resultColor; ?>;">
					<?php echo $rezultatas; ?>
												</font>
											</div>
										</div>
									</div>
				<?php } ?>
							</div>
			<?php } ?>
					</div>
				</div>
			<?php }
		?>
		</div>
		<?php
		//$number = substr($voteResult[0][1], 0, strpos($voteResult[0][1], "."));
		//$title = substr($voteResult[0][1], strpos($voteResult[0][1], "."));
		//$voteResult[0][1] = ($number+2)."".$title;
		if (strlen($voteResult[0][1]) >= 2) {
			$voteResult[0][1] = changeNumber($voteResult[0][1], 0);
		} else {
			$voteResult[0][1] = changeNumber(currentSession(), 0);
		}
		$filename = $voteResult[0][3] . " " . $voteResult[0][1] . ".txt";

		$changeFromArray = Array('Ą', 'ą', 'Č', 'č', 'Ę', 'ę', 'Ė', 'ė', 'Į', 'į', 'Š', 'š', 'Ų', 'ų', 'Ū', 'ū', 'Ž', 'ž', ':', "\n", "\r", "\"", "'");
		$changeToArray = Array('A', 'a', 'C', 'c', 'E', 'e', 'E', 'e', 'I', 'i', 'S', 's', 'U', 'u', 'U', 'u', 'Z', 'z', '.', '', '', '', '');
		$filename = str_replace($changeFromArray, $changeToArray, $filename);
		$path = $resultPath . date("Y-m-d") . DIRECTORY_SEPARATOR . $filename;

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
		?>
	</body>
</html>
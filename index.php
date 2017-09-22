<?php
include 'helper/functions.php';
include('class/council.class.php');
$council = new Council();
$getQuestion = $council->getCurrentQuestion(); //get question to show
?>
<html lang="lt">
  <head>
        <!-- Required meta tags -->
        <title>Manage</title>
        <meta charset="utf-8">
        <meta http-equiv=Refresh content="3">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400&amp;subset=latin-ext" rel="stylesheet">
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:100" /> 
            <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:300" /> 
            <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:400" />
        <![endif]-->
        <link rel="stylesheet" type="text/css" href="lib/css/bootsrap.min.css">
        <link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body>
        <div class="container-fluid">
		<?php
		ini_set('memory_limit', '16M');
//ini_set('display_errors', true);
//ini_set('error_reporting', E_ALL);
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
		extract(test());
		$sessionName = currentSession();
		$nebalsavo = $voteResult[1][1];
		$dalyvauja = $voteResult[1][3];
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
		<div class="page">

			<?php
			if ($balsavimoStatusas != "2") {
				switch ($balsavimoStatusas) {
					case "0":
						if ($sessionName != null) {
                            $statusTXT = $sessionName;
                            if($getQuestion['ID'] == 1) {
				                $statusInsert = '<div class="vote-status">'. $statusTXT . '</div><div class="herbas"></div>'; 
                            } else {
                                $statusInsert = '<div class="vote-status">'. $statusTXT . '</div>
                                <div class="questionText">'.$getQuestion['pranesimo_tekstas'].'</div>
                                <div class="footer"></div>';
                            }
						} else {
							$statusTXT = "NĖRA BALSAVIMO";
							$statusInsert = '<div class="vote-status">' . $statusTXT . '</div><div class="herbas" ></div>';
						}
						break;
					case "1":
						$statusTXT = "VYKSTA BALSAVIMAS";
						if ($voteStartArray[2] != '') {
							$voteType = "<div class=\"vote-name\" style=\"color: #00e4ff; font-size: 120%; position: relative; top: 150px;\">(" . $voteStartArray[2] . ")</div>";
						}
						$statusInsert = '<div class="alert">Dėmesio!</div>
						 <div class="vote-status">' . $statusTXT . '</div>
						 <div class="vote-subject">' . $voteStartArray[1] . '</div>' . $voteType;
                        $statusInsert.='<div class="footer"></div>';
						break;
					default: break;
				}
				echo '<div class="mainContent">'
				. $statusInsert . '</div>';
			} else {
				$fR = fopen($resultFile, 'w');
				fwrite($fR, $voteResult[1][0] . "\r\n" . $voteStoppedAt);
				fclose($fR);
				if(TimePassed() >= 60 || TimePassed() <= 0) {
				//if (1 === 2) {
					$statusTXT = $sessionName;
					$statusInsert = '<div class="vote-status">'
							. $statusTXT . '</div><div class="herbas">
						</div>';
					echo '<div class="header">'
					. $statusInsert . '</div>';
				} else {
					?>

					<div class="header">
						<?php
						if (strlen($voteResult[0][1]) >= 200) {
							echo substr($voteResult[0][1], 0, 140) . "...";
						} else {
							echo $voteResult[0][1];
						}
						?>
					</div>
					<div class="voters" style="position: relative; left:15%; margin-top: 3px; margin: 0px auto;">
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
										if ($voteResult[3][$from][2] == $pirmininkas || $voteResult[3][$from][4] == $pirmininkas) {
											$pirmininkasVote = $voteResult[3][$from][1];
										}
										if ($voteResult[3][$from][4] == NULL && $voteResult[3][$from][2] == NULL) {
											break;
										}

										echo '<div class="voterAfterVote voter' . $from . '" style="' . $margin . '">
								<font class="voter' . $from . '-text" style="float: left; padding-left: 5px; font-size: 16pt;">' .
										$voteResult[3][$from][4] . ' ' . $voteResult[3][$from][2] . '</font>
								<div class="vote' . $from . '-vote votersSquare" style="background: ' . $color . '; "></div>
							  </div>';
										$from++;
									}
								}
								?>
							</div>
		<?php } ?>
					</div>
					<div class="bottom">
						<div class="total-votes">
		<?php if (file_exists($votingFile)) { ?>
								<div class="inner">
									<div class="participate">
										<div class="participate-text">Dalyvauja: <?php echo $dalyvauja; ?>
										</div>
									</div>
								</div>
								<div class="votes" style="font-size: 140%">
									<?php
									renderTotalVotes($voteResult);
									?>

									<font class="nebalsavo countVotes" style="color: #00e4ff; margin-left: 15px;"> NEBALSAVO: <?php echo $nebalsavo; ?></font>
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
													if (trim($voteResult[2][0]) == ((int) trim($voteResult[2][1]) + (int) trim($voteResult[2][2]) + (int) trim($nebalsavo))) {
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
				<?php
				}
			}
			?>
		</div>
		<?php
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
		if (($balsavimoStatusas == "2") && (!file_exists($path)) && (TimePassed() <= 60 && TimePassed() >= 0)) {
			updateDalyviai($voteResult);
			uploadToFTP(null, 'ftp.vilnius.lt', 'voting', 'voteres.2011');
			$output = generateOutput($voteResult, $kvorumas, $rezultatas);
			if ((writerResultFile($output, $filename) == 1)) {
				uploadToFTP($filename, 'ftp.vilnius.lt', 'voting', 'voteres.2011');
			}
		}

		$NebalsavoOutput = generateOutputNebalsavo($voteResult);
		$NebalsavoFilename = changeNumber($voteResult[0][1], 1);
		if ((writerResultFile($NebalsavoOutput, $NebalsavoFilename) == 1)) {
			uploadToFTP($NebalsavoFilename, 'ftp.vilnius.lt', 'voting', 'voteres.2011') or die("nepavyko $NebalsavoFilename");
		}
		if ($balsavimoStatusas == "2") {
			$file = generatePrint($voteResult);
		}
		?>
        </div>
	</body>
</html>
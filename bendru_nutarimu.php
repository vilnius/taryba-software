<?php
include 'helper/functions.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title> </title>
		<style type="text/css">
			form { width: 900px; }
			ul { list-style-type: none;  width: 900px; }
			li { border-bottom: 1px solid black; height: 30px; }
			.checkboxes img { padding-left: 5px; height: 10px; width: 10px; }
			a { text-decoration: none; color: #fff; }
			input[type="text"] { width: 35px; }
			#list { float: left; }
			#refresh-list { vertical-align: middle; cursor: hand; }
			#render-list { vertical-align: middle; cursor: hand; padding-left: 3px; }
			.checkboxes { margin-top: 5px; }
			.vertical-text {
				float: left;
				padding-left: 5px;
				padding-left: 3px\9;
				writing-mode: tb-rl;
				-webkit-transform: rotate(90deg);
				-moz-transform: rotate(90deg);
				-o-transform: rotate(90deg);
				white-space: nowrap;
				bottom: 0;
				width: 17px;
				font-family: ‘Trebuchet MS’, Helvetica, sans-serif;
				font-size: 14px;
				font-weight: normal;
				text-shadow: 0px 0px 1px #333;
				display: inline-block;
				border-image: initial;
			}
			.checkboxes-desc {
				height: 0;
				margin-top: 0px;
			}
			.question, .meeting {
				float: left;
				width: 85%;
			}
			.meeting { padding-top: 45px; }
			div.submit { width: 75.9%; text-align: right; }
		</style>
	</head>
	<body onLoad="onLoad();" style="background: #001d57; color: #fff; font-size: 17pt; font-family: 'Calibri'; margin:0px auto; text-align: left; ">
		<?php
		if (!file_exists($meetingFile)) {
			echo "<div style='text-align: center;'>Pradėkite posėdį</div>";
			header("refresh:1;");
		} elseif (file_exists($meetingFile)) {
			$meetingOpen = fopen($meetingFile, 'r');
			while (!feof($meetingOpen)) {
				$xmlString = simplexml_load_string(fgets($meetingOpen));
				if ($xmlString == NULL) {
					break;
				}
				if ($xmlString["Type"] == "MeetingStarted") {
					$meetingArray = meetingStarted($xmlString);
					$status = 1;
				} elseif ($xmlString["Type"] == "MeetingStopped") {
					$status = 0;
				}
			}
			fclose($meetingOpen);
			if (($_GET['action'] == null) && ($status == 1)) {
				?>
				<?php
				//echo renderMeetingList($meetingArray, "ul");
				?>
				Balsavimų skaičius: <input id="votings" type="text" onChange="renderList(this, '<?php echo $meetingArray[0][1] ?>');"><img id="render-list" src="images/next.png" alt="Sukurti sąrašą"/>
				<FORM NAME ='meetings' METHOD='POST' ACTION ='?'>
					Posėdis: <?php echo renderMeetingList($meetingArray, "select"); ?><img id="refresh-list" onClick="location.reload(true);" src="images/refresh.png" alt="Atnaujinti sąrašą"/>
					<div>
						<!--<div style="display: inline"><input type="checkbox" name="type" id="type" value="slaptas" onChange="renderSlaptas(this);"/></div>
						<div style="width: 250px; font-size: 0.8em; display: inline" onClick="checking('type');"><label>Slaptas balsavimas</label></div>-->
						<select name="type" id="type" onChange="typeChanged(this)"><option>-------------<option>Dėl atleidimo</option><option>Dėl nepasitikėjimo</option><option>Slaptas</option></select>
					</div>
					<div id="list"></div>
				</FORM>
				<?php
			} elseif (($_GET['action'] == null) && ($status == 0)) {
				echo "<div style='text-align: center;'>Pradėkite posėdį</div>";
				header("refresh:1;");
			}

			if (($_POST['posedis'] != null) && ($status == 1)) {
				?>
				<?php
				$render_result = "";
				if (strtolower($_POST['type']) != "slaptas" && $_POST['type_id'] != 1 && $_POST['type_id'] != 2) {
					foreach ($_POST as $id => $result) {
						$id = str_replace("r", "", $id);
						$id = str_replace("_", "-", $id);
						if ((is_numeric($id)) && ($result != "")/* ($result != "atmesta") */) {
							if (file_exists($participantFile)) {
								$participantOpen = fopen($participantFile, 'r');
								// TODO:
								$bendruParticipantList = Array();
								while (!feof($participantOpen)) {
									$xmlString = simplexml_load_string(fgets($participantOpen));
									if ($xmlString == NULL) {
										break;
									}
									if ($xmlString["Type"] == "ParticipantUpdated") {
										array_push($bendruParticipantList, bendruParticipant($xmlString));
									}
								}
								fclose($participantOpen);
								$participantArray = bendruUpdatePresent($bendruParticipantList/* , $participantArray */);
							}
							// JS
							//$filename = date("Y-m-d\TH.i.s") . " " . $_POST['posedis'] . " - " . ($id + 2) . " nr.txt";
							$filename = date("Y-m-d\TH.i.s") . " " . $_POST['posedis'] . " - " . $id . " nr.txt";
							$changeFromArray = Array('Ą', 'ą', 'Č', 'č', 'Ę', 'ę', 'Ė', 'ė', 'Į', 'į', 'Š', 'š', 'Ų', 'ų', 'Ū', 'ū', 'Ž', 'ž', ':', "\n", "\r", "\"", "'");
							$changeToArray = Array('A', 'a', 'C', 'c', 'E', 'e', 'E', 'e', 'I', 'i', 'S', 's', 'U', 'u', 'U', 'u', 'Z', 'z', '.', '', '', '', '');
							$filename = str_replace($changeFromArray, $changeToArray, $filename);
							//$render_result .= $id . " " . strtoupper($result) . "<br/>";
							$output = generateOutput_bendru($id, $result, /* $meetingArray */ $participantArray);
							if (writerResultFile($output, $filename) == 1) {
								uploadToFTP($filename, 'ftp.vilnius.lt', 'voting', 'voteres.2011');
							}
						}
					}
					//echo"$render_result<br/>";
				} else {
					// jei tipas ne "slaptas" .., jei "slaptas"
					$data = (strtolower($_POST['type']) != "slaptas") ? array("number" => $_POST['number'], "type_id" => $_POST['type_id']) : array("number" => $_POST['number'], "type_id" => "3");
					foreach ($_POST as $key => $value) {
						$number = substr($key, strlen($key) - 1);
						if (ctype_digit($number)) {
							$vote = substr($key, 0, strlen($key) - 1);
							$data['teiginiai'][$number][$vote] = $value;
						}
					}
					// jei visi elementai uzmildyti, sukuriam html, uploadinam ir nustatom stilius
					if (isArrayNotEmpty($data)) {
						$HTMLoutput = generateHTML($data);
						$HTMLoutput = str_replace(array("\r", "\n", "\t"), "", $HTMLoutput);
						$HTMLfile = $_POST["number"] . "_secret.html";
						writerResultFile($HTMLoutput, $HTMLfile);
						uploadToFTP($HTMLfile, 'ftp.vilnius.lt', 'voting', 'voteres.2011');
						echo "<script type=\"text/javascript\">
					document.getElementById(\"votings\").disabled = true;
					document.getElementById(\"render-list\").disabled = true;
					document.getElementById(\"render-list\").style.cursor = \"default\";
					//document.getElementById(\"type\").checked = true;
					document.getElementById(\"list\").innerHTML = '$HTMLoutput';
					</script>";
					} else {
						echo "Klaida: Užpildyti ne visi laukai.";
					}
					/* // if(isset($_POST["number"]) && isset($_POST["uz"]) && isset($_POST["pries"]) && isset($_POST["susilaike"]) && isset($_POST['vote_result'])) {
					  //	$result = Array("0" => $_POST["number"], "1" => $_POST["uz"],"2" => $_POST["pries"],"3" => $_POST["susilaike"],"4"=>$_POST['vote_result']);
					  //	$HTMLoutput = generateHTML($result);
					  //	$HTMLoutput = str_replace(array("\r","\n","\t"),"",$HTMLoutput);
					  //	$HTMLfile = $_POST["number"]."_secret.html";
					  //	writerResultFile($HTMLoutput, $HTMLfile);
					  uploadToFTP($HTMLfile, 'ftp.vilnius.lt', 'voting', 'voteres.2011');
					  //	echo "<script type=\"text/javascript\">
					  //	document.getElementById(\"votings\").disabled = true;
					  //	document.getElementById(\"render-list\").disabled = true;
					  //	document.getElementById(\"render-list\").style.cursor = \"default\";
					  //	document.getElementById(\"type\").checked = true;
					  //	document.getElementById(\"list\").innerHTML = '$HTMLoutput';
					  //	</script>";
					  } */
				}
			}
		}
		?>

		<script type="text/javascript">
			var _POST = new Array();
<?php
foreach ($_POST as $key => $value) {
	echo "_POST[\"$key\"] = \"$value\";\n";
}
?>
			function onLoad() {
				if (_POST['votings'] != null) {
					renderList(_POST['votings'], '<?php echo $meetingArray[0][1] ?>');
					selectPosedis(document.getElementById("posedis"), _POST['posedis']);
				}
				if (_POST['posedis'] != null) {
					selectPosedis(document.getElementById("posedis"), _POST['posedis']);
				}
				if (_POST["type"] != null && _POST["type"].toLowerCase() == "slaptas") {
					selectPosedis(document.getElementById("type"), _POST['type']);
				}
				if (_POST["type"] != null && (_POST["type_id"] == 1 || _POST["type_id"] == 2)) {
					selectPosedis(document.getElementById("type"), _POST['type']);
				}
			}
			function naujasKlausimas() {
				_POST = [];
				var obj = document.getElementById("type");
				if (getTypeIndex(obj) == 3) {
					renderSlaptas(obj);
				} else {
					renderList(document.getElementById("votings"), '<?php echo $meetingArray[0][1] ?>');
				}
			}
			function renderList(obj, title) {
				if (document.getElementById("votings").disabled == false) {
					var votings = obj.value;
					var meetingList = "";
					//var meetingList = "<li><span class='question r-1' style='color: "+color+"'>Dėl balsų skaičiavimo komisijojs<input type='hidden' name='r-1color' value='"+color+"'/></span><div class='checkboxes'><input type='radio' name='r-1' value='priimta'/><input type='radio' name='r-1' value='nepriimta'/><input type='radio' name='r-1' value='atideta'/><input type='radio' name='r-1' value='isbraukta'/><input type='radio' name='r-1' value='' style='display: none'/><img onclick='document.meetings.r-1[4].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
					//meetingList += "<li><span class='question r0' style='color: "+color+"'>Dėl darbotvarkės<input type='hidden' name='r0color' value='"+color+"'/></span><div class='checkboxes'><input type='radio' name='r0' value='priimta'/><input type='radio' name='r0' value='nepriimta'/><input type='radio' name='r0' value='atideta'/><input type='radio' name='r0' value='isbraukta'/><input type='radio' name='r0' value='' style='display: none'/><img onclick='document.meetings.r0[4].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
					if (votings == null) {
						votings = _POST['votings'];
					}
					document.getElementById("votings").value = votings;
					if (form_input_message(document.getElementById("votings"), 'is_int') == false) {
						return;
					}
					//for(var i=1; i<=votings; i++) {
					for (var i = -1; i <= votings; i++) {
						switch (_POST['r' + i]) {
							case 'priimta':
								var color = '#00fb47';
								break;
							case 'nepriimta':
								var color = '#ff1515';
								break;
							case 'atideta':
								var color = '#ffea00';
								break;
							case 'isbraukta':
								var color = '#d8d8d8';
								break;
							case 'perkelta':
								var color = '#a52a2a';
								break;
							default:
								var color = _POST['r' + i + 'color'];
						}
						switch (i) {
							case -1:
								//meetingList += "<li><span class='question r-1' style='color: "+color+"'>Dėl balsų skaičiavimo komisijojs<input type='hidden' name='r-1color' value='"+color+"'/></span><div class='checkboxes'><input type='radio' name='r-1' value='priimta'/><input type='radio' name='r-1' value='nepriimta'/><input type='radio' name='r-1' value='atideta'/><input type='radio' name='r-1' value='isbraukta'/><input type='radio' name='r-1' value='' style='display: none'/><img onclick='document.meetings.r-1[4].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
								meetingList += "<li><span class='question r-1' style='color: " + color + "'>Dėl balsų skaičiavimo komisijos<input type='hidden' name='r-1color' value='" + color + "'/></span><div class='checkboxes'><input type='radio' name='r-1' value='priimta'/><input type='radio' name='r-1' value='nepriimta'/><input type='radio' name='r-1' value='atideta'/><input type='radio' name='r-1' value='isbraukta'/><input type='radio' name='r-1' value='perkelta'/><input type='radio' name='r-1' value='' style='display: none'/><img onclick='document.meetings.r-1[5].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
								break;
							case 0:
								//meetingList += "<li><span class='question r0' style='color: "+color+"'>Dėl darbotvarkės<input type='hidden' name='r0color' value='"+color+"'/></span><div class='checkboxes'><input type='radio' name='r0' value='priimta'/><input type='radio' name='r0' value='nepriimta'/><input type='radio' name='r0' value='atideta'/><input type='radio' name='r0' value='isbraukta'/><input type='radio' name='r0' value='' style='display: none'/><img onclick='document.meetings.r0[4].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
								meetingList += "<li><span class='question r0' style='color: " + color + "'>Dėl darbotvarkės<input type='hidden' name='r0color' value='" + color + "'/></span><div class='checkboxes'><input type='radio' name='r0' value='priimta'/><input type='radio' name='r0' value='nepriimta'/><input type='radio' name='r0' value='atideta'/><input type='radio' name='r0' value='isbraukta'/><input type='radio' name='r0' value='perkelta'/><input type='radio' name='r0' value='' style='display: none'/><img onclick='document.meetings.r0[5].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
								break;
								//default: meetingList += "<li><span class='question r"+i+"' style='color: "+color+"'>"+i+"<input type='hidden' name='r"+i+"color' value='"+color+"'/></span><div class='checkboxes'><input type='radio' name='r"+i+"' value='priimta'/><input type='radio' name='r"+i+"' value='nepriimta'/><input type='radio' name='r"+i+"' value='atideta'/><input type='radio' name='r"+i+"' value='isbraukta'/><input type='radio' name='r"+i+"' value='' style='display: none'/><img onclick='document.meetings.r"+i+"[4].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
							default:
								meetingList += "<li><span class='question r" + i + "' style='color: " + color + "'>" + i + "<input type='hidden' name='r" + i + "color' value='" + color + "'/></span><div class='checkboxes'><input type='radio' name='r" + i + "' value='priimta'/><input type='radio' name='r" + i + "' value='nepriimta'/><input type='radio' name='r" + i + "' value='atideta'/><input type='radio' name='r" + i + "' value='isbraukta'/><input type='radio' name='r" + i + "' value='perkelta'/><input type='radio' name='r" + i + "' value='' style='display: none'/><img onclick='document.meetings.r" + i + "[5].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
						}
						//meetingList += "<li><span class='question r"+i+"' style='color: "+color+"'>"+i+"<input type='hidden' name='r"+i+"color' value='"+color+"'/></span><div class='checkboxes'><input type='radio' name='r"+i+"' value='priimta'/><input type='radio' name='r"+i+"' value='nepriimta'/><input type='radio' name='r"+i+"' value='atideta'/><input type='radio' name='r"+i+"' value='isbraukta'/><input type='radio' name='r"+i+"' value='' style='display: none'/><img onclick='document.meetings.r"+i+"[4].checked = true;' src='images/cross.gif' alt='Atžymėti'/></div></li><div style='clear: both;'>";
					}
					//var output = "<ul><span class='meeting'>"+title+"</span>	<div class='checkboxes checkboxes-desc'>		<div class='vertical-text' style='color: #00fb47'>UŽ</div>		<div class='vertical-text' style='color: #ff1515'>PRIEŠ</div>		<div class='vertical-text' style='color: #ffea00'>PERTR. SV.</div>		<div class='vertical-text' style='color: #d8d8d8'>IŠBRAUKTA</div>	</div>	<div style='clear: left'></div>"+meetingList+"</ul>";
					var output = "<ul><span class='meeting'>" + title + "</span>	<div class='checkboxes checkboxes-desc'>		<div class='vertical-text' style='color: #00fb47'>UŽ</div>		<div class='vertical-text' style='color: #ff1515'>PRIEŠ</div>		<div class='vertical-text' style='color: #ffea00'>PERTR. SV.</div>		<div class='vertical-text' style='color: #d8d8d8'>IŠBRAUKTA</div>	<div class='vertical-text' style='color: #a52a2a'>PERKELTA</div>	</div>	<div style='clear: left'></div>" + meetingList + "</ul>";
					output += '<div class="submit"><input type="hidden" name="votings" value="' + votings + '"/><input type="submit" value="Taikyti" name="apply-meeting"></div>';

					document.getElementById("list").innerHTML = output;
				}
			}
			function renderDel(obj, type) {
				switch (type) {
					case 1:
						type_id = "1";
						type_option_1 = "Atleisti";
						type_option_2 = "Neatleisti";
						type_result_1 = "Priimta";
						type_result_2 = "Nepriimta";
						break;
					case 2:
						type_id = "2";
						type_option_1 = "Pasitiki";
						type_option_2 = "Nepasitiki";
						type_result_1 = "Priimta";
						type_result_2 = "Nepriimta";
						break;
					default:
						type_id = "0";
				}
				var output = "<div id=\"type" + type_id + "-voting\">Nr.: <input type='text' name='number' value='' onChange=\"form_input_message(this, 'is_int');\"/><br/>";
				output += "<div style=\"display:block\">";
				output += "&nbsp;&nbsp;&nbsp; " + type_option_1 + ": <input type='text' name='uz1' value='' onChange=\"form_input_message(this, 'is_int');\"/>";
				output += " " + type_option_2 + ": <input type='text' name='pries1' value='' onChange=\"form_input_message(this, 'is_int');\"/>";
				output += " Rezultatas: <select name='vote_result1'><option value='priimta'>" + type_result_1 + "</option><option value='nepriimta'>" + type_result_2 + "</option></select>";
				output += "</div>";
				output += "</div>";
				output += "<input type='hidden' name='type_id' value='" + getTypeIndex(obj) + "'>";
				output += "<input style=\"margin: 15px 0 0 10px;\" type='button' onClick=\"onSubmit('slaptas');\" value='Taikyti' name='apply-meeting'>";
				if (getTypeIndex(obj) != 3) {
					document.getElementById("votings").disabled = true;
					document.getElementById("render-list").disabled = true;
					document.getElementById("render-list").style.cursor = "default";
					document.getElementById("list").innerHTML = '';
					document.getElementById("list").innerHTML = output;
				} else {
					document.getElementById("votings").disabled = false;
					document.getElementById("render-list").disabled = false;
					document.getElementById("render-list").style.cursor = "pointer";
					document.getElementById("list").innerHTML = '';
				}
			}
			function renderSlaptas(obj) {
				var output = "<div id=\"secret-voting\">Nr.: <input type='text' name='number' value='' onChange=\"form_input_message(this, 'is_int');\"/><br/>";
				output += "<div style=\"display:block\"><label style=\"display: block;\"></label>";
				//output += "&nbsp;&nbsp;&nbsp;Už: <input type='text' name='uz' value='' onChange=\"form_input_message(this, 'is_int');\"/> Prieš: <input type='text' name='pries' value='' onChange=\"form_input_message(this, 'is_int');\"/> Susilaikė: <input type='text' name='susilaike' value='' onChange=\"form_input_message(this, 'is_int');\"/> Rezultatas: <select name='vote_result'><option value='priimta'>Priimta</option><option value='nepriimta'>Nepriimta</option><option value='nkvorumas'>Nėra kvorumo</option></select>";
				output += "&nbsp;&nbsp;&nbsp;Už: <input type='text' name='uz1' value='' onChange=\"form_input_message(this, 'is_int');\"/> Prieš: <input type='text' name='pries1' value='' onChange=\"form_input_message(this, 'is_int');\"/> Susilaikė: <input type='text' name='susilaike1' value='' onChange=\"form_input_message(this, 'is_int');\"/> Rezultatas: <select name='vote_result1'><option value='priimta'>Priimta</option><option value='nepriimta'>Nepriimta</option><option value='nkvorumas'>Nėra kvorumo</option></select>";
				output += "</div>"
				output += "</div>";
				output += "<div id=\"addTeiginys\" style=\"font-size: 18px; font-weight: bold; border: 1px solid white; width: 120px; margin: 15px 0 0 10px; cursor: hand;\" onclick=\"addTeiginys();\">+ Pridėti teiginį</div>";
				output += "<input style=\"margin: 15px 0 0 10px;\" type='button' onClick=\"onSubmit('slaptas');\" value='Taikyti' name='apply-meeting'>";
				if (getTypeIndex(obj) == 3) {
					document.getElementById("votings").disabled = true;
					document.getElementById("render-list").disabled = true;
					document.getElementById("render-list").style.cursor = "default";
					document.getElementById("list").innerHTML = '';
					document.getElementById("list").innerHTML = output;
				} else {
					document.getElementById("votings").disabled = false;
					document.getElementById("render-list").disabled = false;
					document.getElementById("render-list").style.cursor = "pointer";
					document.getElementById("list").innerHTML = '';
				}
			}
			function addTeiginys() {
				var inputForm = document.getElementById('secret-voting');
				//save old data, kad paspaudus Prideti Teigini nenumustu uzpiditu lauku
				//var inputElement = inputForm.getElementsByTagName('input'); // 0-3
				//var selectElement = inputForm.getElementsByTagName('select'); // 0-3

				var text = new Array("pirmąjį", "antrąjį");
				var divElements = inputForm.getElementsByTagName('div');
				for (i = 0; i < divElements.length; i++) {
					//alert(divElements[i].children[1].value);
					//alert(divElements[i].children[2].value);
					//alert(divElements[i].children[3].value);
					//alert(divElements[i].children[4].value);
					if (typeof divElements[i].attributes['id'] == 'undefined') {
						divElements[i].setAttribute("id", "teiginys" + (i + 1));
						divElements[i].children[0].innerHTML = "Už " + text[i] + " teiginį";
					}
				}

				var output = "<div id=\"teiginys" + (i + 1) + "\" style=\"display:block\"><label style=\"display: block;\">Už " + text[i] + " teiginį</label>";
				output += "&nbsp;&nbsp;&nbsp;Už: <input type='text' name='uz" + (i + 1) + "' value='' onChange=\"form_input_message(this, 'is_int');\"/> Prieš: <input type='text' name='pries" + (i + 1) + "' value='' onChange=\"form_input_message(this, 'is_int');\"/> Susilaikė: <input type='text' name='susilaike" + (i + 1) + "' value='' onChange=\"form_input_message(this, 'is_int');\"/> Rezultatas: <select name='vote_result" + (i + 1) + "'><option value='priimta'>Priimta</option><option value='nepriimta'>Nepriimta</option><option value='nkvorumas'>Nėra kvorumo</option></select>";
				output += "</div>";
				inputForm.innerHTML = inputForm.innerHTML + output;
				if ((i + 1) == text.length) {
					document.getElementById('addTeiginys').style.display = "none";
				}


				/* var divElements = inputForm.getElementsByTagName('div');
				 for(i = 0; i < divElements.length; i++) {
				 var elementId = divElements[i].attributes['id'];
				 var elementClass = divElements[i].attributes['class'];
				 var idVal = elementId.value;
				 var classVal = elementClass.value;
				 alert(idVal+" "+classVal);
				 } */
				//var output = "<div id=\"teiginys1\" style=\"display:block\"><label></label>";
				//output += "&nbsp;&nbsp;&nbsp;Už: <input type='text' name='uz' value='' onChange=\"form_input_message(this, 'is_int');\"/> Prieš: <input type='text' name='pries' value='' onChange=\"form_input_message(this, 'is_int');\"/> Susilaikė: <input type='text' name='susilaike' value='' onChange=\"form_input_message(this, 'is_int');\"/> Rezultatas: <select name='vote_result'><option value='priimta'>Priimta</option><option value='nepriimta'>Nepriimta</option><option value='nkvorumas'>Nėra kvorumo</option></select> <input type='button' onClick=\"onSubmit('slaptas');\" value='Taikyti' name='apply-meeting'>";
				//output += "</div>"
			}
			function selectPosedis(object, value) {
				var A = object.options, L = A.length;
				while (L) {
					if (A[--L].text == value) {
						object.selectedIndex = L;
						L = 0;
					}
				}
			}
			/* function selectPosedis(object, value){
			 var A = object.options, L = A.length;
			 while(L){
			 if(A[--L].text == value){
			 object.selectedIndex = L;
			 L = 0;
			 }
			 }
			 } */
			function typeChanged(obj) {
				var index = getTypeIndex(obj);
				//alert(index);
				switch (index) {
					case 0: // tuscias
						document.getElementById("votings").disabled = false;
						document.getElementById("render-list").disabled = false;
						document.getElementById("render-list").style.cursor = "pointer";
						document.getElementById("list").innerHTML = '';
						break;
					case 1: // del atleidimo
						renderDel(obj, 1);
						break;
					case 2: // del nepasitikejimo
						renderDel(obj, 2);
						break;
					case 3: // slaptas
						renderSlaptas(obj);
						break;
				}
			}
			function getTypeIndex(obj) {
				var indexas = obj.selectedIndex;
				return indexas;
			}

		//buvo naudojamas tik slapto checkbox'ui
			/*function checking(element) {
			 var obj = document.getElementById(element);
			 if(obj.checked == true) {
			 obj.checked = false; renderSlaptas(obj);
			 } else {
			 obj.checked = true; renderSlaptas(obj);
			 }
			 }*/

			function form_input_is_int(input) {
				return !isNaN(input) && parseInt(input) == input;
			}
			function form_input_message(obj, type) {
				switch (type) {
					case 'is_int':
						if (form_input_is_int(obj.value) == false || obj.value < 0) {
							alert('Iveskite sveikaji teigiamą skaičių.'/*+obj.outerHTML*/);
							obj.value = '';
							return false;
						}
						break;
				}
			}

		//isjungiam tikrinima, kol sita vieta neistaisyta keliems teiginiams
			function onSubmit(type) {
				switch (type) {
					case 'slaptas':
						//if(form_input_is_int(document.meetings.number.value) == false || form_input_is_int(document.meetings.uz.value) == false ||
						//form_input_is_int(document.meetings.pries.value) == false || form_input_is_int(document.meetings.susilaike.value) == false)
						//	alert('Prašome užpildyti visus tuščius laukus.');
						//else
						document.forms["meetings"].submit();
						//break;
				}
			}
		</script>
	</body>
	<html>

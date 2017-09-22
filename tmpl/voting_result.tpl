<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="charset=utf-8" />
		<style type="text/css">
			body { font-family: "sans-serif";  font-size: 14px!important; width: 740px; }
			.content > .header { font-weight: bold; border-bottom: 1px solid #000; font-size: 20px; text-align: center;  margin-bottom: 8px; }
			.content > .header > .title { margin-bottom: 5px; }
			.content > .question,
			.content > .result > .info,
			.content > .detailed > .info { border: 1px solid #000; padding: 5px 10px 3px 10px;}
			.content > .question > .info th { font-weight: normal; text-align: left; padding: 0 0 15px 0; }
			.content > .question > .info td { padding: 0 0 15px 50px; }
			.content > .question > .time { width: 100%; }
			.content > .result > .title,
			.content > .detailed > .title { font-size: 16px; font-weight: bold; margin: 15px 0 10px 0;}
			.content > .result > .info > table tr.header > td:first-child,
			.content > .detailed > .info > table tr.header > td:first-child { width: 200px; }
			.content > .result > .info > table tr.row > td.header,
			.content > .detailed > .info > table tr.row > td.header { width: 300px; }
		</style>
	</head>
	<body>
		<div class="content">
			<div class="header"><div class="title">Balsavimo rezultatai</div></div>
			<div class="question">
				<table class="info">
					<tr>
						<th>Posėdis</th>
						<td>{meeting_date}</td>
					</tr>
					<tr>
						<th>Klausimas</th>
						<td>{question}</td>
					</tr>
				</table>
				<table class="time">
					<tr>
						<td>Balsavimo pradžia:</td>
						<td>{question_start_datetime}</td>
						<td>Balsavimo pabaiga:</td>
						<td>{question_end_datetime}</td>
					</tr>
				</table>
			</div>
			<div class="result">
				<div class="title">Bendri balsavimo rezultatai</div>
				<div class="info">
					<table class="participants">
						<tr class="header">
							<td>Balsavimo dalyviai</td>
							<td colspan="2"></td>
						</tr>
						<tr class="row">
							<td></td>
							<td class="header">Dalyvavo balsavime</td>
							<td>{participants_count}</td>
						</tr>
						<tr class="row">
							<td></td>
							<td class="header">Dalyvavo bet nebalsavo</td>
							<td>{inactive_participants_count}</td>
						</tr>
					</table>
					<table class="votes">
						<tr class="header">
							<td>Balsai</td>
							<td colspan="2"></td>
						</tr>
						<!-- BEGIN vote_count_block -->
						<tr class="row">
							<td></td>
							<td class="header">{vote}</td>
							<td>{vote_count}</td>
						</tr>
						<!-- END vote_count_block -->
					</table>
					<table class="results">
						<tr class="header">
							<td>Rezultatai</td>
							<td colspan="2"></td>
						</tr>
						<tr class="row">
							<td></td>
							<td class="header">Būtina patvirtinimui</td>
							<td>{must_vote_count}</td>
						</tr>
						<tr class="row">
							<td></td>
							<td class="header">Balsavo Už</td>
							<td>{vote_count_for}</td>
						</tr>
						<tr class="row">
							<td></td>
							<td class="header">Ar priimta</td>
							<td>{vote_result}</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="detailed">
				<div class="title">Individualūs balsavimo rezultatai</div>
				<div class="info">
					<table class="votes">
						<!-- BEGIN vote_block -->
						<tr class="header">
							<td>{vote}</td>
							<td></td>
						</tr>
						<!-- BEGIN vote_person_block -->
						<tr class="row">
							<td></td>
							<td class="header">{person}</td>
						</tr>
						<!-- END vote_person_block -->
						<!-- END vote_block -->
					</table>
				</div>
			</div>
		</div>
	</body>
</html>
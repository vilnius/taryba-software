<?php

include 'helper/functions.php';

if (isset($_GET['file']) && is_file($_GET['file']) && strpos('..', $_GET['file']) === FALSE) {

	function setprintjs($buffer) {
		$js = '<script type="text/javascript">
function printHtml() {
	try { this.print(); }
	catch(e) { window.onload = window.print; } }
	setTimeout(function() { printHtml() }, 1000);
</script>';
		return str_replace('</head>', $js . '</head>', $buffer);
	}

	ob_start("setprintjs");
	include($_GET['file']);
	ob_end_flush();
}
<?php

/* Get inverter date. Return as string in format: "<current>,<total_today>,<total>" */
function get_inverter_data() {
	$context = stream_context_create(array(
			'http' => array(
					'header'  => "Authorization: Basic " . base64_encode("pvserver:pvwr"))));
	$data = file_get_contents('http://inverter.fritz.box', false, $context);

	$search_string = '<td width="70" align="right" bgcolor="#FFFFFF">';
	$sl = strlen($search_string);

	/* Locate fields in order current, total, totalDaily */
	$cur_pos = strpos($data, $search_string, 0) + $sl;
	$total_pos = strpos($data, $search_string, $cur_pos+1) + $sl;
	$total_daily_pos = strpos($data, $search_string, $total_pos+1) + $sl;

	/* echo "${cur_pos}, ${total_pos}, ${total_daily_pos}"; */

	$cur = get_field($data, $cur_pos);
	if (strpos($cur, ' x x') != FALSE)
		$cur = 0;
	$total = get_field($data, $total_pos);
	$total_daily = get_field($data, $total_daily_pos);

	return join(",", array($cur, $total_daily, $total));
}

function get_field($data, $start_pos) {
	$end_pos = strpos($data, "</td>", $start_pos);
	$field = substr($data, $start_pos, $end_pos - $start_pos);
	return trim($field);
}

/* main loop. infinate. get, publish, wait, repeat */
while (TRUE) {
	/* get */
	$inverter_data = get_inverter_data();
	echo date("Y-m-d h:i:s");
	echo ":  Data = ${inverter_data}\n";

	/* publish */
	$data = file_get_contents("http://vedelmarkussen.dk/wp/?page_id=142&solarVar=${inverter_data}");

			sleep (900);
}


?>

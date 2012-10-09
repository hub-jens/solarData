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
	
	/* Next are: string voltage 1(V), L1(V), string 1 current (A), L1 power(W), string 2 voltage (V), L2(V), string 2 current (A) */
	/* Aim is to calculate DC input and thereby efficiency */
	$s1_v_pos = strpos($data, $search_string, $total_daily_pos+1) + $sl;
	$l1_v_pos = strpos($data, $search_string, $s1_v_pos+1) + $sl;
	$s1_a_pos = strpos($data, $search_string, $l1_v_pos+1) + $sl;
	$l1_w_pos = strpos($data, $search_string, $s1_a_pos+1) + $sl;
	$s2_v_pos = strpos($data, $search_string, $l1_w_pos+1) + $sl;
	$l2_v_pos = strpos($data, $search_string, $s2_v_pos+1) + $sl;
	$s2_a_pos = strpos($data, $search_string, $l2_v_pos+1) + $sl;
	
	$s1_v = get_field($data, $s1_v_pos);
	$s1_a = get_field($data, $s1_a_pos);
	$s2_v = get_field($data, $s2_v_pos);
	$s2_a = get_field($data, $s2_a_pos);
	
	if ($cur == 0) {
		$dc = 0;
		$eff = 0;
	} else {
		$dc = $s1_v * $s1_a + $s2_v * $s2_a;
		$eff = round (100.0 * $cur / $dc, 1);
	}
	
	/* echo "${s1_v} ${s1_a} ${s2_v} ${s2_a} .. ${dc} .. ${eff}\n"; */
	

	return join(",", array($cur, $total_daily, $total, $eff));
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

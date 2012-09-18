<?php
/*
 Plugin Name: Solar Data
Plugin URI: http://www.vedelmarkussen.dk
Description: Receive and show basic solar data
Version: 0.1
Author: Jens Vedel Markussen
Author URI: http://www.vedelmarkussen.dk
License: Copyrighted
*/

/*
 URL Params (Wordpress Plugin)
Copyright (C) 2012 Jens Vedel Markussen

*/

date_default_timezone_set ("Europe/Copenhagen");

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'solar_data_install');

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'solar_data_remove' );

function solar_data_install() {
	/* Creates new database fields */

	/* Format: time (unix seconds), current (W), total_today (kWh), total (kWh), last_average (W) */
	add_option('solar_data', '0,0,0,0,0', '', 'yes');
}

function solar_data_remove() {
	/* Deletes the database fields */
	delete_option('solar_data');
}

/* Main post function - make it a shortname */
add_shortcode("solar_data_post", "solar_data_post");
function solar_data_post($attrs) {
	/* Retrieve current db values */
	$dbTemp = get_option('solar_data');
	if (!$dbTemp) {
		solar_data_install();
		$dbTemp = get_option('solar_data');
	}
	/* Format: time (unix seconds), current (W), total_today (kWh), total (kWh), last_average (W) */
	list($old_time, $old_current, $old_total_today, $old_total, $last_average) = explode(",", $dbTemp);

	/* Look for solarVar input variable in URL */
	if (!$_REQUEST['solarVar'])
		return; /* no data! */

	/* decode URL argument solarVar */
	list ($current, $total_today, $total) = explode(",", $_REQUEST['solarVar']);

	/* get current time */
	$time = time();

	if ($old_time == 0 || $old_total_today > $total_today) {
		/* First update call or new day, special handling */
		$average = 0;
	} else {
		$interval = $time - $old_time;
		$average = (int) (3600000 * ($total_today - $old_total_today) / $interval);
	}

	/* store values in db */
	update_option('solar_data', implode(",", array($time, $current, $total_today, $total, $average)));
}

add_shortcode("solar_data_value", "solar_data_value");
function solar_data_value($attrs) {
	extract( shortcode_atts( array(
			'param' => 'total_today'
	), $attrs) );

	$dbTemp = get_option('solar_data');
	if (!$dbTemp)
		return "unavailable";

	/* Format: time (unix seconds), current (W), total_today (kWh), total (kWh), last_average (W) */
	list($time, $current, $total_today, $total, $average) = explode(",", $dbTemp);

	$value = $total_today;
	$unit = ' kWh';

	switch ($param) {
		case 'current':
			$value = $current;
			$unit = ' W';
			break;
		case 'total':
			$value = $total;
			break;
		case 'average':
			$value = $average;
			$unit = ' W';
			break;
		case 'time':
			$value = date("j/n-Y G:i", $time);
			$unit = '';
			break;
	}

	return "{$value}{$unit}";
}

?>
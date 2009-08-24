<?php

/**
 * stuff for localization
 */
$polarbear_arr_lang = array(
	'sv_SE' => array(
		'Polar Bear' => 'Polar Bear CMS',
		'hours ago' => 'timmr sedan',
		'X hours ago' => '%d timmar sedan',
		'minutes ago' => 'minuter sedan',
		'days ago' => 'dagar sedan',
		'weeks ago' => 'weckor sedan',
		'last month' => 'förra månaden',
		'in a minute' => 'om en monit',
		'in X minutes' => 'om %d minuter',
		'in an hour' => 'om en timme',
		'in X hours' => 'om %d timmar',
		'tomorrow' => 'imorgon',
		'next week' => 'nästa vecka',
		'in X weeks' => 'om %d veckor',
		'next month' => 'nästa månad',
		'just now' => 'alldeles nyss',
		'1 minute ago' => '1 minut sedan',
		'Yesterday' => 'Igår',
		"Today" => "Idag"
	),
	'en_US' => array(
		'Polar Bear' => 'Polar Bear CMS',
		'hours ago' => 'hours ago',
		'X hours ago' => '%d hours ago',
		'minutes ago' => 'minutes ago',
		'days ago' => 'days ago',
		'weeks ago' => 'weeks ago',
		'last month' => 'last month',
		'in a minute' => 'in a minute',
		'in X minutes' => 'in %d minutes',
		'in an hour' => 'in an hour',
		'in X hours' => 'in %d hours',
		'tomorrow' => 'tomorrow',
		'next week' => 'next week',
		'in X weeks' => 'in %d weeks',
		'next month' => 'next month',
		'just now' => 'just now',
		'1 minute ago' => '1 minute ago',
		'Yesterday' => 'Yesterday',
		"Today" => "Today"
	)
);

function polarbear_msg($str, $arg1 = null) {
	global $polarbear_lang;
	global $polarbear_arr_lang;
	$lang = $polarbear_lang;
	if (!$lang) { $lang = 'en_US'; }
	
	if (isset($polarbear_arr_lang[$lang][$str])) {
		
		if (isset($arg1)) {
			return sprintf($polarbear_arr_lang[$lang][$str], $arg1);
		} else {
			return $polarbear_arr_lang[$lang][$str];
		}

	} else {
		return "String <strong>'$str'</strong> for lang <strong>'$lang'</strong> not found.";
	}
	
}

?>
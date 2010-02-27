<?php

// http://codex.wordpress.org/Shortcode_API

$pb_shortcode_tags = array();


function pb_add_shortcode($tag, $func) {
	global $pb_shortcode_tags;
	if ( is_callable($func) ) {
		$pb_shortcode_tags[$tag] = $func;
	}
}

function pb_remove_shortcode($tag) {
	global $pb_shortcode_tags;

	unset($pb_shortcode_tags[$tag]);
}

function pb_remove_all_shortcodes() {
	global $pb_shortcode_tags;

	$pb_shortcode_tags = array();
}

function pb_do_shortcode($args) {

	$content = $args["output"];
	global $pb_shortcode_tags;

	if (empty($pb_shortcode_tags) || !is_array($pb_shortcode_tags))
		return $args;

	$pattern = pb_get_shortcode_regex();
	$args["output"] = preg_replace_callback('/'.$pattern.'/s', 'pb_do_shortcode_tag', $content);

	pb_pqp_log_speed("do shortcode");

	return $args;
}

function pb_get_shortcode_regex() {
	global $pb_shortcode_tags;

	$tagnames = array_keys($pb_shortcode_tags);
	$tagregexp = join( '|', array_map('preg_quote', $tagnames) );

	return '(.?)\[('.$tagregexp.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)';
}

function pb_do_shortcode_tag($m) {

	global $pb_shortcode_tags;

	// allow [[foo]] syntax for escaping a tag
	if ($m[1] == '[' && $m[6] == ']') {
		return substr($m[0], 1, -1);
	}

	$tag = $m[2];
	$attr = pb_shortcode_parse_atts($m[3]);

	pb_pqp_log_speed("do shortcode tag");

	if ( isset($m[5]) ) {
		// enclosing tag - extra parameter
		return $m[1] . call_user_func($pb_shortcode_tags[$tag], $attr, $m[5], $m[2]) . $m[6];
	} else {
		// self-closing tag
		return $m[1] . call_user_func($pb_shortcode_tags[$tag], $attr, NULL, $m[2]) . $m[6];
	}
}


function pb_shortcode_parse_atts($text) {
	$atts = array();
	$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
	$text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
	if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
		foreach ($match as $m) {
			if (!empty($m[1]))
				$atts[strtolower($m[1])] = stripcslashes($m[2]);
			elseif (!empty($m[3]))
				$atts[strtolower($m[3])] = stripcslashes($m[4]);
			elseif (!empty($m[5]))
				$atts[strtolower($m[5])] = stripcslashes($m[6]);
			elseif (isset($m[7]) and strlen($m[7]))
				$atts[] = stripcslashes($m[7]);
			elseif (isset($m[8]))
				$atts[] = stripcslashes($m[8]);
		}
	} else {
		$atts = ltrim($text);
	}
	pb_pqp_log_speed("shortcode partse atts");
	return $atts;
}


?>
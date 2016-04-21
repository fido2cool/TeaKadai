<?php

if (!function_exists('convert_php_size_to_bytes')) {
	//This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
	function convert_php_size_to_bytes($sSize)
	{
		if (is_numeric($sSize))
		   return $sSize;

		$sSuffix = substr($sSize, -1);
		$iValue = substr($sSize, 0, -1);

		switch(strtoupper($sSuffix))
		{
		case 'P':
			$iValue *= 1024;
		case 'T':
			$iValue *= 1024;
		case 'G':
			$iValue *= 1024;
		case 'M':
			$iValue *= 1024;
		case 'K':
			$iValue *= 1024;
			break;
		}
		return ($iValue);
	}
}

if (!function_exists('redirect_https')) {
	function redirect_https()
	{
		if (!is_ssl()) {
			$redirect= "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header("Location:$redirect");
		}
	}
}

if (!function_exists('dateformat_PHP_to_jQueryUI')) {
	/*
	 * Matches each symbol of PHP date format standard
	 * with jQuery equivalent codeword
	 * @author Tristan Jahier
	 */
	function dateformat_PHP_to_jQueryUI($php_format)
	{
	    $SYMBOLS_MATCHING = array(
	        // Day
	        'd' => 'dd',
	        'D' => 'D',
	        'j' => 'd',
	        'l' => 'DD',
	        'N' => '',
	        'S' => '',
	        'w' => '',
	        'z' => 'o',
	        // Week
	        'W' => '',
	        // Month
	        'F' => 'MM',
	        'm' => 'mm',
	        'M' => 'M',
	        'n' => 'm',
	        't' => '',
	        // Year
	        'L' => '',
	        'o' => '',
	        'Y' => 'yyyy',
	        'y' => 'yy',
	        // Time
	        'a' => '',
	        'A' => '',
	        'B' => '',
	        'g' => '',
	        'G' => '',
	        'h' => '',
	        'H' => '',
	        'i' => '',
	        's' => '',
	        'u' => ''
	    );
	    $jqueryui_format = '';
	    $escaping = FALSE;
	    for ($i = 0; $i < strlen($php_format); $i++) {
	        $char = $php_format[$i];
	        if ('\\' === $char) {		// PHP date format escaping character
	            ++$i;
	            if ($escaping)
					$jqueryui_format .= $php_format[$i];
	            else
					$jqueryui_format .= '\'' . $php_format[$i];
	            $escaping = TRUE;
	        } else {
	            if ($escaping) {
					$jqueryui_format .= "'";
					$escaping = FALSE;
				}
	            if (isset($SYMBOLS_MATCHING[$char]))
	                $jqueryui_format .= $SYMBOLS_MATCHING[$char];
	            else
	                $jqueryui_format .= $char;
	        }
	    }
	    return ($jqueryui_format);
	}
}

if (!function_exists('ps_oembed_get')) {
	/**
	 * PeepSo wrapper for wp_oembed_get.
	 * Turns off discover for oembed calls when the WP version is less than 3.9 prior to https://core.trac.wordpress.org/ticket/27656 .
	 * Attempts to fetch the embed HTML for a provided URL using oEmbed.
	 *
	 * @see WP_oEmbed
	 *
	 * @uses _wp_oembed_get_object()
	 * @uses WP_oEmbed::get_html()
	 *
	 * @param string $url The URL that should be embedded.
	 * @param array $args Additional arguments and parameters.
	 * @return bool|string False on failure or the embed HTML on success.
	 */
	function ps_oembed_get($url, $args = '', $check_force = FALSE)
	{
		global $wp_version;

		if (version_compare($wp_version, '3.9') < 0) {
			$args['discover'] = FALSE;
		}

		require_once( ABSPATH . WPINC . '/class-oembed.php' );

		$oembed = _wp_oembed_get_object();

		$html = $oembed->get_html( $url, $args );

		// < 1.2.0 - for legacy reasons return only HTML if the third flag is not set
		if( FALSE === $check_force ) {
			return $html;
		}

		// >= 1.2.0 build a response array
		$return = array(
			'html'				=> $html,
			'force_oembed' 		=> FALSE,
		);

		// if it's a valid oembed
		if( $oembed->get_provider($url) || $oembed->discover($url)  ) {
			$return['force_oembed'] = TRUE;
		} else {
			// if NOT an oembed, reset the content to force og-image fallback
			$return['html'] = '';
		}

		return $return;
	}
}


if (!function_exists('ps_isempty')) {
	/**
	 * Checks parameter value to be 'empty', as in: not assigned, FALSE, NULL, empty string or empty array
	 * Note: a string of '0' is *NOT* considered 'empty', unlike the PHP isempty() function
	 * @param mixed $val
	 * @return Boolean TRUE if value is empty as defined above; otherwise FALSE
	 */
	function ps_isempty($val)
	{
		if (!isset($val) || is_null($val) ||
			(is_string($val) && '' === trim($val) && !is_bool($val)) ||
			(FALSE === $val && is_bool($val)) ||
			(is_array($val) && empty($val)))
			return (TRUE);
		return (FALSE);
	}
}

if (!function_exists('truncateHtml')) {
	function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
	{
		if ($considerHtml) {
			// if the plain text is shorter than the maximum length, return the whole text
			if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = strlen($ending);
			$open_tags = array();
			$truncate = '';
			foreach ($lines as $line_matchings) {
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1])) {
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
						// do nothing
						// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
							unset($open_tags[$pos]);
						}
						// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, strtolower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}
				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
				if ($total_length + $content_length > $length) {
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;
					// search for html entities
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1] + 1 - $entities_length <= $left) {
								$left--;
								$entities_length += strlen($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}
					$truncate .= substr($line_matchings[2], 0, $left + $entities_length);
					// maximum lenght is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}
				// if the maximum length is reached, get off the loop
				if ($total_length >= $length) {
					break;
				}
			}
		} else {
			if (strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = substr($text, 0, $length - strlen($ending));
			}
		}
		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				$truncate = substr($truncate, 0, $spacepos);
			}
		}
		// add the defined ending to the text
		$truncate .= $ending;
		if ($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}
		return $truncate;
	}
}
// EOF

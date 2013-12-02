<?php
class Vki {

	/**
	 * Prepares parameters for vox use
	 * @param  array $params - Parameters for vox
	 * @return array
	 */
	static function vox_prep_args($params){

		if(!is_array($params) ) {
			$prep = array();
			$prep['label'] = $params;
		}

		return $prep;
	}

	/**
	 * Presents given strings in a readable format
	 * @param  mixed $utterance - Object to display
	 * @param  array $params - Parameters for the output
	 * @return string 
	 */
	static function vox($utterance, $params = null){

		extract(self::vox_prep_args($params) );

		$format = (!isset($format)) ? 'html' : $format;
		$sentence = var_export($utterance, 1);

		switch($format) {

			case 'html': 
			$articulation = self::vox_html($sentence, $params);
			break;

			default:
			$articulation = $sentence;
			break;
		}

		echo $articulation;
		return $articulation;
	}

	/**
	 * Parses HTML for vox
	 * @param  mixed $utterance - Object to display
	 * @param  array $params - Parameters for the output
	 * @return [type]
	 */
	static function vox_html($utterance, $params){

		extract(self::vox_prep_args($params) );

		$tag = (!isset($tag)) ? 'pre' : $tag;
		$label = (!isset($label)) ? '' : $label;

		$articulation = sprintf('<%s class="vox">%s%s</%s><!-- /.vox -->', $tag, $label, $utterance, $tag);

		return $articulation;

	}


	/**
	 * Truncates a string to a set number of words
	 * @param  String $string
	 * @param  Array $params
	 * @return Truncated string
	 */
	static function truncate_string($string, $params){
		$length = (is_int($params)) ? $params : (int) $params['length'];
		$collapse = (bool) $params['collapse'];
		$strip_tags = (bool) $params['strip_tags'];
		$no_escape = (bool) $params['no_escape'];

		// Truncate the excerpt to the proper length
		$words = explode(' ', $string);

		foreach($words as $word){
			static $i;
			$i++;
			if($i > $length) { $i = 0; break; }
			$truncated .= $word.' ';
		}

		if($collapse) $truncated = preg_replace('#[\n\r]#', " ", $truncated);
		if($strip_tags) $truncated = strip_tags($truncated);
		if($no_escape) $truncated = stripcslashes($truncated);

		return $truncated;
	}


	/**
	 * Convert non-breaking space to a forced break
	 *
	 * @str - String to convert
	 *
	 * Returns string with non-breaking space converted to newlines
	 */
	static function nbsp_to_break($str) {

		$converted = preg_replace('/\xC2\xA0/', "\n", $str);

		return $converted;
	}

	/**
	* Convert break to html break
	*
	* @str - String to convert
	* @break_type - Optional break type, default: br
	*
	* Returns string with newlines converted to breaks
	*/
	static function break_to_html($str, $break_type = 'br'){

		if ($break_type != "br"){
			$before = "<{$break_type}>";
			$after = "</{$break_type}"; 
		}
		else {
			$before = null;
			$after = "<{$break_type}/>";
		}

	// TODO: Refine replace to allow placing elements before and after
		$converted = preg_replace('/[\n|\r]/', "{$after}", $str);

		return $converted;
	}
	
	/**
	* Converts text QA to HTML QA
	*
	* @str - String containing QA
	* @match_filter - Function name to use to filter matches
	* 
	* Returns html-formatted QA
	*/
	/**
	 * Converts QA text to an HTML QA
	 * @param  string $qa - QA text to convert
	 * @param  string $match_filter - Function to call to filter the string
	 * @return string QA - formatted as HTML
	 */
	static function qa_to_html($qa, $match_filter = null) {

		$lines = explode("\n", $qa);
		$q_rx = "/^[\t ]*Q\.\s/i";
		$a_rx = "/^[\t ]*A\.\s/i";
		$open_qa_rx = "/start qa/i";
		$close_qa_rx = "/end qa/i";

		$qa_container = 'dl';
		$q_tag = 'dt';
		$a_tag = 'dd';
		$html;


		foreach($lines as $line) {

			static $container_open = false;

			// Open or close the qa container as requested
			if (preg_match($open_qa_rx, $line) ) {

				$container_open = true;
				$line = null;
				$html .= "\n<{$qa_container}>";
			}

			elseif ($container_open && preg_match($close_qa_rx, $line) ) {

				$container_open = false;
				$line = null;
				$html .= "\n</{$qa_container}>\n";
			}

			// Properly tag Q and A lines appropriately
			if($line && $container_open){

				$processed_line = (function_exists($match_filter) ) ?  $match_filter($line) : $line;

				if(preg_match($q_rx, $line) ){

					$match = true;
					$html .= "\n<{$q_tag}>".preg_replace($q_rx, "", $processed_line)."</{$q_tag}>";
				}

				elseif(preg_match($a_rx, $line) ){

					$match = true;
					$html .= "\n<{$a_tag}>".preg_replace($a_rx, "", $processed_line)."</{$a_tag}>";
				}

				else {
					$match = false;
				}
			}

			// Let other lines through normally
			if(!$match && $line) 
				$html .= "\n<p>{$line}</p>";
		}

		if ($container_open) { 

			$container_open = false;
			$html .= "\n</{$qa_container}>\n";
		}
		echo "<!--\n".var_export($lines, 1)."\n-->";

		return $html;
	}
	
	/**
	 * Converts html breaks to markdown breaks
	 * @param  string $html
	 * @return string Text with markdown-style breaks
	 */
	static function convert_html_breaks($html){

	// Convert break elements to newline elements (\n)
		$converted = preg_replace("/<br[\/\s]*>/i", "\n", $html);

	// Convert paragraph elements to markdown paragraph elements (\n\n)
		if( preg_replace("/<p[^>]*>/i", "\n\n", $converted) ) {
			$converted = preg_replace("/<\/p>/i", "\n", $converted);
		}
		return $converted;
	}
}
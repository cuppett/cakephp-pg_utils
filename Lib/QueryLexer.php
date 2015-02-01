<?php
/**
 * Inspired by http://stackoverflow.com/questions/207817/converting-a-google-search-query-to-a-postgresql-tsquery
 * @author Peter Bailey
 * @author Stephen Cuppett
 */

app::uses('ILexer', 'PgUtils.Lib');

class QueryLexer implements ILexer
{
	const trimmables = " \t\n\r\0\x0B-";
	private $delimit_symbols = array('<', '>', ':', '-', '+');
	private $join_words = array('', 'and', 'or');

	public function getTokens( $str )
	{
		$tokenStack = array();
		$chars = str_split( $str );
		$in_quotes = false;
		$holding_string = '';
		foreach ( $chars as $char )
		{
			// The end of a subgroup.
			// Ending any current string and identifying the delimiter.
			if ($char == ')' && !$in_quotes) {
				if ($holding_string != '') {
					$tokenStack[] = $holding_string;
					$holding_string = '';
				}
				$tokenStack[] = $char;
				continue;
			}

			// The beginning of a subgroup.
			if ($char == '(' && ($holding_string == '' || in_array(trim($holding_string, QueryLexer::trimmables), $this->join_words)) && !$in_quotes) {
				$tokenStack[] = $holding_string . $char;
				$holding_string = '';
				continue;
			}

			// The end of a quoted string!
			if ($char == '"' && $holding_string != '' && $in_quotes) {
				$tokenStack[] = $holding_string;
				$holding_string = '';
				$in_quotes = false;
				continue;
			}

			// The beginning of a quoted string
			if ($char == '"' && !$in_quotes) {
				if ($holding_string == '') {
					$in_quotes = true;
					continue;
				} else {
					$last_char = substr($holding_string, -1);
					$in_quotes = in_array($last_char, $this->delimit_symbols);
					if ($in_quotes) continue;
				}
			}

			// Whitespace stops the term
			if ($char == ' ' && !$in_quotes && !in_array($holding_string, $this->join_words) && !$in_quotes) {
				$tokenStack[] = $holding_string;
				$holding_string = '';
				continue;
			}

			// No need for extraneous spaces
			if ($char == ' ' && $holding_string == '') continue;

			// All other characters, just add it to the holding string
			// Should we strip out special characters?
			$holding_string .= $char;
		}

		if ($holding_string != '')
			$tokenStack[] = $holding_string;

		return $tokenStack;
	}
}

<?php
/**
 * Inspired by http://stackoverflow.com/questions/207817/converting-a-google-search-query-to-a-postgresql-tsquery
 * @author Peter Bailey
 * @author Stephen Cuppett
 */

class QueryPhrase
{
	const MODE_AND = 1;
	const MODE_OR = 2;
	const MODE_EXCLUDE = 4;

	protected $phrase;
	protected $mode;

	public function __construct( $input, $mode=self::MODE_AND ) {
		$this->phrase = $input;
		$this->mode = $mode;
	}

	public function getMode() {
		return $this->mode;
	}

	public function __toString() {
		return $this->phrase;
	}
}

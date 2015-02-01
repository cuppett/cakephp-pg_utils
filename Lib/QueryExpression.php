<?php
/**
 * Inspired by http://stackoverflow.com/questions/207817/converting-a-google-search-query-to-a-postgresql-tsquery
 * @author Peter Bailey
 * @author Stephen Cuppett
 */
app::uses ( 'QueryPhrase', 'PgUtils.Lib' );

class QueryExpression {

	protected $mode;
	protected $phrases = array();
	protected $subExpressions = array();
	protected $parent;

	public function __construct($parent = null, $or = false, $exclusion = false) {
		$this->parent = $parent;
		$this->mode = ($or ? QueryPhrase::MODE_OR : QueryPhrase::MODE_AND) |
			($exclusion ? QueryPhrase::MODE_EXCLUDE : 0);
	}

	public function initiateSubExpression($or = false, $exclusion = false) {
		$expression = new self($this, $or, $exclusion);
		$this->subExpressions[] = $expression;
		return $expression;
	}

	public function getMode() {
		return $this->mode;
	}

	public function getPhrases() {
		return $this->phrases;
	}

	public function getSubExpressions() {
		return $this->subExpressions;
	}

	public function getParentExpression() {
		return $this->parent != null ? $this->parent : $this;
	}

	protected function addQueryPhrase(QueryPhrase $phrase) {
		$this->phrases[] = $phrase;
	}

	public function addPhrase($input, $or = false, $exclusion = false) {
		$this->addQueryPhrase (
			new QueryPhrase (
				$input,
				($or ? QueryPhrase::MODE_OR : QueryPhrase::MODE_AND) |
				($exclusion ? QueryPhrase::MODE_EXCLUDE : 0)
			)
		);
	}
}

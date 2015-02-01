<?php
/**
 * Inspired by http://stackoverflow.com/questions/207817/converting-a-google-search-query-to-a-postgresql-tsquery
 * @author Peter Bailey
 * @author Stephen Cuppett
 */
App::uses('QueryLexer', 'PgUtils.Lib');
App::uses('QueryParser', 'PgUtils.Lib');
App::uses('QueryExpression', 'PgUtils.Lib');

class CakeTsQueryBuilder {

	private $query;

	public function __construct($expression, $field_weights = array()) {
		// Add the colon in the first pass.
		$kf = array();
		foreach($field_weights as $key => $value) {
			$kf[$key . ':'] = $value;
		}

		$lexer = new QueryLexer();
		$parser = new QueryParser($lexer);

		$this->query = $this->buildQuery($parser->parse($expression), $kf);
	}

	public function getQuery() {
		return $this->query;
	}

	private function buildQuery($expression, $known_fields) {
    	$query = '';
    	$phrases = $expression->getPhrases();
    	$subExpressions = $expression->getSubExpressions();

    	foreach ( $phrases as $phrase )
    	{
    		$hasWeight = false;
    		$format = '\'%s\'';

   	    	// Look for labeled fields.
			foreach($known_fields as $key => $value) {
				if (strpos(strtolower($phrase), strtolower($key)) === 0) {
					$hasWeight = $key;
					$format .= ':%s';
					break;
				}
			}

			// Adding the operators
			if ($phrase->getMode() & QueryPhrase::MODE_EXCLUDE)
				$format = '!' . $format;

			if (strlen($query) > 0) {
				if ($phrase->getMode() & QueryPhrase::MODE_OR)
					$format = '| ' . $format;
				else
					$format = '& ' . $format;
			}

			// Formatting the appropriate values into the addition
    		if (strlen($query) > 0) $query .= ' ';
    		$query .= sprintf(
    			$format,
    			str_replace( '\'', '\\\'', $hasWeight ? substr($phrase, strlen($hasWeight)) : $phrase),
    			($hasWeight ? $known_fields[$hasWeight] : null)
			);
    	}

    	// Now add the subexpressions
    	foreach ( $subExpressions as $subExpression )
    	{
    		// Adding join operator
    		if (strlen($query) > 0) {
    			$query .= ' ';
    			if ($subExpression->getMode() & QueryPhrase::MODE_OR) {
    				$query .= '| ';
    			} else {
    				$query .= '& ';
    			}
    		}
    		// Adding negation if needed
    		if ($subExpression->getMode() & QueryPhrase::MODE_EXCLUDE)
    			$query .= '!';

    		// Formatting in the query
    		$query .= '(' . $this->buildQuery($subExpression, $known_fields) . ')';
    	}
    	return $query;
	}

}

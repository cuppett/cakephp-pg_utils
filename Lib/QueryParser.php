<?php
/**
 * Inspired by http://stackoverflow.com/questions/207817/converting-a-google-search-query-to-a-postgresql-tsquery
 * @author Peter Bailey
 * @author Stephen Cuppett
 */

app::uses('IParser', 'PgUtils.Lib');
app::uses('QueryExpression', 'PgUtils.Lib');

class QueryParser implements IParser {

	protected $lexer;

	public function __construct( ILexer $lexer ) {
		$this->lexer = $lexer;
	}

	public function parse( $input ) {

		$tokens = $this->lexer->getTokens( $input );
		$expression = new QueryExpression();

		foreach ($tokens as $token) {
			$expression = $this->processToken( $token, $expression );
		}

		// Unwind the stack of subexpressions
		while($expression->getParentExpression() != $expression)
			$expression = $expression->getParentExpression();

		return $expression;
	}

	protected function processToken($token, QueryExpression $expression) {
		$or = false;
		$exclude = false;

		// Determine logical operator first.
		$operator = trim ( substr ( $token, 0, strpos ( $token, ' ' ) ) );
		$phrase = trim ( substr ( $token, strpos ( $token, ' ' ) ) );
		switch (strtolower ( $operator )) {
			case 'or' :
				$or = true;
			case 'and' :
				$token = $phrase;
				break;
		}

		// Determine if negation is there.
		$modifier = $token[0];
		$phrase = trim(substr ( $token, 1 ));
		switch ($modifier) {
			case '-' :
				$exclude = true;
			case '+' :
				$token = $phrase;
				break;
		}

		switch ($token) {
			case '(' :
				return $expression->initiateSubExpression($or, $exclude);
				break;
			case ')' :
				return $expression->getParentExpression();
				break;
			default:
				if (trim($token) != '')
					$expression->addPhrase($token, $or, $exclude);
				break;

		}
		return $expression;
	}
}

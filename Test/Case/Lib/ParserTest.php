<?php
App::uses('QueryLexer', 'PgUtils.Lib');
App::uses('QueryParser', 'PgUtils.Lib');
App::uses('QueryExpression', 'PgUtils.Lib');

class ParserTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->lexer = new QueryLexer();
		$this->parser = new QueryParser($this->lexer);
	}

	public function testSimpleWords() {
		$query_expression = $this->parser->parse('sam had a green gun');
		$phrases = $query_expression->getPhrases();

		// Validate the counts
		$this->assertEquals(count($phrases), 5);
		$this->assertEquals(count($query_expression->getSubExpressions()), 0);

		// Validate the phrases
		$elements = array('sam', 'had', 'a', 'green', 'gun');
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), QueryPhrase::MODE_AND);
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}
	}

	public function testQuoteWords() {
		$query_expression = $this->parser->parse('sam "had a" "green gun"');
		$phrases = $query_expression->getPhrases();

		// Validate the counts
		$this->assertEquals(count($phrases), 3);
		$this->assertEquals(count($query_expression->getSubExpressions()), 0);

		// Validate the phrases
		$elements = array('sam', 'had a', 'green gun');
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), QueryPhrase::MODE_AND);
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}
	}

	public function testMinus() {
		$query_expression = $this->parser->parse('sam "had a" -"green gun"');
		$phrases = $query_expression->getPhrases();

		// Validate the counts
		$this->assertEquals(count($phrases), 3);
		$this->assertEquals(count($query_expression->getSubExpressions()), 0);

		// Validate the phrases
		$elements = array('sam', 'had a', 'green gun');
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_AND, QueryPhrase::MODE_AND | QueryPhrase::MODE_EXCLUDE);
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), array_shift($modes));
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}
	}

	public function testMinusOr() {
		$tokens = $this->lexer->getTokens('sam "had a" or -"green gun"');
		$this->assertEquals(3, count($tokens));
		$this->assertEquals('sam', $tokens[0]);
		$this->assertEquals('had a', $tokens[1]);
		$this->assertEquals('or -green gun', $tokens[2]);

		$query_expression = $this->parser->parse('sam "had a" or -"green gun"');
		$phrases = $query_expression->getPhrases();

		// Validate the counts
		$this->assertEquals(count($phrases), 3);
		$this->assertEquals(count($query_expression->getSubExpressions()), 0);

		// Validate the phrases
		$elements = array('sam', 'had a', 'green gun');
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_AND, QueryPhrase::MODE_OR | QueryPhrase::MODE_EXCLUDE);
		foreach($phrases as $phrase) {
			$this->assertEquals(array_shift($modes), $phrase->getMode());
			$this->assertEquals(array_shift($elements), $phrase->__toString());
		}
	}

	public function testLessThan() {
		$query_expression = $this->parser->parse('sam "had a" modified:<"2010-04-07"');
		$phrases = $query_expression->getPhrases();

		// Validate the counts
		$this->assertEquals(count($phrases), 3);
		$this->assertEquals(count($query_expression->getSubExpressions()), 0);

		// Validate the phrases
		$elements = array('sam', 'had a', 'modified:<2010-04-07');
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_AND, QueryPhrase::MODE_AND);
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), array_shift($modes));
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}
	}

	public function testGroupedBy() {
		$query_expression = $this->parser->parse('sam (jim or bacon) modified:<"2010-04-07"');
		$phrases = $query_expression->getPhrases();
		$subexpressions = $query_expression->getSubExpressions();

		// Validate the counts
		$this->assertEquals(count($phrases), 2);
		$this->assertEquals(count($subexpressions), 1);

		// Validate the phrases
		$elements = array('sam', 'modified:<2010-04-07');
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), QueryPhrase::MODE_AND);
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}

		// Validate the subphrases
		$elements = array('jim', 'bacon');
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_OR);
		foreach ($subexpressions as $expression) {
			$this->assertEquals(QueryPhrase::MODE_AND, $expression->getMode());
			$this->assertEquals(count($expression->getSubExpressions()), 0);
			$this->assertEquals(count($expression->getPhrases()), 2);
			foreach($expression->getPhrases() as $phrase) {
				$this->assertEquals($phrase->getMode(), array_shift($modes));
				$this->assertEquals($phrase->__toString(), array_shift($elements));
			}
		}
	}

	public function testGroupedByOr() {
		$query_expression = $this->parser->parse('sam or (jim and bacon)');
		$phrases = $query_expression->getPhrases();
		$subexpressions = $query_expression->getSubExpressions();

		// Validate the counts
		$this->assertEquals(1, count($phrases));
		$this->assertEquals(1, count($subexpressions));

		// Validate the phrases
		$elements = array('sam');
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), QueryPhrase::MODE_AND);
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}

		// Validate the subphrases
		$elements = array('jim', 'bacon');
		$submodes = array(QueryPhrase::MODE_OR);
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_AND);
		foreach ($subexpressions as $expression) {
			$this->assertEquals(array_shift($submodes), $expression->getMode());
			$this->assertEquals(count($expression->getSubExpressions()), 0);
			$this->assertEquals(count($expression->getPhrases()), 2);
			foreach($expression->getPhrases() as $phrase) {
				$this->assertEquals($phrase->getMode(), array_shift($modes));
				$this->assertEquals($phrase->__toString(), array_shift($elements));
			}
		}
	}

	public function testGroupedByWithQuotes() {
		$query_expression = $this->parser->parse('sam (jim "has bacon") modified:<"2010-04-07"');
		$phrases = $query_expression->getPhrases();
		$subexpressions = $query_expression->getSubExpressions();

		// Validate the counts
		$this->assertEquals(count($phrases), 2);
		$this->assertEquals(count($subexpressions), 1);

		// Validate the phrases
		$elements = array('sam', 'modified:<2010-04-07');
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), QueryPhrase::MODE_AND);
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}

		// Validate the subphrases
		$elements = array('jim', 'has bacon');
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_AND);
		foreach ($subexpressions as $expression) {
			$this->assertEquals(count($expression->getSubExpressions()), 0);
			$this->assertEquals(count($expression->getPhrases()), 2);
			foreach($expression->getPhrases() as $phrase) {
				$this->assertEquals($phrase->getMode(), array_shift($modes));
				$this->assertEquals($phrase->__toString(), array_shift($elements));
			}
		}
	}

	public function testFalseGroupingWithQuotes() {
		$query_expression = $this->parser->parse('sam (jim "(has bacon)") modified:<"2010-04-07"');
		$phrases = $query_expression->getPhrases();
		$subexpressions = $query_expression->getSubExpressions();

		// Validate the counts
		$this->assertEquals(count($phrases), 2);
		$this->assertEquals(count($subexpressions), 1);

		// Validate the phrases
		$elements = array('sam', 'modified:<2010-04-07');
		foreach($phrases as $phrase) {
			$this->assertEquals($phrase->getMode(), QueryPhrase::MODE_AND);
			$this->assertEquals($phrase->__toString(), array_shift($elements));
		}

		// Validate the subphrases
		$elements = array('jim', '(has bacon)');
		$modes = array(QueryPhrase::MODE_AND, QueryPhrase::MODE_AND);
		foreach ($subexpressions as $expression) {
			$this->assertEquals(count($expression->getSubExpressions()), 0);
			$this->assertEquals(count($expression->getPhrases()), 2);
			foreach($expression->getPhrases() as $phrase) {
				$this->assertEquals($phrase->getMode(), array_shift($modes));
				$this->assertEquals($phrase->__toString(), array_shift($elements));
			}
		}
	}
}

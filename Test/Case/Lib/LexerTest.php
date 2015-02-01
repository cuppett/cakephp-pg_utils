<?php
App::uses('QueryLexer', 'PgUtils.Lib');

class LexerTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->lexer = new QueryLexer();
	}

	public function testSimpleWords() {
		$tokens = $this->lexer->getTokens('sam had a green gun');
		$this->assertEquals(5, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], 'had');
 		$this->assertEquals($tokens[2], 'a');
		$this->assertEquals($tokens[3], 'green');
		$this->assertEquals($tokens[4], 'gun');
	}

	public function testQuoteWords() {
		$tokens = $this->lexer->getTokens('sam "had a" "green gun"');
		$this->assertEquals(3, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], 'had a');
		$this->assertEquals($tokens[2], 'green gun');
	}

	public function testMinus() {
		$tokens = $this->lexer->getTokens('sam "had a" -"green gun"');
		$this->assertEquals(3, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], 'had a');
		$this->assertEquals($tokens[2], '-green gun');
	}

	public function testLessThan() {
		$tokens = $this->lexer->getTokens('sam "had a" modified:<"2010-04-07"');
		$this->assertEquals(3, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], 'had a');
		$this->assertEquals($tokens[2], 'modified:<2010-04-07');
	}

	public function testGroupedBy() {
		$tokens = $this->lexer->getTokens('sam (jim or bacon) modified:<"2010-04-07"');
		$this->assertEquals(6, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], '(');
		$this->assertEquals($tokens[2], 'jim');
		$this->assertEquals($tokens[3], 'or bacon');
		$this->assertEquals($tokens[4], ')');
		$this->assertEquals($tokens[5], 'modified:<2010-04-07');
	}

	public function testGroupedByOr() {
		$tokens = $this->lexer->getTokens('sam or (jim and bacon)');
		$this->assertEquals(5, count($tokens));
		$this->assertEquals('sam', $tokens[0]);
		$this->assertEquals('or (', $tokens[1]);
		$this->assertEquals('jim', $tokens[2]);
		$this->assertEquals('and bacon', $tokens[3]);
		$this->assertEquals(')', $tokens[4]);
	}

	public function testMinusOr() {
		$tokens = $this->lexer->getTokens('sam "had a" or -"green gun"');
		$this->assertEquals(3, count($tokens));
		$this->assertEquals('sam', $tokens[0]);
		$this->assertEquals('had a', $tokens[1]);
		$this->assertEquals('or -green gun', $tokens[2]);
	}

	public function testGroupedByWithQuotes() {
		$tokens = $this->lexer->getTokens('sam (jim "has bacon") modified:<"2010-04-07"');
		$this->assertEquals(6, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], '(');
		$this->assertEquals($tokens[2], 'jim');
		$this->assertEquals($tokens[3], 'has bacon');
		$this->assertEquals($tokens[4], ')');
		$this->assertEquals($tokens[5], 'modified:<2010-04-07');
	}

	public function testNegativeGroupedByWithQuotes() {
		$tokens = $this->lexer->getTokens('sam -(jim "has bacon")');
		$this->assertEquals('sam', $tokens[0]);
		$this->assertEquals('-(', $tokens[1]);
		$this->assertEquals('jim', $tokens[2]);
		$this->assertEquals('has bacon', $tokens[3]);
		$this->assertEquals( ')', $tokens[4]);
		$this->assertEquals(5, count($tokens));
	}

	public function testFalseGroupingWithQuotes() {
		$tokens = $this->lexer->getTokens('sam (jim "(has bacon)") modified:<"2010-04-07"');
		$this->assertEquals(6, count($tokens));
		$this->assertEquals($tokens[0], 'sam');
		$this->assertEquals($tokens[1], '(');
		$this->assertEquals($tokens[2], 'jim');
		$this->assertEquals($tokens[3], '(has bacon)');
		$this->assertEquals($tokens[4], ')');
		$this->assertEquals($tokens[5], 'modified:<2010-04-07');
	}
}

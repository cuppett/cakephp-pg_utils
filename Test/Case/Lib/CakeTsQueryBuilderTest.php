<?php
App::uses('CakeTsQueryBuilder', 'PgUtils.Lib');

class CakeTsQueryBuilderTestCase extends CakeTestCase {

	public function testSimpleWords() {
		$query = new CakeTsQueryBuilder('sam had a green gun');
		$this->assertEquals("'sam' & 'had' & 'a' & 'green' & 'gun'", $query->getQuery());
	}

	public function testPrimaryObjectQuery() {
		$query = new CakeTsQueryBuilder('test1 or test2 -test3 name:another',
			array(
				'name' => 'A',
				'code' => 'A',
				'tag' => 'B',
				'tags' => 'B',
				'description' => 'D'
			)
		);
		$this->assertEquals("'test1' | 'test2' & !'test3' & 'another':A", $query->getQuery());
	}

	public function testQuoteWords() {
		$query = new CakeTsQueryBuilder('sam "had a" "green gun"');
		$this->assertEquals("'sam' & 'had a' & 'green gun'", $query->getQuery());
	}

	public function testMinus() {
		$query = new CakeTsQueryBuilder('sam "had a" -"green gun"');
		$this->assertEquals("'sam' & 'had a' & !'green gun'", $query->getQuery());
	}

	public function testMinusSpace() {
		$query = new CakeTsQueryBuilder('sam "had a" - "green gun"');
		$this->assertEquals("'sam' & 'had a' & 'green gun'", $query->getQuery());
	}

	public function testMinusOr() {
		$query = new CakeTsQueryBuilder('sam "had a" or -"green gun"');
		$this->assertEquals("'sam' & 'had a' | !'green gun'", $query->getQuery());
	}

	public function testGroupedBy() {
		$query = new CakeTsQueryBuilder('sam or (jim or bacon)');
		$this->assertEquals("'sam' | ('jim' | 'bacon')", $query->getQuery());
	}

	public function testGroupedByWithQuotes() {
		$query = new CakeTsQueryBuilder('sam (jim or -"has bacon")');
		$this->assertEquals("'sam' & ('jim' | !'has bacon')", $query->getQuery());
	}

	public function testNegativeGroupedByWithQuotes() {
		$query = new CakeTsQueryBuilder('sam or -(jim or -"has bacon")');
		$this->assertEquals("'sam' | !('jim' | !'has bacon')", $query->getQuery());
	}
}

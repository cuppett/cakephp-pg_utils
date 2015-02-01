<?php
/**
 * Inspired by http://stackoverflow.com/questions/207817/converting-a-google-search-query-to-a-postgresql-tsquery
 * @author Peter Bailey
 * @author Stephen Cuppett
 */

app::uses('ILexer', 'PgUtils.Lib');

interface IParser
{
	public function __construct( ILexer $lexer );
	public function parse( $input );
}

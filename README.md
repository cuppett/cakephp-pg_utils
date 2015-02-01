# CakePHP PostgreSQL Utilities

PgUtils provides a small set of behaviors useful for working with Postgres datasources.

## Requirements

The master branch has the following requirements:

* CakePHP 2.2.0 or greater.
* PHP 5.3.0 or greater.

## Features

* Search behavior - Performing text searches on models. Allows sophisticated full text searches to be
performed. Also includes a simplified query parser.
* JSON behavior - Retrieving and storing json datatypes. Values are exploded into associative arrays upon
retrieval and collapsed back down on save.
* Array behavior - Retrieving and storing array datatypes. Values are exploded into arrays upon retrieval
and collapsed back down on save
* Interval behavior - Retrieving and storing interval datatypes. PHP intervals are converted to ISO8601 format
and stored into this special PostgreSQL datatype column.

## Installation

* Clone/Copy the files in this directory into `app/Plugin/PgUtils`
* Ensure the plugin is loaded in `app/Config/bootstrap.php` by calling `CakePlugin::load('PgUtils');`
* To use the IntervalBehavior, you must ensure values are stored/retrieved using iso8601 via your database connection.

```php
	public $default = array(
		'datasource' => 'Database/Postgres',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'user',
		'password' => 'password',
		'database' => 'database_name',
		'prefix' => '',
		'encoding' => 'utf8',
		'settings' => array(
			'intervalstyle' => 'iso_8601'
		)		
	);
```

* Full text search requires a ts_vector column to be created and maintained. The simplest way
is with a trigger.  

### Using Composer

Ensure `require` is present in `composer.json`. This will install the plugin into `Plugin/AwsUtils`:

```
{
    "require": {
        "cuppett/cakephp-pg_utils": "1.0.*"
    },
    "extra":
	{
	    "installer-paths":
	    {
	        "app/Plugin/PgUtils": ["cuppett/cakephp-pg_utils"]
	    }
	}       
}
```

## Examples

### Integrating the search column into your database.

The search behavior requires a ts_vector column to be defined. The column must be maintained separately
of the behavior. Here is an example set of SQL to add & update the column when the database
is updated:

```sql
CREATE TABLE primary_objects (
  id UUID NOT NULL default uuid_generate_v4(),
  "name" varchar(255) not null,
  description text,
  created timestamp with time zone not null default CURRENT_TIMESTAMP,
  modified timestamp with time zone not null default CURRENT_TIMESTAMP,
  searchable_text tsvector
);

CREATE OR REPLACE FUNCTION updateVector() RETURNS trigger AS $$
BEGIN
	NEW.searchable_text = 
		setweight(to_tsvector('pg_catalog.english', coalesce(NEW."name", '')), 'A') ||
		setweight(to_tsvector('pg_catalog.english', coalesce(NEW.description, '')), 'D');

	RETURN NEW;	
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER indexObjects
	BEFORE INSERT OR UPDATE OF "name", description ON primary_objects
	FOR EACH ROW
	EXECUTE PROCEDURE updateVector();
```

You can add other columns & weights to the search; you can add a gist or gin index to the table/column to
speed up the results; etc.

### Using search from your application

In your Model class:

```php
$this->Behaviors->load(
    'PgUtils.Search', array(
        'column' => 'searchable_text',
        'weights' => array(
            'name' => 'A',
            'description' => 'D'
        )
    )
);
```

From your Controller:

```php
$results = $this->PrimaryObject->search($this->request->data['Search']['query'],
    array(
        'limit' => 25,
        'headline' => array('name', 'description'),
        'fields' => array('id', 'name', 'description', 'modified')
     )
);
```

Then you can display the results in your view like normal.

Potential searches include freeform text such as "cat and dog", "cat or dog", etc. The current set of 
operators are "and", "or", and "-" to exclude certain terms.

You can also prefix the term with a field name such as "name:cat". From the above setup examples, this 
will change the query such that only the "A" category will be searched for the term "cat". 

Please see the topic [Controlling Text Search][text_search] in the PostgreSQL documentation for more
information on these topics. I've tried to expose the most commonly desired knobs for my use cases, but
I'm open to other suggestions.

## Reporting issues

If you have a problem with PgUtils please open an issue on [GitHub][issues].

[text_search]: http://www.postgresql.org/docs/9.4/static/textsearch-controls.html
[issues]: https://github.com/cuppett/cakephp-pg_utils/issues

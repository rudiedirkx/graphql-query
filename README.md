GraphQL query builder
====

Build a query:

	$query = new Query;
	$query->field('scope'); // Returns the Query
	$query->child('viewer'); // Returns the child Container
	$query->viewer->fields('id', 'name'); // Returns the Container
	$query->viewer->child('repos');
	$query->viewer->repos
		->attribute('public', true) // Returns the Container
		->attribute('limit', 10)
		->attribute('order', ['field' => Query::enum('STARS'), 'direction' => Query::enum('DESC')]);
	$query->viewer->repos->fields('id', 'path');

Render it:

	$string = $query->build();

Results in:

	query {
	  scope
	  viewer {
	    id
	    name
	    repos(public: true, limit: 10, order: {field: STARS, direction: DESC}) {
	      id
	      path
	    }
	  }
	}

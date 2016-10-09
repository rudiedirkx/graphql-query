GraphQL query builder
====

Build a query:

	$query = new Query('TestQueryWithEverything');
	$query->defineFragment('userStuff', 'User');
	$query->userStuff->fields('id', 'name', 'path');
	$query->field('scope');
	$query->child('viewer');
	$query->viewer->field('...userStuff');
	$query->viewer->child('repos');
	$query->viewer->repos
		->attribute('public', true)
		->attribute('limit', 10)
		->attribute('order', ['field' => Query::enum('STARS'), 'direction' => Query::enum('DESC')]);
	$query->viewer->repos->fields('id', 'path');
	$query->viewer->repos->fragment('PublicRepo');
	$query->viewer->repos->PublicRepo->field('stars');
	$query->viewer->repos->fragment('PrivateRepo');
	$query->viewer->repos->PrivateRepo->fields('status', 'permissions');
	$query->viewer->repos->PrivateRepo->child('members');
	$query->viewer->repos->PrivateRepo->members->field('...userStuff');

Render it:

	$string = $query->build();

Results in:

	query TestQueryWithEverything {
	  scope
	  viewer {
	    ...userStuff
	    repos(public: true, limit: 10, order: {field: STARS, direction: DESC}) {
	      ... on PublicRepo {
	        stars
	      }
	      ... on PrivateRepo {
	        status
	        permissions
	        members {
	          ...userStuff
	        }
	      }
	      id
	      path
	    }
	  }
	}
	
	fragment userStuff on User {
	  id
	  name
	  path
	}

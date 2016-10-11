GraphQL query builder
====

Build a query:

	$query = new Query('TestQueryWithEverything');
	$query->defineFragment('userStuff', 'User');
	$query->userStuff->fields('id', 'name', 'path');
	$query->fields('scope', 'friends', 'viewer');
	$query->friends->attribute('names', ['marc', 'jeff']);
	$query->friends->fields('id', 'name', 'picture');
	$query->friends->picture->attribute('size', 50);
	$query->viewer->fields('...userStuff', 'repos');
	$query->viewer->repos
		->attribute('public', true)
		->attribute('limit', 10)
		->attribute('order', ['field' => Query::enum('STARS'), 'direction' => Query::enum('DESC')]);
	$query->viewer->repos->fields('id', 'path');
	$query->viewer->repos->fragment('PublicRepo')->fields('stars');
	$query->viewer->repos->fragment('PrivateRepo')->fields('status', 'permissions', 'members');
	$query->viewer->repos->PrivateRepo->members->fields('...userStuff');

Render it:

	$string = $query->build();

Results in:

	query TestQueryWithEverything {
	  scope
	  friends(names: ["marc","jeff"]) {
	    id
	    name
	    picture(size: 50)
	  }
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

GraphQL query builder
====

Build a query:

	$query = Query::query('TestQueryWithEverything');
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

Mutations
----

Since Mutations are practically the same as Queries, it has the same exact semantics:

	$query = Query::mutation();
	$query->field('moveProjectCard')->attribute('input', ['cardId' => 123, 'columnId' => 456]);
	$query->moveProjectCard->fields('clientMutationId');

makes:

	mutation {
	  moveProjectCard (input: {cardId: 123, columnId: 456}) {
	    clientMutationId
	  }
	}

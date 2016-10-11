<?php

use rdx\graphqlquery\Query;

require 'autoload.php';

header('Content-type: text/plain; charset=utf-8');

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

echo "====\n";
echo $query->build();
echo "====\n";

echo "\n\n";

print_r($query);

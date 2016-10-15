<?php

use rdx\graphqlquery\Query;

require 'autoload.php';

header('Content-type: text/plain; charset=utf-8');

$query = new Query('TestQueryWithEverything', ['smallPicSize' => 'int']);
$query->defineFragment('userStuff', 'User');
$query->userStuff->fields('id', 'name', 'path');
$query->fields('scope', 'friends', 'viewer');
$query->friends->attribute('names', ['marc', 'jeff']);
$query->friends->fields(['id', 'name', 'smallpic' => 'picture', 'picture']);
$query->friends->smallpic->attribute('size', Query::variable('smallPicSize', 'int'));
$query->friends->picture->alias('bigpic')->attribute('size', 50); // Alias 'picture' to 'bigpic', and add attribute
$query->viewer->fields('...userStuff', 'repos');
$query->viewer->repos
	->attribute('public', true)
	->attribute('limit', 10)
	->attribute('order', ['field' => Query::enum('STARS'), 'direction' => Query::enum('DESC')]);
$query->viewer->repos->fields('id', 'path');
$query->viewer->repos->fragment('PublicRepo')->field('stars', 'popularity');
$query->viewer->repos->fragment('PrivateRepo')->fields(['status', 'popularity' => 'permissions', 'members']);
$query->viewer->repos->PrivateRepo->members->fields('...userStuff');

echo "====\n";
echo $query->build();
echo "====\n";

echo "\n\n";

print_r($query);

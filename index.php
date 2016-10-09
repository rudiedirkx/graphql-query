<?php

class Enum {

	protected $option;

	public function __construct($option) {
		$this->option = $option;
	}

	public function __toString() {
		return $this->option;
	}

}

class Container {

	protected $fragmentType;
	protected $attributes = [];
	protected $fields = [];
	protected $fragments = [];
	protected $children = [];

	public function attribute($name, $value) {
		$this->attributes[$name] = $value;
		return $this;
	}

	public function attributes($attributes) {
		foreach ($attributes as $name => $value) {
			$this->attribute($name, $value);
		}

		return $this;
	}

	public function child($name) {
		$child = new Container();
		$this->children[$name] = $child;
		return $child;
	}

	public function field($name) {
		$this->fields[] = $name;
		return $this;
	}

	public function fields(...$names) {
		array_map([$this, 'field'], $names);
		return $this;
	}

	public function fragment($type) {
		$child = new Container();
		$this->fragments[$type] = $child;
		return $child;
	}

	public function render($depth) {
		$indent = $this->indent($depth);
		$output = '';

		// Fragments
		foreach ($this->fragments as $type => $child) {
			$output .= $indent . '... on ' . $type . " {\n";
			$output .= $child->render($depth + 1);
			$output .= $indent . "}\n";
		}

		// Fields
		foreach ($this->fields as $name) {
			$output .= $indent . $name . "\n";
		}

		// Children
		foreach ($this->children as $name => $child) {
			$attributes = $child->renderAttributes();
			$output .= $indent . $name . $attributes . " {\n";
			$output .= $child->render($depth + 1);
			$output .= $indent . "}\n";
		}

		return $output;
	}

	protected function renderAttributes() {
		if ($this->attributes) {
			$attributes = $this->renderAttributeValues($this->attributes);
			return '(' . $attributes . ')';
		}

		return '';
	}

	protected function renderAttributeValues($attributes) {
		$components = [];
		foreach ($attributes as $name => $value) {
			$components[] = $name . ': ' . $this->renderAttributeValue($value);
		}

		return implode(', ', $components);
	}

	protected function renderAttributeValue($value) {
		// Enums are unquoted strings
		if ($value instanceof Enum) {
			return (string) $value;
		}

		if (is_array($value)) {
			// JSON arrays are JSON
			if (isset($value[0])) {
				return json_encode($value);
			}

			// Object arrays get another round of recursion
			return '{' . $this->renderAttributeValues($value) . '}';
		}

		// All the rest is scalar
		return json_encode($value);
	}

	protected function indent($depth) {
		return str_repeat('  ', $depth);
	}

	public function __get($name) {
		return $this->children[$name] ?? $this->fragments[$name] ?? null;
	}

}

class Query extends Container {

	protected $name;
	protected $fragmentDefinitions = [];

	public function __construct($name = '') {
		$this->name = $name;
	}

	public function defineFragment($name, $type) {
		$child = new Container();
		$child->fragmentType = $type;
		$this->fragmentDefinitions[$name] = $child;
		return $child;
	}

	public function build() {
		return trim($this->buildQuery() . $this->buildFragmentDefinitions()) . "\n";
	}

	protected function buildQuery() {
		return "query {$this->name} {\n" . $this->render(1) . "}\n\n";
	}

	protected function buildFragmentDefinitions() {
		$output = '';
		foreach ($this->fragmentDefinitions as $name => $child) {
			$type = $child->fragmentType;

			$output .= "fragment $name on $type {\n";
			$output .= $child->render(1);
			$output .= "}\n\n";
		}

		return $output;
	}

	public function __get($name) {
		return $this->fragmentDefinitions[$name] ?? parent::__get($name);
	}

	static public function enum($option) {
		return new Enum($option);
	}

}

header('Content-type: text/plain; charset=utf-8');

$query = new Query('TestQueryWithEverything');
$query->defineFragment('userStuff', 'User');
$query->userStuff->fields('id', 'name', 'path');
$query->field('scope');
$query->child('friends');
$query->friends->attribute('names', ['marc', 'jeff']);
$query->friends->fields('id', 'name');
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

// echo "====\n";
// echo $repos->render(2) . "\n";
// echo "====\n";

// echo "\n\n";

echo "====\n";
echo $query->build() . "\n";
echo "====\n";

echo "\n\n";

print_r($query);

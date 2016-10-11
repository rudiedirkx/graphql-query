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

	protected $attributes = [];

	// Fields and Fragments can have the same name ('user' field and 'user' type), so they
	// can't be in the same list.
	protected $fields = [];
	protected $fragments = [];

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

	public function field($name) {
		return $this->fields[$name] = new Container;
	}

	public function fields(...$names) {
		foreach ($names as $name) {
			$this->field($name);
		}

		return $this;
	}

	public function fragment($type) {
		return $this->fragments[$type] = new FragmentContainer($type);
	}

	public function fragments(...$types) {
		foreach ($types as $type) {
			$this->fragment($type);
		}

		return $this;
	}

	public function render($depth) {
		$indent = $this->indent($depth);
		$output = '';

		// Fragments
		foreach ($this->fragments as $index => $container) {
			$output .= $indent . $container->renderSignature($index) . $container->renderChildren($depth) . "\n";
		}

		// Fields
		foreach ($this->fields as $index => $container) {
			$output .= $indent . $container->renderSignature($index) . $container->renderChildren($depth) . "\n";
		}

		return $output;
	}

	protected function renderSignature($name) {
		return $name . $this->renderAttributes();
	}

	protected function renderChildren($depth) {
		if ($this->fragments || $this->fields) {
			return
				" {\n" .
				$this->render($depth + 1) .
				$this->indent($depth) . "}";
		}

		return '';
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
		return $this->fields[$name] ?? $this->fragments[$name] ?? null;
	}

}

class FragmentContainer extends Container {

	public function __construct($type) {
		$this->type = $type;
	}

	protected function renderSignature($index) {
		return '... on ' . $this->type;
	}

}

class FragmentDefinitionContainer extends Container {

	public function __construct($type) {
		$this->type = $type;
	}

}

class Query extends Container {

	protected $name;
	protected $fragmentDefinitions = [];

	public function __construct($name = '') {
		$this->name = $name;
	}

	public function defineFragment($name, $type) {
		return $this->fragmentDefinitions[$name] = new FragmentDefinitionContainer($type);
	}

	public function build() {
		return trim($this->buildQuery() . $this->buildFragmentDefinitions()) . "\n";
	}

	protected function buildQuery() {
		return "query {$this->name} {\n" . $this->render(1) . "}\n\n";
	}

	protected function buildFragmentDefinitions() {
		$output = '';
		foreach ($this->fragmentDefinitions as $name => $container) {
			$type = $container->type;

			$output .= "fragment $name on $type {\n";
			$output .= $container->render(1);
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

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
	protected $fields = [];
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

	public function render($depth) {
		$indent = $this->indent($depth);
		$output = '';

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

		// Arrays get another round of recursion
		if (is_array($value)) {
			return '{' . $this->renderAttributeValues($value) . '}';
		}

		// All the rest is scalar
		return json_encode($value);
	}

	protected function indent($depth) {
		return str_repeat('  ', $depth);
	}

	public function __get($name) {
		return @$this->children[$name];
	}

}

class Query extends Container {

	protected $root;

	public function __construct($root = 'query') {
		$this->root = $root;
	}

	public function build() {
		return $this->root . " {\n" . $this->render(1) . "}";
	}

	static public function enum($option) {
		return new Enum($option);
	}

}

header('Content-type: text/plain; charset=utf-8');

$query = new Query;
$query->field('scope');
$query->child('viewer');
$query->viewer->fields('id', 'name');
$query->viewer->child('repos');
$query->viewer->repos
	->attribute('public', true)
	->attribute('limit', 10)
	->attribute('order', ['field' => Query::enum('STARS'), 'direction' => Query::enum('DESC')]);
$query->viewer->repos->fields('id', 'path');

// echo "====\n";
// echo $repos->render(2) . "\n";
// echo "====\n";

// echo "\n\n";

echo "====\n";
echo $query->build() . "\n";
echo "====\n";

echo "\n\n";

print_r($query);

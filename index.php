<?php

class Container {

	public $attributes = [];
	public $fields = [];
	public $children = [];

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
		$output = '';

		// Fields
		foreach ($this->fields as $name) {
			$output .= $this->indent($depth) . $name . "\n";
		}

		// Children
		foreach ($this->children as $name => $child) {
			$attributes = $child->renderAttributes();
			$output .= $this->indent($depth) . $name . $attributes . " {\n";
			$output .= $child->render($depth + 1);
			$output .= $this->indent($depth) . "}\n";
		}

		return $output;
	}

	protected function renderAttributes() {
		$attributes = [];
		foreach ($this->attributes as $name => $value) {
			$attributes[] = $name . ': ' . $this->renderAttributeValue($value);
		}

		return $attributes ? '(' . implode(', ', $attributes) . ')' : '';
	}

	protected function renderAttributeValue($value) {
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

	public function build() {
		return "query {\n" . $this->render(1) . "}";
	}

}

header('Content-type: text/plain; charset=utf-8');

$query = new Query;
$query->field('scope');
$query->child('viewer');
$query->viewer->fields('id', 'name');
$query->viewer->child('repos');
$query->viewer->repos->attribute('public', true)->attribute('limit', 10)->attribute('order', ['field' => 'stars', 'desc' => true]);
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

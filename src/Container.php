<?php

namespace rdx\graphqlquery;

use rdx\graphqlquery\Enum;
use rdx\graphqlquery\FragmentContainer;

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

<?php

namespace rdx\graphqlquery;

use rdx\graphqlquery\Attribute;
use rdx\graphqlquery\Enum;
use rdx\graphqlquery\FragmentContainer;

class Container {

	protected $field = '';
	protected $alias = '';
	protected $attributes = [];

	// Fields and Fragments can have the same name ('user' field and 'user' type), so they
	// can't be in the same list.
	protected $fields = [];
	protected $fragments = [];

	public function __construct($field, $alias = '') {
		$this->field = $field;
		$this->alias = $alias;
	}

	public function id() {
		return $this->alias ?: $this->field;
	}

	public function alias($alias) {
		$this->alias = $alias;
		return $this;
	}

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

	public function field($field, $alias = '') {
		return $this->fields[] = new Container($field, $alias);
	}

	public function fields(...$names) {
		if (is_array($names[0])) {
			$names = $names[0];
		}

		foreach ($names as $alias => $name) {
			is_int($alias) ? $this->field($name) : $this->field($name, $alias);
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
		foreach ($this->fragments as $container) {
			$output .= $indent . $container->renderSignature() . $container->renderChildren($depth) . "\n";
		}

		// Fields
		foreach ($this->fields as $container) {
			$output .= $indent . $container->renderSignature() . $container->renderChildren($depth) . "\n";
		}

		return $output;
	}

	protected function renderSignature() {
		$name = $this->alias ? "{$this->alias}: {$this->field}" : $this->field;
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
			return " ($attributes)";
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

	protected function renderAttributeArrayValues($attributes) {
		$components = [];
		foreach ($attributes as $value) {
			$components[] = $this->renderAttributeValue($value);
		}

		return implode(', ', $components);
	}


	protected function isAssociative(array $array) {
		if (array() === $array) return false;
		return array_keys($array) !== range(0, count($array) - 1);
	}

	protected function renderAttributeValue($value) {
		// Enums are unquoted strings
		if ($value instanceof Attribute) {
			return (string) $value;
		}

		if (is_array($value)) {
			if (!$this->isAssociative($value)) {
				return '[' . $this->renderAttributeArrayValues($value) . ']';
			}

			// Object arrays get another round of recursion
			return '{' . $this->renderAttributeValues($value) . '}';
		}

		// All the rest is scalar
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	protected function indent($depth) {
		return str_repeat('  ', $depth);
	}

	public function get($name) {
		return $this->getField($name) ?: $this->getFragment($name) ?: null;
	}

	public function getField($name) {
		foreach ($this->fields as $field) {
			if ($field->id() == $name) {
				return $field;
			}
		}
	}

	public function getFragment($name) {
		return $this->fragments[$name] ?? null;
	}

	/**
	 * @param $name
	 * @return Container
	 */
	public function __get($name) {
		return $this->get($name);
	}

}

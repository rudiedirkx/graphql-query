<?php

namespace rdx\graphqlquery;

use rdx\graphqlquery\Enum;
use rdx\graphqlquery\FragmentDefinitionContainer;
use rdx\graphqlquery\Variable;

class Query extends Container {

	protected $name;
	protected $variables;
	protected $fragmentDefinitions = [];
	protected $type;

	const TYPE_QUERY = 'query';
	const TYPE_MUTATION = 'mutation';

	public function __construct($name = null, $variables = [], $type = self::TYPE_QUERY) {
		$this->type = $type;
		$this->name = $name;
		$this->variables = $variables;
	}

	static function query($name = null, $variables = []) {
		return new static($name, $variables, $type = self::TYPE_QUERY);
	}

	static function mutation($name = null, $variables = []) {
		return new static($name, $variables, $type = self::TYPE_MUTATION);
	}

	public function defineFragment($name, $type) {
		return $this->fragmentDefinitions[$name] = new FragmentDefinitionContainer($name, $type);
	}

	public function build() {
		return trim($this->buildQuery() . $this->buildFragmentDefinitions()) . "\n";
	}

	protected function buildQuery() {
		$signature = $this->renderSignature();
		return "$this->type $signature{\n".$this->render(1)."}\n\n";
	}

	public function setType($type) {
		$this->type = $type;
	}

	protected function renderSignature() {
		$name = $this->getName();
		$variables = $this->renderVariables();
		return "$name$variables";
	}

	protected function renderVariables() {
		if ($this->variables) {
			$variables = [];
			foreach ($this->variables as $name => $type) {
				$variables[] = '$' . $name . ': ' . ucfirst($type);
			}

			return '(' . implode(', ', $variables) . ') ';
		}

		return '';
	}

	protected function getName() {
		if (!$this->name && $this->variables) {
			$this->name = 'CustomQuery';
		}

		return $this->name ? "{$this->name} " : '';
	}

	protected function buildFragmentDefinitions() {
		$output = '';
		foreach ($this->fragmentDefinitions as $name => $container) {
			$type = $container->getType();

			$output .= "fragment $name on $type {\n";
			$output .= $container->render(1);
			$output .= "}\n\n";
		}

		return $output;
	}

	/**
	 * @param $name
	 * @return Container
	 */
	public function __get($name) {
		return $this->getFragmentDefinition($name) ?: parent::get($name);
	}

	/**
	 * @param $name
	 * @return FragmentDefinitionContainer
	 */
	public function getFragmentDefinition($name) {
		return $this->fragmentDefinitions[$name] ?? null;
	}

	static public function enum($name) {
		return new Enum($name);
	}

	static public function variable($name) {
		return new Variable($name);
	}

}

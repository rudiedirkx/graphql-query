<?php

namespace rdx\graphqlquery;

use rdx\graphqlquery\Enum;
use rdx\graphqlquery\FragmentDefinitionContainer;

class Query extends Container {

	protected $name;
	protected $fragmentDefinitions = [];

	public function __construct($name = '') {
		$this->name = $name;
	}

	public function defineFragment($name, $type) {
		return $this->fragmentDefinitions[$name] = new FragmentDefinitionContainer($name, $type);
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

<?php

namespace rdx\graphqlquery;

abstract class Attribute {

	protected $name;

	public function __construct($name) {
		$this->name = $name;
	}

	public function __toString() {
		return $this->name;
	}

}

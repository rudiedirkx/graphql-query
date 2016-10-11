<?php

namespace rdx\graphqlquery;

class Enum {

	protected $option;

	public function __construct($option) {
		$this->option = $option;
	}

	public function __toString() {
		return $this->option;
	}

}

<?php

namespace rdx\graphqlquery;

use rdx\graphqlquery\Attribute;

class Variable extends Attribute {

	public function __toString() {
		return '$' . $this->name;
	}

}

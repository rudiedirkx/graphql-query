<?php

namespace rdx\graphqlquery;

class FragmentDefinitionContainer extends Container {

	public function __construct($type) {
		$this->type = $type;
	}

}

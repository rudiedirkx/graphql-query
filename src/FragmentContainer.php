<?php

namespace rdx\graphqlquery;

class FragmentContainer extends Container {

	protected $type = '';

	public function __construct($type) {
		$this->type = $type;
	}

	protected function renderSignature() {
		return '... on ' . $this->type;
	}

}

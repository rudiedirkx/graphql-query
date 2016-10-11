<?php

namespace rdx\graphqlquery;

class FragmentContainer extends Container {

	public function __construct($type) {
		$this->type = $type;
	}

	protected function renderSignature($index) {
		return '... on ' . $this->type;
	}

}

<?php

namespace rdx\graphqlquery;

class FragmentDefinitionContainer extends Container {

	protected $type = '';

	public function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
	}

    /**
     * Returns fragment type
     * @return string
     */
	public function getType() {
	    return $this->type;
    }

}

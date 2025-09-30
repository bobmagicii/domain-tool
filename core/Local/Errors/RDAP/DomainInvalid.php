<?php ##########################################################################
################################################################################

namespace Local\Errors\RDAP;

use Exception;

################################################################################
################################################################################

class DomainInvalid
extends Exception {

	public function
	__Construct(string $Domain) {

		$this->message = sprintf('%s domain is invalid', $Domain);

		return;
	}

};

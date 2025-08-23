<?php ##########################################################################
################################################################################

namespace Local\Errors\RDAP;

use Exception;

################################################################################
################################################################################

class CommandFailed
extends Exception {

	public function
	__Construct(string $Message='RDAP command returned a failure') {

		$this->message = $Message;

		return;
	}

};

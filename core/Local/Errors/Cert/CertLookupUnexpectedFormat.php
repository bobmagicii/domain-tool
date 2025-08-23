<?php

namespace Local\Errors\Cert;

use Exception;

class CertLookupUnexpectedFormat
extends Exception {

	public function
	__Construct(string $Domain, string $Why='R.I.P.') {

		parent::__Construct(sprintf(
			'ssl cert lookup for %s (%s) returned unexpected format',
			$Domain,
			$Why
		));

		return;
	}

}

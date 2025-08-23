<?php ##########################################################################
################################################################################

namespace Local\Formats\CertSSL;

use Local;
use Nether\Common;

################################################################################
################################################################################

class Domain
extends Common\Prototype {

	public string
	$Domain;

	public ?Common\Date
	$DateStart = NULL;

	public ?Common\Date
	$DateExpire = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromWithTime(string $Domain, int $TimeStart, int $TimeExpire):
	static {

		$Output = new static([
			'Domain'     => $Domain,
			'DateStart'  => Common\Date::FromTime($TimeStart),
			'DateExpire' => Common\Date::FromTime($TimeExpire)
		]);

		return $Output;
	}

	static public function
	FromWithDatestamp(string $Domain, string $DateStart, string $DateExpire):
	static {

		$Output = new static([
			'Domain'     => $Domain,
			'DateStart'  => Common\Date::FromDateString($DateStart),
			'DateExpire' => Common\Date::FromDateString($DateExpire)
		]);

		return $Output;
	}

	static public function
	FromNull(string $Domain) {

		$Output = new static([
			'Domain' => $Domain
		]);

		return $Output;
	}

};

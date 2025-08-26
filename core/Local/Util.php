<?php ##########################################################################
################################################################################

namespace Local;

use Nether\Common;

################################################################################
################################################################################

class Util {

	#[Common\Meta\Date('2025-08-23')]
	static public function
	StripSubdomains(string $Domain, int $Keep=2):
	string {

		// subdomain(.[domain])(.[tld])

		$Group = '\.([^\.]+?)';
		$Pattern = str_repeat($Group, $Keep);
		$Stripped = $Domain;

		////////

		if(substr_count($Domain, '.') !== 1)
		$Stripped = preg_replace(
			sprintf('/(.+?)%s$/', $Pattern),
			'\\2.\\3',
			$Domain
		);

		////////

		return $Stripped;
	}

	#[Common\Meta\Date('2025-08-25')]
	static public function
	Word(string $Input):
	string {

		// in the future, this would translate the words.

		return $Input;
	}

};

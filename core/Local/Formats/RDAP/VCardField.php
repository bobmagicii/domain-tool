<?php ##########################################################################
################################################################################

namespace Local\Formats\RDAP;

use Local;
use Nether\Common;

################################################################################
################################################################################

class VCardField
extends Common\Prototype {

	const
	KeyVersion       = 'version',
	KeyFormattedName = 'fn',
	KeyOrganization  = 'org';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public string
	$Name;

	public string
	$Value;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		$this->DigestDataArray($Args->Input);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	DigestDataArray(array $Input):
	static {

		list($Name, $Args, $Type, $Val) = $Input;

		$this->Name = strtolower($Name);

		$this->Value = match(TRUE) {
			(is_string($Val))
			=> $Val,

			default
			=> serialize($Val)
		};

		return $this;
	}

};

<?php ##########################################################################
################################################################################

namespace Local\Formats\RDAP;

use Local;
use Nether\Common;

################################################################################
################################################################################

class DomainEvent
extends Common\Prototype {

	const
	KeyRegister = 'registration',
	KeyExpire   = 'expiration',
	KeyUpdate   = 'last changed';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\PropertyOrigin('eventAction')]
	public string
	$Name;

	#[Common\Meta\PropertyOrigin('eventDate')]
	public string|Common\Date
	$Date;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		$this->DigestName($this->Name);
		$this->DigestDate($this->Date);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	DigestName(string $Input):
	static {

		$this->Name = strtolower($Input);

		return $this;
	}

	public function
	DigestDate(string $Input):
	static {

		$this->Date = Common\Date::FromDateString($this->Date);

		return $this;
	}

};

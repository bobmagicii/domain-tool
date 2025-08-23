<?php ##########################################################################
################################################################################

namespace Local\Formats\RDAP;

use Local;
use Nether\Common;

################################################################################
################################################################################

class DomainEntity
extends Common\Prototype {

	const
	RoleRegistrar = 'registrar';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\PropertyOrigin('roles')]
	public array|Common\Datastore
	$Roles;

	#[Common\Meta\PropertyOrigin('vcardArray')]
	public array|VCard
	$VCard;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		$this->DigestRoles($this->Roles);
		$this->DigestVCards($this->VCard);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	DigestRoles(array $Roles):
	static {

		$Data = Common\Datastore::FromArray($Roles);

		$this->Roles = $Data;

		return $this;
	}

	public function
	DigestVCards(array $VCard):
	static {

		if(count($VCard) === 2) {
			if($VCard[0] === 'vcard')
			$this->VCard = new VCard($VCard[1]);
		}

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetNameFull():
	?string {

		return $this->VCard->NameFull;
	}

};

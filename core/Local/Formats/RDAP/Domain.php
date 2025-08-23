<?php ##########################################################################
################################################################################

namespace Local\Formats\RDAP;

use Local;
use Nether\Common;

################################################################################
################################################################################

class Domain
extends Common\Prototype {

	#[Common\Meta\PropertyOrigin('ldhName')]
	public string
	$Domain;

	#[Common\Meta\PropertyOrigin('events')]
	public array|Common\Datastore
	$Events = [];

	#[Common\Meta\PropertyOrigin('entities')]
	public array|Common\Datastore
	$Entities = [];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		$this->DigestDomain($this->Domain);
		$this->DigestEvents($this->Events);
		$this->DigestEntities($this->Entities);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	DigestDomain(string $Input):
	static {

		$this->Domain = strtolower($Input);

		return $this;
	}

	public function
	DigestEvents(array $Input):
	static {

		$Data = Common\Datastore::FromArray($Input);
		$Data->Remap(fn(array $DE)=> new DomainEvent($DE));

		$Events = new Common\Datastore;
		$Data->Each(fn(DomainEvent $Ev)=> $Events[$Ev->Name] = $Ev);

		$this->Events = $Events;

		return $this;
	}

	public function
	DigestEntities(array $Input):
	static {

		$Data = Common\Datastore::FromArray($Input);
		$Data->Remap(fn(array $DE)=> new DomainEntity($DE));

		$this->Entities = $Data;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	FindRegistrarName():
	?string {

		foreach($this->Entities as $Ent) {
			/** @var DomainEntity $Ent */

			if(!$Ent->Roles->HasValue(DomainEntity::RoleRegistrar))
			continue;

			return $Ent->GetNameFull();
		}

		return NULL;
	}

	public function
	FindRegistrationDate():
	?Common\Date {

		if($this->Events->HasKey(DomainEvent::KeyRegister))
		return $this->Events[DomainEvent::KeyRegister]->Date;

		return NULL;
	}

	public function
	FindExpirationDate():
	?Common\Date {

		if($this->Events->HasKey(DomainEvent::KeyExpire))
		return $this->Events[DomainEvent::KeyExpire]->Date;

		return NULL;
	}

	public function
	FindUpdatedDate():
	?Common\Date {

		if($this->Events->HasKey(DomainEvent::KeyUpdate))
		return $this->Events[DomainEvent::KeyUpdate]->Date;

		return NULL;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromJSON(string $JSON):
	static {

		$Data = json_decode($JSON, TRUE);
		$DKey = 'objectClassName';
		$DVal = 'domain';

		////////

		if(!$Data || !is_array($Data))
		throw new Common\Error\RequiredDataMissing('Data', 'object');

		if(!array_key_exists($DKey, $Data))
		throw new Common\Error\FormatInvalidJSON(sprintf('%s not found', $DKey));

		if($Data[$DKey] !== $DVal)
		throw new Common\Error\FormatInvalidJSON(sprintf('%s is not %s', $DKey, $DVal));

		////////

		$Output = new static($Data);

		return $Output;
	}

	static public function
	FromNull(string $Domain):
	static {

		$Output = new static([
			'Domain' => $Domain
		]);

		return $Output;
	}

};

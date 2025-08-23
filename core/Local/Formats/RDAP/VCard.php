<?php ##########################################################################
################################################################################

namespace Local\Formats\RDAP;

use Local;
use Nether\Common;

################################################################################
################################################################################

class VCard
extends Common\Prototype {

	public string
	$Version;

	public ?string
	$NameFull;

	public Common\Datastore
	$Fields;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		$this->DigestDataArray($Args->Input);
		$this->NameFull = $this->FindNameFull();

		return;
	}

	public function
	DigestDataArray(array $Input):
	static {

		$Data = Common\Datastore::FromArray($Input);
		$Data->Remap(fn(array $F)=> new VCardField($F));

		$Fields = new Common\Datastore;
		$Data->Each(fn(VCardField $F)=> $Fields[$F->Name] = $F);

		$this->Fields = $Fields;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	FindNameFull():
	?string {

		if($this->Fields->HasKey(VCardField::KeyFormattedName))
		return $this->Fields[VCardField::KeyFormattedName]->Value;

		if($this->Fields->HasKey(VCardField::KeyOrganization))
		return $this->Fields[VCardField::KeyOrganization]->Value;

		return NULL;
	}

};

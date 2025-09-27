<?php ##########################################################################
################################################################################

namespace Local\DB;

use Local;
use Nether\Common;
use Nether\Database;

################################################################################
################################################################################

#[Database\Meta\TableClass('Domains')]
#[Database\Meta\InsertUpdate]
class Domain
extends Database\Prototype {

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, AutoInc: TRUE)]
	#[Database\Meta\PrimaryKey]
	public int
	$ID;

	#[Database\Meta\TypeChar(Size: 36)]
	public string
	$UUID;

	#[Database\Meta\TypeVarChar]
	public string
	$Domain;

	#[Database\Meta\TypeVarChar]
	public ?string
	$Registrar;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeLogged;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeRegRegister;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeRegExpire;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeRegUpdate;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeCertExpire;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	WasRecentlyLogged(int $TimeWindow=Common\Values::SecPerDay):
	bool {

		$Now = Common\Date::Unixtime();
		$Diff = $Now - $this->TimeLogged;

		////////

		if($Diff < $TimeWindow)
		return TRUE;

		return FALSE;
	}

	public function
	IsRegAboutToExpire(int $TimeWindow=(Common\Values::SecPerDay*3)):
	bool {

		$Now = Common\Date::Unixtime();
		$Diff = $this->TimeRegExpire - $Now;

		if($Diff < 0 || $Diff < $TimeWindow)
		return TRUE;

		return FALSE;
	}

};

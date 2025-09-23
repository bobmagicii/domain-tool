<?php ##########################################################################
################################################################################

namespace Local\DB;

use Local;
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
	public string
	$Registrar;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeLogged;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeRegExpire;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeCertExpire;

};

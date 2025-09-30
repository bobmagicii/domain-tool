<?php ##########################################################################
################################################################################

namespace Local\Tools;

use Local;
use Nether\Common;

################################################################################
################################################################################

class RegistrationInfo
extends Common\Prototype {

	const
	StatusUnregistered    = -2,
	StatusFailure         = -1,
	StatusExpired         = 0,
	StatusOK              = 1,
	StatusExpireSoon      = 2,
	StatusExpireWarning   = 3;

	const
	StatusWords = [
		self::StatusUnregistered   => 'UNREGISTERED',
		self::StatusFailure        => 'ERROR',
		self::StatusExpired        => 'EXPIRED',
		self::StatusOK             => 'OK',
		self::StatusExpireSoon     => 'SOON',
		self::StatusExpireWarning  => 'IMMINENT'
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public string
	$Domain;

	public ?string
	$Registrar = NULL;

	public ?Common\Date
	$DateRegister = NULL;

	public ?Common\Date
	$DateUpdate = NULL;

	public ?Common\Date
	$DateExpire = NULL;

	public bool
	$Cache = FALSE;

	public bool
	$Valid = TRUE;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	IsRegistered():
	bool {

		$Is = (TRUE
			&& ($this->Registrar !== NULL)
			&& ($this->DateRegister !== NULL && $this->DateRegister->GetUnixtime() !== 0)
		);

		return $Is;
	}

	public function
	IsExpired():
	bool {

		$Diff = $this->GetExpireTimeframe();
		$Dist = $Diff->GetTimeDiff();

		if($Dist <= 0)
		return TRUE;

		return FALSE;
	}

	public function
	GetRegistrarName():
	string {

		// in the case of co.uk since it is treated as a tld we were able
		// to get a registrar name but not any other data about it. the
		// other compound tld probably work in a simliar ay.

		if(!$this->Registrar)
		return '';

		return $this->Registrar;
	}

	public function
	GetDateExpire():
	string {

		if(!$this->IsRegistered())
		return 'XXXX-XX-XX';

		return $this->DateExpire->Get();
	}

	public function
	GetDateExpireTimeframe():
	string {

		if(!$this->IsRegistered())
		return 'INVALID';

		$Output = sprintf(
			'%s (%s)',
			$this->DateExpire->Get(),
			$this->GetExpireTimeframe()
		);

		return $Output;
	}

	public function
	GetTimeRegister():
	int {

		if(!$this->DateRegister)
		return 0;

		return $this->DateRegister->GetUnixtime();
	}

	public function
	GetTimeUpdate():
	int {

		if(!$this->DateUpdate)
		return 0;

		return $this->DateUpdate->GetUnixtime();
	}

	public function
	GetTimeExpire():
	int {

		if(!$this->DateExpire)
		return 0;

		return $this->DateExpire->GetUnixtime();
	}

	public function
	GetDomain():
	string {

		return $this->Domain;
	}

	public function
	GetStatusCode():
	int {

		if(!$this->IsRegistered())
		return static::StatusUnregistered;

		if($this->DateExpire->GetUnixtime() === 0)
		return static::StatusFailure;

		$Diff = $this->GetExpireTimeframe();
		$Dist = $Diff->GetTimeDiff();

		////////

		if($Dist <= 0)
		return static::StatusExpired;

		if($Dist <= (Common\Values::SecPerDay * 5))
		return static::StatusExpireWarning;

		if($Dist <= (Common\Values::SecPerDay * 12))
		return static::StatusExpireSoon;

		////////

		return static::StatusOK;
	}

	public function
	GetStatusWord():
	string {

		$Status = $this->GetStatusCode();

		if(isset(static::StatusWords[$Status]))
		return static::StatusWords[$Status];

		return 'UNKNOWN';
	}

	public function
	GetExpireTimeframe():
	?Common\Units\Timeframe {

		if(!$this->IsRegistered())
		return NULL;

		return new Common\Units\Timeframe(
			(new Common\Date)->GetUnixtime(),
			$this->DateExpire->GetUnixtime(),
			Common\Units\Timeframe::FormatShort,
			Precision: 2
		);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FetchViaRDAP(string $Domain):
	static {

		$LookupDomain = Local\Util::StripSubdomains($Domain);
		$LookupDomain = $Domain;
		$Client = NULL;
		$Data = NULL;
		$Output = NULL;

		////////

		try {
			$Client = new Local\Clients\CommandRDAP;
			$Data = $Client->Fetch($LookupDomain);
		}

		catch(Local\Errors\RDAP\CommandFailed $Err) {
			// rdap says unregistered domain
			if(str_contains($Err->GetMessage(), '404'))
			$Data = Local\Formats\RDAP\Domain::FromNull($Domain, TRUE);

			// rdap says this tld doesnt exist or the domain sucks
			elseif(str_contains($Err->GetMessage(), 'No RDAP servers'))
			$Data = Local\Formats\RDAP\Domain::FromNull($Domain, FALSE);

			else
			throw $Err;
		}

		catch(Local\Errors\RDAP\DomainInvalid $Err) {
			$Data = Local\Formats\RDAP\Domain::FromNull($Domain, FALSE);
		}

		$Output = new static([
			'Domain'       => $Data->Domain,
			'Valid'        => $Data->Valid,
			'Registrar'    => $Data->FindRegistrarName() ?: NULL,
			'DateRegister' => $Data->FindRegistrationDate(),
			'DateExpire'   => $Data->FindExpirationDate(),
			'DateUpdate'   => $Data->FindUpdatedDate()
		]);

		return $Output;
	}

	static public function
	FromDB(Local\DB\Domain $Row):
	static {

		$Output = new static([
			'Domain'       => $Row->Domain,
			'Registrar'    => $Row->Registrar ?: NULL,
			'DateRegister' => Common\Date::FromTime($Row->TimeRegRegister),
			'DateExpire'   => Common\Date::FromTime($Row->TimeRegExpire),
			'DateUpdate'   => Common\Date::FromTime($Row->TimeRegUpdate),
			'Cache'        => TRUE
		]);

		return $Output;
	}

};

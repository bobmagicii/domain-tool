<?php

namespace Local\Tools;

use Local;
use Nether\Common;

use Throwable;

class CertInfo
extends Common\Prototype {

	const
	StatusFailure         = -1,
	StatusExpired         = 0,
	StatusOK              = 1,
	StatusExpireSoon      = 2,
	StatusExpireWarning   = 3;

	const
	StatusWords = [
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

	public int
	$TimeStart;

	public int
	$TimeExpire;

	public string
	$Source;

	////////

	public ?Common\Date
	$DateStart = NULL;

	public ?Common\Date
	$DateExpire = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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
	GetDateExpire():
	string {

		if(!$this->DateExpire)
		return 'XXXX-XX-XX';

		return $this->DateExpire->Get();
	}

	public function
	GetDateExpireTimeframe():
	string {

		if(!$this->DateExpire)
		return 'INVALID';

		$Output = sprintf(
			'%s (%s)',
			$this->GetDateExpire(),
			$this->GetExpireTimeframe()
		);

		return $Output;
	}

	public function
	GetTimeStart():
	int {

		if(!$this->DateStart)
		return 0;

		return $this->DateStart->GetUnixtime();
	}

	public function
	GetTimeExpire():
	int {

		if(!$this->DateExpire)
		return 0;

		return $this->DateExpire->GetUnixtime();
	}

	public function
	GetStatusCode():
	int {

		if(!$this->DateExpire)
		return static::StatusFailure;

		$Diff = $this->GetExpireTimeframe();
		$Dist = $Diff->GetTimeDiff();

		////////

		if($Dist <= 0)
		return static::StatusExpired;

		if($Dist <= (Common\Values::SecPerDay * 2))
		return static::StatusExpireWarning;

		if($Dist <= (Common\Values::SecPerDay * 7))
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

		if(!$this->DateExpire)
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
	FetchViaOpenSSL(string $Domain):
	static {

		$Client = NULL;
		$Data = NULL;
		$Err = NULL;

		////////

		try {
			$Client = new Local\Clients\CertLookupOpenSSL;
			$Data = $Client->Fetch($Domain);
		}

		catch(Local\Errors\Cert\CertLookupFailure $Err) {
			$Data = Local\Formats\CertSSL\Domain::FromNull($Domain);
		}

		catch(Throwable $Err) {
			throw $Err;
		}

		////////

		$Output = new static([
			'Domain'     => $Domain,
			'DateStart'  => $Data->DateStart,
			'DateExpire' => $Data->DateExpire
		]);

		return $Output;
	}

	static public function
	FetchViaCurl(string $Domain):
	static {

		$Client = new Local\Clients\CertLookupCurl;
		$Data = $Client->Fetch($Domain);

		$Output = new static([
			'Domain'     => $Domain,
			'DateStart'  => $Data->DateStart,
			'DateExpire' => $Data->DateExpire
		]);

		return $Output;
	}

	static public function
	FromNull(string $Domain):
	static {

		$Output = new static([
			'Domain' => NULL
		]);

		return $Output;
	}

}

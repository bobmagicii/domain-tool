<?php ##########################################################################
################################################################################

namespace Local\Clients;

use Local;
use Nether\Common;
use Nether\Console;

use Exception;

################################################################################
################################################################################

class CommandRDAP {

	public function
	__Construct() {

		static::CheckInstalledDeps();

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Fetch(string $Domain):
	Local\Formats\RDAP\Domain {

		if(substr_count($Domain, '.') < 1)
		throw new Local\Errors\RDAP\DomainInvalid($Domain);

		////////

		$JSON = $this->FetchJSON($Domain);
		$Output = Local\Formats\RDAP\Domain::FromJSON($JSON);

		return $Output;
	}

	public function
	FetchJSON(string $Domain):
	string {

		$Cmd = new Console\Struct\CommandLineUtil(sprintf(
			'rdap -j %s 2>&1',
			escapeshellarg($Domain)
		));

		$Result = $Cmd->Run();
		$Output = $Cmd->GetOutputString();

		if($Result !== 0)
		throw new Local\Errors\RDAP\CommandFailed($Output);

		////////

		return $Output;
	}

	/*
	public function
	FetchRegistrationInfo(string $Domain, bool $ObjOnFail=FALSE):
	Local\Tools\RegistrationInfo {

		$RDAP = NULL;
		$Output = Local\Tools\RegistrationInfo::FromNull($Domain);
		$Err = NULL;

		////////

		try {
			$RDAP = $this->Fetch($Domain);

			$Output = new Local\Tools\RegistrationInfo;
			$Output->Domain = $RDAP->Domain;
			$Output->Registrar = $RDAP->FindRegistrarName();
			$Output->DateRegister = $RDAP->FindRegistrationDate();
			$Output->DateExpire = $RDAP->FindExpirationDate();
			$Output->DateUpdate = $RDAP->FindExpirationDate();
		}

		catch(Local\Errors\RDAP\CommandFailed $Err) {
			if(!$ObjOnFail || !str_contains($Err->GetMessage(), '404'))
			throw $Err;
		}

		////////

		return $Output;
	}
	*/

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	CheckInstalledDeps():
	void {

		$Which = new Console\Struct\CommandLineUtil(match(TRUE) {
			(Common\Values::IsOnWindows())
			=> 'where rdap',

			default
			=> 'which rdap'
		});

		$Result = $Which->Run();

		////////

		if($Result !== 0)
		throw new Common\Error\FileNotFound('rdap command');

		if(!$Which->GetOutputString())
		throw new Common\Error\FileNotFound('rdap command path');

		////////

		return;
	}

};

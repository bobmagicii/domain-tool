<?php ##########################################################################
################################################################################

namespace Local;

use Nether\Common;
use Nether\Database;

################################################################################
################################################################################

class DB
extends Common\Prototype {

	protected Database\Connection
	$CTX;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public array
	$Tables = [
		'Domains' => <<< SQL
		CREATE TABLE IF NOT EXISTS Domains (
			ID INTEGER PRIMARY KEY AUTOINCREMENT,
			UUID TEXT,
			Domain TEXT,
			Registrar TEXT,
			TimeLogged INTEGER,
			TimeRegRegister INTEGER,
			TimeRegExpire INTEGER,
			TimeRegUpdate INTEGER,
			TimeCertExpire INTEGER
		);
		SQL
	];

	static public array
	$TableIndexes = [
		'CREATE UNIQUE INDEX "IdxDomain" ON `Domains`(`Domain`);'
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {


		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Connect():
	static {

		if(!$this->CTX->IsConnected())
		$this->CTX->Connect();

		return $this;
	}

	public function
	Open(bool $Fresh=FALSE):
	static {

		$this->Connect();

		////////

		if($Fresh || !$this->HasTables())
		$this->DefineTables();

		////////

		return $this;
	}

	public function
	NewVerseQuery():
	Database\Verse {

		return $this->CTX->NewVerse();
	}

	public function
	GetDatabaseName():
	string {

		return $this->CTX->Database;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	HasTables():
	bool {

		$SQL = $this->CTX->NewVerse();

		$SQL->Select('sqlite_master');
		$SQL->Fields('COUNT(*) AS TableCount');
		$SQL->Where('type="table"');

		$Result = $SQL->Query();
		$Row = $Result->Next();

		////////

		if(!$Row)
		return FALSE;

		if(!$Row->TableCount)
		return FALSE;

		////////

		return TRUE;
	}

	protected function
	DefineTables():
	static {


		foreach(static::$Tables as $Table => $SQL) {
			$this->CTX->Query(sprintf('DROP TABLE IF EXISTS %s', $Table));
			$this->CTX->Query($SQL);
		}

		foreach(static::$TableIndexes as $Table => $SQL) {
			$this->CTX->Query($SQL);
		}

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Touch(string $Filename, bool $Fresh=FALSE):
	static {

		$Output = new static([
			'CTX' => new Database\Connection(
				'sqlite',
				'localhost',
				$Filename,
				'', '',
				'utf8',
				'Default'
			)
		]);

		$Output->Open($Fresh);

		$DBM = new Database\Manager;
		$DBM->Add($Output->CTX);

		return $Output;
	}

};

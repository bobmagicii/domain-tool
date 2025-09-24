<?php ##########################################################################
################################################################################

namespace Local;

use Nether\Common;
use Nether\Console;
use Nether\Database;
use Nether\Dye;

use Exception;

################################################################################
################################################################################

#[Console\Meta\Application('DomainTool', '0.0.1-dev', NULL, 'domain.phar')]
class App
extends Console\Client {

	const
	DomainDB          = 'Reg.DB.File',
	ConfRegMode       = 'Reg.Mode',
	ConfCertMode      = 'Cert.Mode';

	const
	RegModeRDAP     = 'rdap';

	const
	CertModeOpenSSL = 'openssl',
	CertModeCurl    = 'curl';

	const
	WordOK         = 'OK',
	WordSoon       = 'Soon',
	WordImminent   = 'Imminent',
	WordExpired    = 'Expired',
	WordError      = 'Error',
	WordDefault    = 'Default',

	WordDomain     = 'Domain',
	WordRegistrar  = 'Registrar',
	WordRegExpire  = 'Registration Expire',
	WordCertExpire = 'SSL Cert Expire';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected Common\Datastore
	$Config;

	protected Common\Datastore
	$Library;

	protected string
	$AppRoot;

	public ?Dye\Colour
	$BorderColour;

	public ?Dye\Colour
	$FooterColour;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	private Common\Timer
	$TimerReg;

	private Common\Timer
	$TimerCert;

	private Common\Timer
	$TimerTotal;

	private DB
	$DB;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnPrepare():
	void {

		$this->AppRoot = $this->GetOption('AppRoot');
		$this->TimerTotal = Common\Timer::New(Start: TRUE);
		$this->TimerReg = Common\Timer::New();
		$this->TimerCert = Common\Timer::New();

		$this->PrepareConfig();
		$this->PrepareLibrary();
		$this->PrepareTheme();

		return;
	}

	protected function
	PrepareConfig():
	void {

		$this->Config = new Common\Datastore([
			static::DomainDB     => Common\Filesystem\Util::Pathify($this->AppRoot, 'domain.sqlite'),
			static::ConfRegMode  => static::RegModeRDAP,
			static::ConfCertMode => static::CertModeOpenSSL
		]);

		return;
	}

	protected function
	PrepareTheme():
	void {

		//$this->BorderColour = Dye\Colour::From('#FFFF44');
		//$this->FooterColour = Dye\Colour::From('#696969');

		$this->BorderColour = NULL;
		$this->FooterColour = NULL;

		return;
	}

	protected function
	PrepareLibrary():
	void {

		$this->Library = new Common\Datastore;
		$this->Library['Common'] = new Common\Library($this->Config);
		$this->Library['Database'] = new Database\Library($this->Config);

		return;
	}

	protected function
	OnReady():
	void {

		$OptDB = $this->GetOption('db') ?: FALSE;

		// open the sqlite datbaase

		//if($OptDB)
		$this->DB = DB::Touch($this->Config->Get(static::DomainDB));

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Console\Meta\Command('report')]
	#[Console\Meta\Arg('domain')]
	#[Console\Meta\Arg('...')]
	#[Console\Meta\Toggle('--files', 'Arguments are paths to files of domains, one on each line.')]
	#[Console\Meta\Toggle('--sort', 'Sort report by domain name.')]
	#[Console\Meta\Toggle('--db', 'Store results in SQLite database.')]
	#[Console\Meta\Toggle('--short', 'Only print domain table to terminal.')]
	#[Console\Meta\Toggle('--quiet', 'No output printed to terminal.')]
	public function
	HandleDomains():
	int {

		$OptSort = $this->GetOption('sort') ?: FALSE;
		$OptFiles = $this->GetOption('files') ?: FALSE;
		$OptCertMode = $this->GetOption('certmode') ?: $this->Config->Get(static::ConfCertMode);
		$OptLogToDB = $this->GetOption('db') ?: FALSE;
		$OptShort = $this->GetOption('short') ?: FALSE;
		$OptVerbose = $this->GetOption('verbose') ?: FALSE;
		$OptQuiet = $this->GetOption('quiet') ?: FALSE;

		$Domains = NULL;
		$Files = NULL;

		////////

		if($OptFiles === TRUE)
		$Files = $this->FetchInputFiles();

		////////

		$Domains = match(TRUE) {
			($OptFiles === TRUE)
			=> $this->FetchDomainsFromInputFiles($Files),

			default
			=> $this->FetchDomainsFromInputArgs($this->Args->Inputs)
		};

		////////

		if($OptSort === TRUE)
		$Domains->SortKeys(fn(string $A, string $B)=> $A <=> $B);

		////////

		$this->RunDomainsReport($Domains, $OptLogToDB);

		////////

		if($OptVerbose && !$OptQuiet) {
			$this->PrintConfigHeader();
			$this->PrintConfigReport();
		}

		if(!$OptShort && !$OptQuiet) {
			$this->PrintReportCommandHeader('Domain Registration & SSL Certs');
			$this->PrintFilesHeader($Files);
			$this->PrintFilesReport($Files);
		}

		////////

		if($Domains->Count()) {
			if(!$OptShort && !$OptQuiet)
			$this->PrintDomainsHeader($Domains);

			if(!$OptQuiet)
			$this->PrintDomainsReport($Domains);
		}

		////////

		if(!$OptShort && !$OptQuiet) {
			$this->TimerTotal->Stop();
			$this->PrintTimerHeader();
			$this->PrintTimerReport();
		}

		return 0;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	RunDomainsReport(Common\Datastore $Domains, bool $LogToDB=FALSE):
	void {

		$Domains->RemapKeyValue(function(string $Domain) use($LogToDB) {

			$Cert = Tools\CertInfo::FromNull($Domain);
			$Now = Common\Date::Unixtime();

			$this->TimerReg->Start();
			$Reg = $this->FetchRegistrationInfo($Domain);
			$this->TimerReg->Stop();

			if($Reg->IsRegistered()) {
				$this->TimerCert->Start();
				$Cert = $this->FetchCertInfo($Domain);
				$this->TimerCert->Stop();
			}

			////////

			//if($LogToDB) {
				$Old = DB\Domain::GetByField('Domain', $Domain);

				if($Old) $Old->Update([
					'Registrar'       => $Reg->Registrar,
					'TimeLogged'      => $Now,
					'TimeRegRegister' => $Reg->GetTimeRegister(),
					'TimeRegExpire'   => $Reg->GetTimeExpire(),
					'TimeRegUpdate'   => $Reg->GetTimeUpdate(),
					'TimeCertExpire'  => $Cert->GetTimeExpire()
				]);

				else DB\Domain::Insert([
					'UUID'            => Common\UUID::V7(),
					'Domain'          => $Domain,
					'Registrar'       => $Reg->Registrar,
					'TimeLogged'      => $Now,
					'TimeRegRegister' => $Reg->GetTimeRegister(),
					'TimeRegExpire'   => $Reg->GetTimeExpire(),
					'TimeRegUpdate'   => $Reg->GetTimeUpdate(),
					'TimeCertExpire'  => $Cert->GetTimeExpire()
				]);
			//}

			////////

			return [ $Reg, $Cert ];
		});

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Info('Prints report command heading introduction.')]
	protected function
	PrintReportCommandHeader(string $Text):
	void {

		Console\Elements\H1::New(
			Client: $this,
			Text: $Text,
			BorderColour: $this->BorderColour,
			Print: 2
		);

		return;
	}

	#[Common\Meta\Info('Print config report header.')]
	protected function
	PrintConfigHeader():
	void {

		Console\Elements\H2::New(
			Client: $this,
			Text: 'Configuration',
			BorderColour: $this->BorderColour,
			Print: 2
		);

		return;
	}

	protected function
	PrintConfigReport():
	void {

		Console\Elements\ListNamed::New(
			$this,
			Items: [
				'RegMode' => $this->Config->Get(static::ConfRegMode),
				'CertMode' => $this->Config->Get(static::ConfCertMode)
			],
			Print: 2
		);

		return;
	}

	#[Common\Meta\Info('Prints list of files heading.')]
	protected function
	PrintFilesHeader(?Common\Datastore $Files):
	void {

		if(!$Files || !$Files->Count())
		return;

		Console\Elements\H2::New(
			Client: $this,
			Text: sprintf('Files (%d)', $Files->Count()),
			BorderColour: $this->BorderColour,
			Print: 2
		);

		return;
	}

	#[Common\Meta\Info('Prints list of files read.')]
	protected function
	PrintFilesReport(?Common\Datastore $Files):
	void {

		if(!$Files || !$Files->Count())
		return;

		$List = Console\Elements\ListBullet::New(
			Client: $this,
			Items: $Files->Values()->Export(),
			BulletColour: $this->BorderColour
		);

		$List->Print();

		return;
	}

	#[Common\Meta\Info('Prints domain table heading.')]
	protected function
	PrintDomainsHeader(Common\Datastore $Domains):
	void {

		Console\Elements\H2::New(
			Client: $this,
			Text: sprintf('Domains (%d)', $Domains->Count()),
			BorderColour: $this->BorderColour,
			Print: 2
		);

		return;
	}

	#[Common\Meta\Info('Prints domain table report.')]
	protected function
	PrintDomainsReport(Common\Datastore $Domains):
	void {

		$Legend = new Common\Datastore([
			static::WordOK       => '#55EE88',
			static::WordSoon     => '#EEEE44',
			static::WordImminent => '#EE9966',
			static::WordExpired  => '#FF6666',
			static::WordError    => '#AA66AA',
			static::WordDefault  => NULL
		]);

		$Headers = new Common\Datastore([
			str_repeat($this->GetBlankChar(), 2),
			static::WordDomain,
			static::WordRegistrar,
			static::WordRegExpire,
			static::WordCertExpire
		]);

		$Rows = new Common\Datastore;
		$CountRegStatus = new Common\Datastore;
		$CountCertStatus = new Common\Datastore;
		$RegSummary = NULL;
		$CertSummary = NULL;
		$LegendSummary = NULL;

		////////

		$Domains->EachKeyValue(function(string $Domain, array $Data) use($Rows, $Legend, $CountRegStatus, $CountCertStatus) {

			list($Reg, $Cert) = $Data;

			/**
			 * @var Tools\RegistrationInfo $Reg
			 * @var Tools\CertInfo $Cert
			 */

			$RegCode = $Reg->GetStatusCode();
			$RegStyle = $this->GetRegStatusStyle($RegCode, $Legend);
			$RegLabel = $this->GetRegStatusLabel($RegCode, $Reg);
			$CountRegStatus->Bump($RegCode, 1);

			$CertCode = $Cert->GetStatusCode();
			$CertStyle = $this->GetCertStatusStyle($CertCode, $Legend);
			$CertLabel = $this->GetCertStatusLabel($CertCode, $Cert);
			$CountCertStatus->Bump($CertCode, 1);

			////////

			if(!$Reg->IsRegistered())
			$CertStyle = $Legend[static::WordDefault];

			////////

			$StatusLabel = sprintf(
				'%s%s',
				$this->Format($this->GetRegStatusChar($Reg->GetStatusCode()), C: $RegStyle),
				$this->Format($this->GetCertStatusChar($Cert->GetStatusCode()), C: $CertStyle)
			);

			////////

			$Rows->Push([
				$StatusLabel,
				($Reg->GetDomain()        ?: $this->GetBlankChar()),
				($Reg->GetRegistrarName() ?: $this->GetBlankChar()),
				($RegLabel                ?: $this->GetBlankChar()),
				($CertLabel               ?: $this->GetBlankChar())
			]);

			return;
		});

		////////

		$RegSummary = (
			($CountRegStatus)
			->MapKeyValue(fn(string $Key, int $Val)=> sprintf(
				'%s(%d)',
				Tools\RegistrationInfo::StatusWords[$Key],
				$Val
			))
			->Join(' ')
		);

		$CertSummary = (
			($CountCertStatus)
			->MapKeyValue(fn(string $Key, int $Val)=> sprintf(
				'%s(%d)',
				Tools\CertInfo::StatusWords[$Key],
				$Val
			))
			->Join(' ')
		);

		$LegendSummary = sprintf(
			'[[[ LEGEND: %s ]]]',
			$Legend
			->MapKeyValue(fn($K, $V)=> $this->Format($K, C: $V))
			->Join(', ')
		);

		////////

		Console\Elements\ListNamed::New(
			Client: $this,
			Items: [ 'REG'=> $RegSummary, 'SSL'=> $CertSummary ],
			Print: 2
		);

		Console\Elements\Table::New(
			Client: $this,
			Headers: $Headers,
			Rows: $Rows,
			Print: 2
		);

		$this->PrintLn($LegendSummary, 2);

		return;
	}

	#[Common\Meta\Info('Prints debugging timer header.')]
	protected function
	PrintTimerHeader():
	void {

		Console\Elements\H2::New(
			Client: $this,
			Text: 'Done',
			BorderColour: $this->FooterColour,
			Print: 2
		);

		return;
	}

	#[Common\Meta\Info('Prints debuging timers.')]
	protected function
	PrintTimerReport():
	void {

		$TimerFmt = '%.2fsec';

		$List = Console\Elements\ListNamed::New(
			Client: $this,
			Items: [
				'Domains' => sprintf($TimerFmt, $this->TimerReg->Get()),
				'Certs'   => sprintf($TimerFmt, $this->TimerCert->Get()),
				'Total'   => sprintf($TimerFmt, $this->TimerTotal->Get())
			],
			BulletColour: $this->FooterColour
		);

		$List->Print(2);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	FetchRegistrationInfo(string $Domain):
	Tools\RegistrationInfo {

		$Mode = $this->Config->Get(static::ConfRegMode);

		////////

		// check if we have old data and if it is recent enough. don't
		// really wanna get banned or rate throttled by rdap servers.

		$Old = DB\Domain::GetByField('Domain', $Domain);

		if($Old && $Old->WasRecentlyLogged()) {
			return Tools\RegistrationInfo::FromDB($Old);
		}

		////////

		$Info = match($Mode) {
			default => Tools\RegistrationInfo::FetchViaRDAP($Domain)
		};

		////////

		return $Info;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	FetchCertInfo(string $Domain, ?string $Mode=NULL):
	Tools\CertInfo {

		$Mode ??= $this->Config->Get(static::ConfCertMode);

		////////

		$Info = match($Mode) {
			static::CertModeCurl => Tools\CertInfo::FetchViaCurl($Domain),
			default              => Tools\CertInfo::FetchViaOpenSSL($Domain)
		};

		////////

		return $Info;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	FetchDomainsFromInputArgs(?Common\Datastore $Args=NULL):
	Common\Datastore {

		// every argument in this instance is considered to be a domain
		// name to look up.

		$Args ??= $this->Args->Inputs;
		$List = new Common\Datastore;

		foreach($Args as $DName)
		$List->Push($DName);

		////////

		$List->Flip();

		return $List;
	}

	protected function
	FetchDomainsFromInputFiles(?Common\Datastore $Files=NULL):
	Common\Datastore {

		$Files ??= $this->FetchInputFiles();
		$List = new Common\Datastore;

		////////

		$Files->Each(function(string $Filename) use($List) {

			$Lines = Common\Datastore::FromArray(file($Filename));
			$Lines->Remap(fn(string $Domain)=> trim($Domain));
			$Lines->Filter(fn(string $Domain)=> !!$Domain);

			$List->MergeRight($Lines);

			return;
		});

		////////

		$List->Flip();

		return $List;
	}

	protected function
	FetchInputFiles():
	Common\Datastore {

		$List = new Common\Datastore;
		$DName = NULL;

		////////

		foreach($this->Args->Inputs as $DName)
		$List->Push($DName);

		////////

		$List->Remap(fn(string $Filename)=> realpath($Filename));
		$List->Filter(fn(false|string $Filename)=> is_string($Filename));
		$List->Filter(fn(string $Filename)=> file_exists($Filename));

		return $List;
	}

	protected function
	GetRegStatusChar(int $StatusCode):
	string {

		$Char = '█';

		return $Char;
	}

	protected function
	GetRegStatusLabel(int $StatusCode, Tools\RegistrationInfo $Reg):
	string {

		return match($StatusCode) {
			$Reg::StatusFailure       => '',
			$Reg::StatusUnregistered  => '',
			default                   => sprintf('%s (%s)', $Reg->GetDateExpire(), $Reg->GetExpireTimeframe())
		};
	}

	protected function
	GetRegStatusStyle(int $StatusCode, Common\Datastore $Legend):
	?string {

		return match($StatusCode) {
			Tools\RegistrationInfo::StatusFailure
			=> $Legend[static::WordError],

			Tools\RegistrationInfo::StatusOK
			=> $Legend[static::WordOK],

			Tools\RegistrationInfo::StatusExpireWarning
			=> $Legend[static::WordImminent],

			Tools\RegistrationInfo::StatusExpireSoon
			=> $Legend[static::WordSoon],

			Tools\RegistrationInfo::StatusExpired
			=> $Legend[static::WordExpired],

			default
			=> $Legend[static::WordDefault]
		};
	}

	protected function
	GetCertStatusChar(int $StatusCode):
	string {

		$Char = '█';

		return $Char;
	}

	protected function
	GetCertStatusLabel(int $StatusCode, Tools\CertInfo $Cert):
	string {

		return match($StatusCode) {
			$Cert::StatusFailure       => '',
			default                    => sprintf('%s (%s)', $Cert->GetDateExpire(), $Cert->GetExpireTimeframe())
		};
	}

	protected function
	GetCertStatusStyle(int $StatusCode, Common\Datastore $Legend):
	?string {

		return match($StatusCode) {
			Tools\CertInfo::StatusFailure
			=> $Legend[static::WordError],

			Tools\CertInfo::StatusOK
			=> $Legend[static::WordOK],

			Tools\CertInfo::StatusExpireWarning
			=> $Legend[static::WordImminent],

			Tools\CertInfo::StatusExpireSoon
			=> $Legend[static::WordSoon],

			Tools\CertInfo::StatusExpired
			=> $Legend[static::WordExpired],

			default
			=> $Legend[static::WordDefault]
		};
	}

	protected function
	GetBlankChar():
	string {

		return '-';
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	GetPharFiles():
	Common\Datastore {

		$Index = parent::GetPharFiles();

		$Index->Push('core');

		return $Index;
	}

	protected function
	GetPharFileFilters():
	Common\Datastore {

		$Output = parent::GetPharFileFilters();

		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/monolog'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/dealerdirect'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/fileeye'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/squizlabs'));

		return $Output;
	}

};

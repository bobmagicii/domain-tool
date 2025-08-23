<?php ##########################################################################
################################################################################

namespace Local;

use Nether\Common;
use Nether\Console;
use Nether\Dye;

use Exception;

################################################################################
################################################################################

#[Console\Meta\Application('DomainTool', '0.0.1-dev', NULL, 'domain.phar')]
class AppMain
extends Console\Client {

	const
	ConfRegMode  = 'Reg.Mode',
	ConfCertMode = 'Cert.Mode';

	const
	RegModeRDAP     = 'rdap';

	const
	CertModeOpenSSL = 'openssl',
	CertModeCurl    = 'curl';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected Common\Datastore
	$Config;

	protected Common\Datastore
	$Library;

	public Dye\Colour
	$BorderColour;

	public Dye\Colour
	$FooterColour;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	private Common\Timer
	$TimerReg;

	private Common\Timer
	$TimerCert;

	private Common\Timer
	$TimerTotal;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnPrepare():
	void {

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
			static::ConfRegMode  => static::RegModeRDAP,
			static::ConfCertMode => static::CertModeOpenSSL
		]);

		return;
	}

	protected function
	PrepareTheme():
	void {

		$this->BorderColour = Dye\Colour::From('#FFFF44');
		$this->FooterColour = Dye\Colour::From('#696969');

		return;
	}

	protected function
	PrepareLibrary():
	void {

		$this->Library = new Common\Datastore;
		$this->Library['Common'] = new Common\Library($this->Config);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Console\Meta\Command('report')]
	#[Console\Meta\Arg('domain')]
	#[Console\Meta\Arg('...')]
	#[Console\Meta\Toggle('--files', 'Arguments are paths to files of domains, one on each line.')]
	#[Console\Meta\Toggle('--sort', 'Sort report by domain name.')]
	#[Console\Meta\Toggle('--short', 'Only show the domain report table.')]
	public function
	HandleDomains():
	int {

		$OptSort = $this->GetOption('sort');
		$OptFiles = $this->GetOption('files');
		$OptShort = $this->GetOption('short') ?: FALSE;

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

		$this->RunDomainsReport($Domains);

		////////

		if(!$OptShort) {
			$this->PrintCommandHeader('Domain Registration & SSL Certs');
			$this->PrintFilesHeader($Files);
			$this->PrintFilesReport($Files);
		}

		////////

		if($Domains->Count()) {
			if(!$OptShort)
			$this->PrintDomainsHeader($Domains);

			$this->PrintDomainsReport($Domains);
		}

		////////

		if(!$OptShort) {
			$this->TimerTotal->Stop();
			$this->PrintTimerHeader();
			$this->PrintTimerReport();
		}

		return 0;
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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	RunDomainsReport(Common\Datastore $Domains):
	void {

		$Domains->RemapKeyValue(function(string $Domain) {

			$Cert = Tools\CertInfo::FromNull($Domain);

			$this->TimerReg->Start();
			$Reg = $this->FetchRegistrationInfo($Domain);
			$this->TimerReg->Stop();

			if($Reg->IsRegistered()) {
				$this->TimerCert->Start();
				$Cert = $this->FetchCertInfo($Domain);
				$this->TimerCert->Stop();
			}

			return [ $Reg, $Cert ];
		});

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	PrintCommandHeader(string $Text):
	void {

		$Header = Console\Elements\H1::New(
			Client: $this,
			Text: $Text,
			BorderColour: $this->BorderColour
		);

		$Header->Print(2);

		return;
	}

	protected function
	PrintFilesHeader(Common\Datastore $Files):
	void {

		$Header = Console\Elements\H2::New(
			Client: $this,
			Text: sprintf('Files (%d)', $Files->Count()),
			BorderColour: $this->BorderColour
		);

		$Header->Print(2);

		return;
	}

	protected function
	PrintFilesReport(Common\Datastore $Files):
	void {

		if(!$Files->Count())
		return;

		$List = Console\Elements\ListBullet::New(
			Client: $this,
			Items: $Files->Values()->Export(),
			BulletColour: $this->BorderColour
		);

		$List->Print();

		return;
	}

	protected function
	PrintDomainsHeader(Common\Datastore $Domains):
	void {

		$Header = Console\Elements\H2::New(
			Client: $this,
			Text: sprintf('Domains (%d)', $Domains->Count()),
			BorderColour: $this->BorderColour
		);

		$Header->Print(2);

		return;
	}

	protected function
	PrintDomainsReport(Common\Datastore $Domains):
	void {

		$Block = '█';
		$Blank = '-';
		$Headers = [ str_repeat($Block, 2), 'Domain', 'Registrar', 'Registration Expire', 'SSL Cert Expire' ];
		$Rows = new Common\Datastore;
		$Fmts = new Common\Datastore(['s', 's', 's', 's', 's' ]);
		$Styles = new Common\Datastore;

		$Legend = new Common\Datastore([
			'OK'       => '#55EE88',
			'Soon'     => '#EEEE44',
			'Imminent' => '#EE9966',
			'Expired'  => '#FF6666',
			'Error'    => '#AA66AA',
			'Default'  => NULL,
		]);

		$Domains->EachKeyValue(function(string $Domain, array $Data) use($Rows, $Fmts, $Styles, $Legend, $Block, $Blank) {

			list($Reg, $Cert) = $Data;

			/**
			 * @var Tools\RegistrationInfo $Reg
			 * @var Tools\CertInfo $Cert
			 */

			$RegStyle = match($Reg->GetStatusCode()) {
				$Reg::StatusFailure       => $Legend['Error'],
				$Reg::StatusOK            => $Legend['OK'],
				$Reg::StatusExpireWarning => $Legend['Imminent'],
				$Reg::StatusExpireSoon    => $Legend['Soon'],
				$Reg::StatusExpired       => $Legend['Expired'],
				default                   => $Legend['Default']
			};

			$RegLabel = match($Reg->GetStatusCode()) {
				$Reg::StatusFailure       => '',
				$Reg::StatusUnregistered  => '',
				default                   => sprintf('%s (%s)', $Reg->GetDateExpire(), $Reg->GetExpireTimeframe())
			};

			////////

			$CertStyle = match($Cert->GetStatusCode()) {
				$Cert::StatusFailure       => $Legend['Error'],
				$Cert::StatusOK            => $Legend['OK'],
				$Cert::StatusExpireWarning => $Legend['Imminent'],
				$Cert::StatusExpireSoon    => $Legend['Soon'],
				$Cert::StatusExpired       => $Legend['Expired'],
				default                    => $Legend['Default']
			};

			if(!$Reg->IsRegistered())
			$CertStyle = $Legend['Default'];

			$CertLabel = match($Cert->GetStatusCode()) {
				$Cert::StatusFailure       => '',
				default                    => sprintf('%s (%s)', $Cert->GetDateExpire(), $Cert->GetExpireTimeframe())
			};

			////////

			$StatusFlags = sprintf(
				'%s%s',
				$this->Format($Block, C: $RegStyle),
				$this->Format($Block, C: $CertStyle)
			);

			////////

			$Row = [];
			$Row[] = $StatusFlags;
			$Row[] = $Reg->GetDomain() ?: $Blank;
			$Row[] = $Reg->GetRegistrarName() ?: $Blank;
			$Row[] = $RegLabel ?: $Blank;
			$Row[] = $CertLabel ?: $Blank;
			$Rows->Push($Row);

			if(!$Reg->IsRegistered())
			$Styles->Push(Console\Theme::Error);
			else
			$Styles->Push(Console\Theme::Default);

			return;
		});

		$Table = Console\Elements\Table::New($this);
		$Table->SetHeaders(...$Headers);
		$Table->SetData($Rows->Export());
		$Table->PrintHeaders();
		$Table->PrintRows();
		$Table->PrintFooter();
		$this->PrintLn(sprintf(
			'[ LEGEND: %s ]',
			$Legend
			->MapKeyValue(fn($K, $V)=> $this->Format($K, C: $V))
			->Join(', ')
		));
		$this->PrintLn();

		return;
	}

	protected function
	PrintTimerHeader():
	void {

		$Header = Console\Elements\H2::New(
			Client: $this,
			Text: 'Done',
			BorderColour: $this->FooterColour
		);

		$Header->Print(2);

		return;
	}

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

		$Info = match($Mode) {
			default => Tools\RegistrationInfo::FetchViaRDAP($Domain)
		};

		////////

		return $Info;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	FetchCertInfo(string $Domain):
	Tools\CertInfo {

		$Mode = $this->Config->Get(static::ConfCertMode);

		////////

		$Info = match($Mode) {
			static::CertModeCurl => Tools\CertInfo::FetchViaCurl($Domain),
			default              => Tools\CertInfo::FetchViaOpenSSL($Domain)
		};

		////////

		return $Info;
	}

};

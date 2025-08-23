<?php ##########################################################################
################################################################################

namespace Local\Clients;

use Local;
use Nether\Common;

################################################################################
################################################################################

class CertLookupOpenSSL {

	public function
	Fetch(string $Domain):
	Local\Formats\CertSSL\Domain {


		$Output = NULL;
		$StErr = NULL;
		$StMsg = NULL;
		$Timeout = 30.0;
		$Cert = NULL;
		$Info = NULL;

		////////

		$Context = stream_context_create([
			'ssl' => [
				'capture_peer_cert' => TRUE,
				'verify_peer'       => FALSE
			]
		]);

		$Client = @stream_socket_client(
			sprintf('ssl://%s:443', $Domain),
			$StErr, $StMsg,
			$Timeout,
			STREAM_CLIENT_CONNECT,
			$Context
		);

		if(!$Client)
		throw new Local\Errors\Cert\CertLookupFailure("OPENSSL_CLIENT({$Domain})");

		$Cert = stream_context_get_params($Client);
		$Info = openssl_x509_parse($Cert['options']['ssl']['peer_certificate']);

		////////

		if(!is_array($Info))
		throw new Local\Errors\Cert\CertLookupFailure("OPENSSL_STREAM({$Domain})");

		if(!isset($Info['subject']) || !isset($Info['subject']['CN']))
		throw new Local\Errors\Cert\CertLookupUnexpectedFormat($Domain, 'no [subject][CN] in openssl result');

		if(!isset($Info['validFrom_time_t']))
		throw new Local\Errors\Cert\CertLookupUnexpectedFormat($Domain, 'no [validFrom_time_t] in openssl result');

		if(!isset($Info['validTo_time_t']))
		throw new Local\Errors\Cert\CertLookupUnexpectedFormat($Domain, 'no [validTo_time_t] in openssl result');

		////////

		$Output = Local\Formats\CertSSL\Domain::FromWithTime(
			$Domain,
			$Info['validFrom_time_t'],
			$Info['validTo_time_t']
		);

		return $Output;
	}

};

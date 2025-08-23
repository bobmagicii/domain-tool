<?php ##########################################################################
################################################################################

namespace Local\Clients;

use Local;
use Nether\Common;

################################################################################
################################################################################

class CertLookupCurl {

	public function
	Fetch(string $Domain):
	Local\Formats\CertSSL\Domain {

		$Curl = NULL;
		$Info = NULL;

		////////

		$Curl = curl_init();
		curl_setopt($Curl, CURLOPT_URL, sprintf('https://%s', $Domain));
		curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($Curl, CURLOPT_CERTINFO, TRUE);
		curl_setopt($Curl, CURLOPT_RETURNTRANSFER, TRUE);

		if(!curl_exec($Curl))
		throw new Local\Errors\Cert\CertLookupFailure("CURL({$Domain})");

		$Info = curl_getinfo($Curl, CURLINFO_CERTINFO);
		curl_close($Curl);

		////////

		if(!is_array($Info) || !isset($Info[0]))
		throw new Local\Errors\Cert\CertLookupFailure("CURLRESULT({$Domain})");

		if(!isset($Info[0]['Start date']))
		throw new Local\Errors\Cert\CertLookupUnexpectedFormat($Domain, 'no [Start date] in curl result');

		if(!isset($Info[0]['Expire date']))
		throw new Local\Errors\Cert\CertLookupUnexpectedFormat($Domain, 'no [Expire date] in curl result');

		////////

		$Output = Local\Formats\CertSSL\Domain::FromWithDatestamp(
			$Domain,
			$Info[0]['Start date'],
			$Info[0]['Expire date']
		);

		return $Output;
	}

};

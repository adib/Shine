<?php

class EngineCoppertino extends Engine {
	# Copertino signature
	public function generateLicenseSignature($dict) {
		$values = implode('', $dict);
		# uses CocoaFob private + public keys!
		openssl_sign($values, $binary_signature,$this->application->cf_pkey, OPENSSL_ALGO_SHA1);
		
		return base64_encode($binary_signature);
	}
	
	# No offline activations
	public function generateLicense() { }
	public function downloadLicense() { }
	
	# Online license generation
	public function generateLicenseOnline($hwid)
	{
		$rand = EngineOnline::generate_uid(15, true);
		$dict = array(
			'hash' => base64_encode(sha1($hwid . $this->application->bundle_name . $rand, true)),
			'randValue' => $rand,
			'expirationDate' => time(),
			'expirationVersion' => ''
		);
		$sig = $this->generateLicenseSignature($dict);
		
		return static::generatePlist($dict, $sig);
	}
}
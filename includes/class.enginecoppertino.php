<?php

class EngineCoppertino extends Engine {
	# Copertino signature
	public function generateLicenseSignature($dict) {
		$values = implode('', $dict);
		# uses CocoaFob private + public keys!
		openssl_sign($values, $binary_signature,$this->application->rsa_pkey, OPENSSL_ALGO_SHA1);
		
		return base64_encode($binary_signature);
	}
	
	# No offline activations
	public function generateLicense() { }
	public function downloadLicense() { }
	
	# Online license generation
	public function generateLicenseOnline($hwid)
	{
		$rand = EngineOnline::generate_uid(20, false);
		
		$lt_id = $this->order->license_type_id;
		if (!empty($lt_id)) {
			$lt = new LicenseType($lt_id);
			if ($lt->ok()) $exp_version = $lt->max_update_version;
		}
		$dict = array(
			'hash' => base64_encode(sha1($hwid . $this->application->bundle_id . $rand, true)),
			'randValue' => $rand,
			'expirationDate' => (int)strtotime($this->order->expiration_date),
			'expirationVersion' => !empty($exp_version) ? $exp_version : ''
		);
		$sig = $this->generateLicenseSignature($dict);
		
		return static::generatePlist($dict, $sig);
	}
}
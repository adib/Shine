<?PHP
	class EngineCocoaFob extends Engine
	{
		# CocoaFob signature
		public function generateLicenseSignature($dict) {
			$stringData = implode(',', $dict);
			$binary_signature ="";
			openssl_sign($stringData, $binary_signature,$this->application->cf_pkey, OPENSSL_ALGO_DSS1);
			// base 32 encode the signature
			$encoded = base32_encode($binary_signature);
			// replace O with 8 and I with 9
			$replacement = str_replace("O", "8", str_replace("I", "9", $encoded));
			//remove padding if any.
			$padding = trim(str_replace("=", "", $replacement));
			$dashed = rtrim(chunk_split($padding, 5,"-"));
			
			return substr($dashed, 0 , strlen($dashed) -1);
		}
		
		public function generateLicense()
		{
			# Create our license dictionary to be signed - from AquaticPrime engine
			$dict = array(
					"Product"       => $this->order->item_name,
					"Name"          => utf8_encode($this->order->first_name . ' ' . $this->order->last_name),
					"Email"         => utf8_encode($this->order->payer_email),
					"Licenses"      => $this->order->quantity,
					"Timestamp"     => date('r', strtotime($this->order->dt)),
					"TransactionID" => $this->order->txn_id
			);
			
			$sig = $this->generateLicenseSignature($dict);
			
			# Convert signature string to plist licence file
			$plist = static::generatePlist($dict, $sig);

			$this->order->license = $plist;
			$this->order->update();
		}
		
		# From class.engineaquaticprime.php
		public function emailLicense()
		{
			Mail_Postmark::compose()
			    ->addTo($this->order->payer_email)
			    ->subject($this->application->email_subject)
			    ->messagePlain($this->application->getBody($this->order))
				->addCustomAttachment($this->application->license_filename, $this->order->license, 'text/plain')
			    ->send();
		}
		
		# From class.engineaquaticprime.php
		public function downloadLicense()
		{
			header("Cache-Control: public");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Content-Type: application/x-download"); // Stupid fix for Safari not honoring content-disposition
			header("Content-Length: " . strlen($this->order->license));
			header("Content-Disposition: attachment; filename={$this->application->license_filename}");
			header("Content-Transfer-Encoding: binary");
			echo $this->order->license;
			exit;
		}
	}

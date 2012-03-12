<?PHP
	class EngineCocoaFob extends Engine
	{
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
			
			$stringData = implode(',', $dict);
			#################################################
			$binary_signature ="";
			openssl_sign($stringData, $binary_signature,$this->application->cf_pkey, OPENSSL_ALGO_DSS1);
			// base 32 encode the signature
			$encoded = base32_encode($binary_signature);
			// replace O with 8 and I with 9
			$replacement = str_replace("O", "8", str_replace("I", "9", $encoded));
			//remove padding if any.
			$padding = trim(str_replace("=", "", $replacement));
			$dashed = rtrim(chunk_split($padding, 5,"-"));
			$sig = substr($dashed, 0 , strlen($dashed) -1);
			
			# Convert signature string to plist licence file
			$plist = "<?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n";
			$plist .= "<!DOCTYPE plist PUBLIC \"-//Apple Computer//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n";
			$plist .= "<plist version=\"1.0\">\n<dict>\n";
			foreach($dict as $key => $value) {
				$value = utf8_encode($value);
				$plist .= "\t<key>" . htmlspecialchars($key, ENT_NOQUOTES) . "</key>\n";
				$plist .= "\t<string>" . htmlspecialchars($value, ENT_NOQUOTES) . "</string>\n";
			}
			$plist .= "\t<key>Signature</key>\n";
			$plist .= "\t<data>$sig</data>\n";
			$plist .= "</dict>\n";
			$plist .= "</plist>\n";

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

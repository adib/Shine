<?php
abstract class EngineOnline {
	public $order;
	public $application;
	
	# Serial number generation
	abstract public function generateSerial();
	
	# Basic unique id generator - can use letters and numbers (and other characters if needed)
	static public function generate_uid($count = 50, $use_other_characters = false, $only_upper_cased = false) {
		$uid = '';
		for ($i = 0; $i < $count; $i++) {
			if ($use_other_characters) { # A large variety of characters, see ASCII character table
				$max_rand = $only_upper_cased == true ? 96 : 122; # Whether to use lower-cased letters
				$rand = mt_rand(33, $max_rand);
			}
			else {
				$max_rand = $only_upper_cased == true ? 36 : 62; # Whether to use lower-cased letters
				$rand = mt_rand(1, $max_rand);
				if ($rand <= 26) { # Capsed letters 'ABCDE'
					$rand += 64;
				}
				else if ($rand <= 36) { # Numbers '01234'
					$rand += 21;
				}
				else { # Small letters 'abcde'
					$rand += 60;
				}
			}
			$uid .= chr($rand);
		}
		return $uid;
	}
}
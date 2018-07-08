<?php
class EngineOnlineDefault extends EngineOnline {
	# Serial number generation
	public function generateSerial() {
		# 20 upper-cased letters/numbers with dashes every 5 characters
		return self::generate_uid(5, false, true)
				. '-' . self::generate_uid(5, false, true)
				. '-' . self::generate_uid(5, false, true)
				. '-' . self::generate_uid(5, false, true);
	}
}
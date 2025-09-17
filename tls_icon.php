<?php

class tls_icon extends rcube_plugin
{
	const POSTFIX_TLS_REGEX = "/\(using (TLS(?:[^()]|\([^()]*\))*)\)/im";
	const POSTFIX_LOCAL_USER_REGEX = "/\([a-zA-Z]*, from userid [0-9]*\)/im";
	const LOCAL_DELIVERY_REGEX = "/(?=.*ESMTPSA)\b(localhost|127\.0\.0\.1)\b/i"; // Requires that the message was directly delivered locally (ESMTPSA)
	const SENDMAIL_TLS_REGEX = "/\(version=(TLS.*)\)(\s+for|;)/im";

	private $message_headers_done = false;
	private $icon_img;
	private $rcmail;

	function init()
	{
		$this->rcmail = rcmail::get_instance();
		$this->load_config();

		$this->add_hook('message_headers_output', array($this, 'message_headers'));
		$this->add_hook('storage_init', array($this, 'storage_init'));

		$this->include_stylesheet('tls_icon.css');

		$this->add_texts('localization/');
	}

	function get_received_header_content($Received_Header)
	{
		$Received = null;
		if (is_array($Received_Header)) {
			$ignore_n_hops = $this->rcmail->config->get('tls_icon_ignore_hops');
			if ($ignore_n_hops && count($Received_Header) > $ignore_n_hops) {
				$Received = $Received_Header[$ignore_n_hops];
			} else {
				$Received = $Received_Header[0];
			}
		} else {
			$Received = $Received_Header;
		}
		return $Received;
	}

	public function storage_init($p)
	{
		$headers = isset($p['fetch_headers']) ? $p['fetch_headers'] : '';
		$p['fetch_headers'] = trim(trim($headers) . ' ' . strtoupper('Received'));
		return $p;
	}

	public function message_headers($p)
	{
		if ($this->message_headers_done === false) {
			$this->message_headers_done = true;

			$Received_Header = isset($p['headers']->others['received']) ? $p['headers']->others['received'] : null;
			$Received = $this->get_received_header_content($Received_Header);

			if ($Received == null) {
				// There was no Received Header. Possibly an outbound mail. Do nothing.
				return $p;
			}

			if (
				preg_match_all(tls_icon::POSTFIX_TLS_REGEX, $Received, $items, PREG_PATTERN_ORDER) ||
				preg_match_all(tls_icon::SENDMAIL_TLS_REGEX, $Received, $items, PREG_PATTERN_ORDER)
			) {
				$data = $items[1][0];

				if ($this->is_weak($data)) {
					$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/weak.svg" title="' . htmlentities($data) . '" />';
				} else {
					$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="' . htmlentities($data) . '" />';
				}
			} elseif (
				preg_match_all(tls_icon::POSTFIX_LOCAL_USER_REGEX, $Received, $items, PREG_PATTERN_ORDER) ||
				preg_match_all(tls_icon::LOCAL_DELIVERY_REGEX, $Received, $items, PREG_PATTERN_ORDER)
			) {
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/blue_lock.svg" title="' . $this->gettext('internal') . '" />';
			} else {
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/unlock.svg" title="' . $this->gettext('unencrypted') . '" />';
			}
		}

		if (isset($p['output']['from'])) {
			$p['output']['from']['value'] = $this->icon_img . $p['output']['from']['value'];
		}

		return $p;
	}

	/**
	 * Determine if the TLS used was weak
	 * based on Dutch Government guidelines: 
	 * https://www.ncsc.nl/documenten/publicaties/2025/juni/01/ict-beveiligingsrichtlijnen-voor-transport-layer-security-2025-05
	 *
	 * @param string $cipherstring
	 * @return boolean
	 */
	public function is_weak($cipherstring)
	{
		// If string contains TLSv1.2
		if (preg_match('/TLSv1\.2/i', $cipherstring)) {
			$allowedCiphers = [
				// Acceptable
				'ECDHE-ECDSA-CHACHA20-POLY1305',
				'ECDHE-ECDSA-AES256-GCM-SHA384',
				'ECDHE-ECDSA-AES256-CCM',
				'ECDHE-ECDSA-AES128-GCM-SHA256',
				'ECDHE-ECDSA-AES128-CCM',
				'ECDHE-RSA-CHACHA20-POLY1305',
				'ECDHE-RSA-AES256-GCM-SHA384',
				'ECDHE-RSA-AES128-GCM-SHA256'
			];

			// If is found in the string, allow
			foreach ($allowedCiphers as $cipher) {
				if (stripos($cipherstring, $cipher) !== false) {
					return false;
				}
			}
		}

		// If string contains TLSv1.3
		if (preg_match('/TLSv1\.3/i', $cipherstring)) {
			$allowedCiphers = [
				// Good
				'AES_256_GCM_SHA384',
				'CHACHA20_POLY1305_SHA256',
				// Acceptable
				'AES_128_GCM_SHA256',
				'AES_128_CCM_SHA256'
			];

			$containsAllowedCipher = false;
			foreach ($allowedCiphers as $cipher) {
				if (stripos($cipherstring, $cipher) !== false) {
					$containsAllowedCipher = true;
					break;
				}
			}

			if (!$containsAllowedCipher) {
				return true; // No allowed cipher found, weak
			}

			// Noted after key-exchange
			$allowedKeyExchange = [
				'X25519',
				'ECDHE',
			];

			// If string contains 'key-exchange', also require one of the allowed key exchanges
			if (stripos($cipherstring, 'key-exchange') !== false) {
				$containsAllowedKeyExchange = false;
				foreach ($allowedKeyExchange as $keyExchange) {
					if (stripos($cipherstring, $keyExchange) !== false) {
						$containsAllowedKeyExchange = true;
						break;
					}
				}

				if (!$containsAllowedKeyExchange) {
					return true; // No allowed key exchange found, weak
				}
			}

			// Noted after server-signature
			$allowedAuth = [
				// Acceptable
				'ECDSA',
				'RSA-PSS',
				'ED25519',
				'ED448'
			];

			// Only used with ECDSA
			$allowedCurves = [
				// Acceptable
				'secp521r1',
				'secp384r1',
				'secp256r1',
				'brainpoolP512r1',
				'brainpoolP384r1',
				'brainpoolP256r1'
			];

			// If string contains 'server-signature', also require one of the allowed auth methods
			if (stripos($cipherstring, 'server-signature') !== false) {
				$containsAllowedAuth = false;
				foreach ($allowedAuth as $auth) {
					if (stripos($cipherstring, $auth) !== false) {
						// If ECDSA is used, also require one of the allowed curves
						if ($auth == 'ECDSA') {
							$containsAllowedCurve = false;
							foreach ($allowedCurves as $curve) {
								if (stripos($cipherstring, $curve) !== false) {
									$containsAllowedCurve = true;
									break;
								}
							}
							if (!$containsAllowedCurve) {
								return true; // No allowed curve found, weak
							}
						} else if ($auth === "RSA-PSS") {
							// If RSA-PSS is used, require at least 3072 bits
							if (preg_match('/RSA-PSS \((\d+) bits\)/i', $cipherstring, $matches)) {
								if (isset($matches[1]) && is_numeric($matches[1]) && (int)$matches[1] < 3072) {
									return true; // Too weak RSA key, weak
								}
							} else {
								return true; // No RSA key length found, weak
							}
						}

						$containsAllowedAuth = true;
						break;
					}
				}
				if (!$containsAllowedAuth) {
					return true; // No allowed auth method found, weak
				}
			}

			// If all match, TLS 1.3 verified
			return false;
		}

		// Otherwise, it's weak.
		return true;
	}
}

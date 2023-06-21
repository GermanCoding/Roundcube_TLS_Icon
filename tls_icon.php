<?php

class tls_icon extends rcube_plugin
{
	const POSTFIX_TLS_REGEX = "/using\s*(TLS[v]?\d\.?\_?\d.*)/im";
	const POSTFIX_LOCAL_REGEX = "/\(envelope-from <.*@kijdo.nl>\)/im";
	const SENDMAIL_TLS_REGEX = "/\(version=\s*(TLS[v]?\d\_?\.?\d)(?:\n|\,|\W)*cipher=(.*)\)/im";

	private $message_headers_done = false;
	private $message_id = 0;
	private $icon_img;
	private $rcmail;

	function init()
	{
		$this->rcmail = rcmail::get_instance();
		$this->load_config();

		$this->add_hook('message_headers_output', array($this, 'message_headers'));
		$this->add_hook('message_load', array($this, 'message_load'));

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

	public function message_load($p)
	{
		// Save message id for retrieving headers
		$this->message_id = $p['object']->uid;
	}

	public function message_headers($p)
	{
		if ($this->message_headers_done === false) {
			$this->message_headers_done = true;

			// Get raw message headers from storage
			$headers = $this->rcmail->get_storage()->get_raw_headers($this->message_id);

			if ($headers == null) {
				// There was no Received Header. Possibly an outbound mail. Do nothing.
				return $p;
			}

			if (preg_match_all(tls_icon::POSTFIX_TLS_REGEX, $headers, $items, PREG_PATTERN_ORDER)) {
				$data = preg_replace('/\s\s+/', '', $items[1][0]);
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="' . htmlentities($data) . '" />';
			} else if (preg_match_all(tls_icon::SENDMAIL_TLS_REGEX, $headers, $items, PREG_PATTERN_ORDER)) {
				$data = preg_replace('/\s\s+/', '', $items[1][0]) . " with cipher " . preg_replace('/\s\s+/', '', $items[2][0]);
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="' . htmlentities($data) . '" />';
			} elseif (preg_match_all(tls_icon::POSTFIX_LOCAL_REGEX, $headers, $items, PREG_PATTERN_ORDER)) {
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/blue_lock.svg" title="' . $this->gettext('internal') . '" />';
			} else {
				// TODO: Mails received from localhost but without TLS are currently flagged insecure
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/unlock.svg" title="' . $this->gettext('unencrypted') . '" />';
			}
		}

		if (isset($p['output']['subject'])) {
			$p['output']['subject']['value'] = $p['output']['subject']['value'] . $this->icon_img;
			$p['output']['subject']['html'] = 1;
		}

		return $p;
	}
}

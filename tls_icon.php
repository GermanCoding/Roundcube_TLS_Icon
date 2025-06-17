<?php

class tls_icon extends rcube_plugin
{
	const POSTFIX_TLS_REGEX = "/\(using (TLS[^()]+(?:\([^)]+\))?)\)/im";
	const POSTFIX_LOCAL_REGEX = "/\([a-zA-Z]*, from userid [0-9]*\)/im";
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

			if (preg_match_all(tls_icon::POSTFIX_TLS_REGEX, $Received, $items, PREG_PATTERN_ORDER) ||
				preg_match_all(tls_icon::SENDMAIL_TLS_REGEX, $Received, $items, PREG_PATTERN_ORDER)) {
				$data = $items[1][0];
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="' . htmlentities($data) . '" />';
			} elseif (preg_match_all(tls_icon::POSTFIX_LOCAL_REGEX, $Received, $items, PREG_PATTERN_ORDER)) {
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/blue_lock.svg" title="' . $this->gettext('internal') . '" />';
			} else {
				// TODO: Mails received from localhost but without TLS are currently flagged insecure
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/unlock.svg" title="' . $this->gettext('unencrypted') . '" />';
			}
		}

		if (isset($p['output']['subject'])) {
			$p['output']['subject']['value'] = htmlentities($p['output']['subject']['value']) . $this->icon_img;
			$p['output']['subject']['html'] = 1;
		}

		return $p;
	}
}

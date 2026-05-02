<?php

class tls_icon extends rcube_plugin
{
	const POSTFIX_TLS_REGEX = "/\(using (TLS(?:[^()]|\([^()]*\))*)\)/im";
	const POSTFIX_LOCAL_REGEX = "/\([a-zA-Z]*, from userid [0-9]*\)|\(localhost \[[^]]+\]\)/im";
	const SENDMAIL_TLS_REGEX = "/\(version=(TLS.*)\)(\s+for|;)/im";

	private $message_headers_done = false;
	private $icon_img;
	private $rcmail;

	private function asset_url($file)
	{
		$file = ltrim($file, '/');
		$url = '';

		if (method_exists($this, 'url')) {
			$url = $this->url($file);
		} elseif (!empty($this->urlbase) && is_string($this->urlbase)) {
			$url = rtrim($this->urlbase, '/') . '/' . $file;
		} else {
			$url = 'plugins/tls_icon/' . $file;
		}

		if (preg_match('/^(?:[a-z][a-z0-9+.-]*:|\\/)/i', $url)) {
			return $url;
		}

		if ($this->rcmail && isset($this->rcmail->output) && method_exists($this->rcmail->output, 'abs_url')) {
			return $this->rcmail->output->abs_url($url);
		}

		return $url;
	}

	private function icon_tag($file, $title)
	{
		return '<img class="lock_icon" src="' . $this->icon_src($file) . '" title="' . $title . '" />';
	}

	private function icon_src($file)
	{
		$file = ltrim($file, '/');
		$path = __DIR__ . '/' . $file;

		if (is_readable($path) && preg_match('/\.svg$/i', $file)) {
			$svg = @file_get_contents($path);

			if (is_string($svg) && $svg !== '') {
				return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
			}
		}

		return $this->asset_url($file);
	}

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
				$this->icon_img .= $this->icon_tag('lock.svg', htmlentities($data));
			} elseif (preg_match_all(tls_icon::POSTFIX_LOCAL_REGEX, $Received, $items, PREG_PATTERN_ORDER)) {
				$this->icon_img .= $this->icon_tag('blue_lock.svg', $this->gettext('internal'));
			} else {
				// TODO: Mails received from localhost but without TLS are currently flagged insecure
				$this->icon_img .= $this->icon_tag('unlock.svg', $this->gettext('unencrypted'));
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

<?php

class tls_icon extends rcube_plugin
{	
	private $message_headers_done = false;
	private $icon_img;

	function init()
	{
		$rcmail = rcmail::get_instance();
		$layout = $rcmail->config->get('layout');

		$this->add_hook('message_headers_output', array($this, 'message_headers'));
		$this->add_hook('storage_init', array($this, 'storage_init'));
		
		$this->include_stylesheet('tls_icon.css');
	}
	
	public function storage_init($p)
	{
		$p['fetch_headers'] = trim($p['fetch_headers'] . ' ' . strtoupper('Received'));
		return $p;
	}
	
	public function message_headers($p)
	{		
		if($this->message_headers_done===false)
		{
			$this->message_headers_done = true;

			$Received_Header = $p['headers']->others['received'];
			if(is_array($Received_Header)) {
				$Received = $Received_Header[0];
			} else {
				$Received = $Received_Header;
			}
			
			if($Received == null) {
				// There was no Received Header. Possibly an outbound mail. Do nothing.
				return $p;
			}
			
			if ( preg_match_all('/\(using TLS.*.*\) \(/im', $Received, $items, PREG_PATTERN_ORDER) ) {
				$data = $items[0][0];

				$needle = "(using ";
				$pos = strpos($data, $needle);
				$data = substr_replace($data, "", $pos, strlen($needle));

				$needle = ") (";
				$pos = strrpos($data, $needle);
				$data = substr_replace($data, "", $pos, strlen($needle));
				
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="'. htmlentities($data) .'" />';
			} else if(preg_match_all('/\([a-zA-Z]*, from userid [0-9]*\)/im', $Received, $items, PREG_PATTERN_ORDER)){
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/blue_lock.svg" title="Mail was internal"';
			} 
			else {
				$this->icon_img .= '<img class="lock_icon" src="plugins/tls_icon/unlock.svg" title="Message received over an unencrypted connection!"';
			}
		}

		if(isset($p['output']['subject']))
		{
			$p['output']['subject']['value'] = $p['output']['subject']['value'] . $this->icon_img;
			$p['output']['subject']['html'] = 1;
		}

		return $p;
	}
}

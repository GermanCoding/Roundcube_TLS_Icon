<?php

namespace RouncubeTlsIcon\Tests;

require_once __DIR__ . '/rcube_plugin.php';

use tls_icon;
use PHPUnit\Framework\TestCase;

final class TlsIconTest extends TestCase
{
	public function testInstance()
	{
		$o = new tls_icon();
		$this->assertInstanceOf('tls_icon', $o);
	}
}

<?php

namespace RouncubeTlsIcon\Tests;

require_once __DIR__ . '/rcube_plugin.php';
require_once __DIR__ . '/rcmail.php';

use tls_icon;
use rcmail;
use PHPUnit\Framework\TestCase;

final class TlsIconTest extends TestCase
{

	/** @var string */
	private $strUnEnCrypted = '<img class="lock_icon" src="plugins/tls_icon/unlock.svg" title="Message received over an unencrypted connection!" />';

	/** @var string */
	private $strCryptedTlsv12 = '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="TLSv1.2" />';

	/** @var string */
	private $strCryptedTlsv12WithCipher = '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits)" />';

	/** @var string */
	private $strInternal = '<img class="lock_icon" src="plugins/tls_icon/blue_lock.svg" title="Mail was internal" />';

	/** @var string */
	private $strSendmailCryptedTlsv13WithCipherNoVerify = '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="TLSv1.3 cipher=TLS_AES_256_GCM_SHA384 bits=256 verify=NO" />';

	/** @var string */
	private $strSendmailCryptedTlsv12WithCipherVerify = '<img class="lock_icon" src="plugins/tls_icon/lock.svg" title="TLSv1.2 cipher=ECDHE-RSA-AES256-GCM-SHA384 bits=256 verify=OK" />';


	public function testInstance()
	{
		$o = new tls_icon();
		$this->assertInstanceOf('tls_icon', $o);
	}

	public function testStorage_Init()
	{
		$o = new tls_icon();
		$this->assertSame([
			'fetch_headers' => ' RECEIVED'
		], $o->storage_init([]));
		$this->assertSame([
			'fetch_headers' => ' RECEIVED'
		], $o->storage_init(['fetch_headers' => null]));
		$this->assertSame([
			'fetch_headers' => 'foo bar RECEIVED'
		], $o->storage_init(['fetch_headers' => 'foo bar']));
		$this->assertSame([
			'fetch_headers' => 'spaces RECEIVED'
		], $o->storage_init(['fetch_headers' => 'spaces   ']));
	}

	public function testMessageHeadersNothing()
	{
		$o = new tls_icon();
		$this->assertSame([], $o->message_headers([]));
	}

	public function testMessageHeadersNoMatching()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'my header',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strUnEnCrypted,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'my header',
				]
			]
		], $headersProcessed);
	}

	public function testMessageHeadersTlsWithCipher()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from smtp.github.com (out-21.smtp.github.com [192.30.252.204])
					(using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits)) (No client certificate requested)
					by mail.example.org (Postfix) with ESMTPS id 46B4C497C2
					for <test@mail.example.org>; Sat, 9 Jul 2022 14:03:01 +0000 (UTC)',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strCryptedTlsv12WithCipher,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from smtp.github.com (out-21.smtp.github.com [192.30.252.204])
					(using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits)) (No client certificate requested)
					by mail.example.org (Postfix) with ESMTPS id 46B4C497C2
					for <test@mail.example.org>; Sat, 9 Jul 2022 14:03:01 +0000 (UTC)',
				]
			]
		], $headersProcessed);
	}

	public function testMessageHeadersTls()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from smtp.github.com (out-21.smtp.github.com [192.30.252.204])
					(using TLSv1.2) (No client certificate requested)
					by mail.example.org (Postfix) with ESMTPS id 46B4C497C2
					for <test@mail.example.org>; Sat, 9 Jul 2022 14:03:01 +0000 (UTC)',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strCryptedTlsv12,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from smtp.github.com (out-21.smtp.github.com [192.30.252.204])
					(using TLSv1.2) (No client certificate requested)
					by mail.example.org (Postfix) with ESMTPS id 46B4C497C2
					for <test@mail.example.org>; Sat, 9 Jul 2022 14:03:01 +0000 (UTC)',
				]
			]
		], $headersProcessed);
	}

	public function testMessageHeadersInternal()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'by aaa.bbb.ccc (Postfix, from userid 0)
					id A70248414D5; Sun, 26 Apr 2020 16:49:01 +0200 (CEST)',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strInternal,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'by aaa.bbb.ccc (Postfix, from userid 0)
					id A70248414D5; Sun, 26 Apr 2020 16:49:01 +0200 (CEST)',
				]
			]
		], $headersProcessed);
	}

	public function testMessageHeadersMultiFromWithConfig()
	{
		$inputHeaders = [
			'from mail.example.org by mail.example.org with LMTP id pLzoBVClyGIiVgAA3BZZyA (envelope-from <bounces@bounces.example.org>) for <test@example.org>; Fri, 08 Jul 2022 21:44:48 +0000',
			'from localhost (localhost [127.0.0.1]) by mail.example.org (Postfix) with ESMTP id 0D33249414 for <test@example.org>; Fri,  8 Jul 2022 21:44:48 +0000 (UTC)',
			'from xxxx-ord.mtasv.net (xxxx-ord.mtasv.net [255.255.255.255]) (using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits)) (No client certificate requested) by mail.example.org (Postfix) with ESMTPS id 73C3B461AF for <test@example.fr>; Fri,  8 Jul 2022 21:44:39 +0000 (UTC)',
			'by xxxx-ord.mtasv.net id hp2il427tk41 for <test@example.fr>; Fri, 8 Jul 2022 17:44:41 -0400 (envelope-from <bounces@bounces.example.org>)',
		];

		$o = new tls_icon();
		$o->init();
		rcmail::get_instance()->config->set('tls_icon_ignore_hops', 2);
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => $inputHeaders,
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strCryptedTlsv12WithCipher,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => $inputHeaders,
				]
			]
		], $headersProcessed);
	}

	public function testMessageHeadersMultiFromWithBadConfig()
	{
		$inputHeaders = [
			'from mail.example.org by mail.example.org with LMTP id pLzoBVClyGIiVgAA3BZZyA (envelope-from <bounces@bounces.example.org>) for <test@example.org>; Fri, 08 Jul 2022 21:44:48 +0000',
			'from localhost (localhost [127.0.0.1]) by mail.example.org (Postfix) with ESMTP id 0D33249414 for <test@example.org>; Fri,  8 Jul 2022 21:44:48 +0000 (UTC)',
			'from xxxx-ord.mtasv.net (xxxx-ord.mtasv.net [255.255.255.255]) (using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits)) (No client certificate requested) by mail.example.org (Postfix) with ESMTPS id 73C3B461AF for <test@example.fr>; Fri,  8 Jul 2022 21:44:39 +0000 (UTC)',
			'by xxxx-ord.mtasv.net id hp2il427tk41 for <test@example.fr>; Fri, 8 Jul 2022 17:44:41 -0400 (envelope-from <bounces@bounces.example.org>)',
		];

		$o = new tls_icon();
		$o->init();
		rcmail::get_instance()->config->set('tls_icon_ignore_hops', 1);
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => $inputHeaders,
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strUnEnCrypted,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => $inputHeaders,
				]
			]
		], $headersProcessed);
	}

	public function testSendmailTLS13NoVerify()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from 69-171-232-143.mail-mail.facebook.com (69-171-232-143.mail-mail.facebook.com [69.171.232.143])
					by mail.aegee.org (8.17.1/8.17.1) with ESMTPS id 2BI73F8b1489360
					(version=TLSv1.3 cipher=TLS_AES_256_GCM_SHA384 bits=256 verify=NO)
					for <my@address>; Sun, 18 Dec 2022 07:03:16 GMT',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strSendmailCryptedTlsv13WithCipherNoVerify,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from 69-171-232-143.mail-mail.facebook.com (69-171-232-143.mail-mail.facebook.com [69.171.232.143])
					by mail.aegee.org (8.17.1/8.17.1) with ESMTPS id 2BI73F8b1489360
					(version=TLSv1.3 cipher=TLS_AES_256_GCM_SHA384 bits=256 verify=NO)
					for <my@address>; Sun, 18 Dec 2022 07:03:16 GMT',
				]
			]
		], $headersProcessed);
	}

	public function testSendmailTLS12WithVerify()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from smtp.github.com (out-18.smtp.github.com [192.30.252.201])
					by mail.aegee.org (8.17.1/8.17.1) with ESMTPS id 2BGMf4uY685293
					(version=TLSv1.2 cipher=ECDHE-RSA-AES256-GCM-SHA384 bits=256 verify=OK)
					for <my@address>; Fri, 16 Dec 2022 22:41:05 GMT',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strSendmailCryptedTlsv12WithCipherVerify,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from smtp.github.com (out-18.smtp.github.com [192.30.252.201])
					by mail.aegee.org (8.17.1/8.17.1) with ESMTPS id 2BGMf4uY685293
					(version=TLSv1.2 cipher=ECDHE-RSA-AES256-GCM-SHA384 bits=256 verify=OK)
					for <my@address>; Fri, 16 Dec 2022 22:41:05 GMT',
				]
			]
		], $headersProcessed);
	}

	public function testSendmailTLS13MultipleRecipients()
	{
		$o = new tls_icon();
		$headersProcessed = $o->message_headers([
			'output' => [
				'subject' => [
					'value' => 'Sent to you',
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from mout.kundenserver.de (mout.kundenserver.de [212.227.126.134])
					by mail.aegee.org (8.17.1/8.17.1) with ESMTPS id 2BLGrgYw3602565
					(version=TLSv1.3 cipher=TLS_AES_256_GCM_SHA384 bits=256 verify=NO);
					Wed, 21 Dec 2022 16:53:42 GMT',
				]
			]
		]);
		$this->assertEquals([
			'output' => [
				'subject' => [
					'value' => 'Sent to you' . $this->strSendmailCryptedTlsv13WithCipherNoVerify,
					'html' => 1,
				],
			],
			'headers' => (object)[
				'others' => [
					'received' => 'from mout.kundenserver.de (mout.kundenserver.de [212.227.126.134])
					by mail.aegee.org (8.17.1/8.17.1) with ESMTPS id 2BLGrgYw3602565
					(version=TLSv1.3 cipher=TLS_AES_256_GCM_SHA384 bits=256 verify=NO);
					Wed, 21 Dec 2022 16:53:42 GMT',
				]
			]
		], $headersProcessed);
	}
}

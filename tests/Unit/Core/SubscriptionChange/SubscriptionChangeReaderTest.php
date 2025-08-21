<?php

declare(strict_types=1);

namespace OCA\NextPod\Tests\Unit\Core\SubscriptionChange;

use OCA\NextPod\Core\SubscriptionChange\SubscriptionChangesReader;
use Test\TestCase;

class SubscriptionChangeReaderTest extends TestCase {
	public function testMapUrlsToSubscriptionChanges(): void {
		$subscriptionChange = SubscriptionChangesReader::mapToSubscriptionsChanges(["https://feeds.megaphone.fm/HSW8286374095", "https://feeds.megaphone.fm/another"], true);
		$this->assertCount(2, $subscriptionChange);
		$this->assertSame("https://feeds.megaphone.fm/HSW8286374095", $subscriptionChange[0]->getUrl());
		$this->assertSame("https://feeds.megaphone.fm/another", $subscriptionChange[1]->getUrl());
	}


	public function testNonUrisAreOmmited(): void {
		$subscriptionChange = SubscriptionChangesReader::mapToSubscriptionsChanges([
			"https://feeds.megaphone.fm/HSW8286374095",
			"antennapod_local:content://com.android.externalstorage.documents/tree/home:podcast"
		], true);
		$this->assertCount(1, $subscriptionChange);
		$this->assertSame("https://feeds.megaphone.fm/HSW8286374095", $subscriptionChange[0]->getUrl());
	}

}

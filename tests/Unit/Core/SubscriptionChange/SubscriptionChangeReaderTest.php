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
		$this->assertTrue($subscriptionChange[0]->isSubscribed());
		$this->assertTrue($subscriptionChange[1]->isSubscribed());
	}


	public function testNonUrisAreOmmited(): void {
		$subscriptionChange = SubscriptionChangesReader::mapToSubscriptionsChanges([
			"https://feeds.megaphone.fm/HSW8286374095",
			"antennapod_local:content://com.android.externalstorage.documents/tree/home:podcast"
		], true);
		$this->assertCount(1, $subscriptionChange);
		$this->assertSame("https://feeds.megaphone.fm/HSW8286374095", $subscriptionChange[0]->getUrl());
	}

	public function testMapUrlsToUnsubscribedChanges(): void {
		$subscriptionChanges = SubscriptionChangesReader::mapToSubscriptionsChanges([
			"https://feeds.example.com/podcast.xml",
		], false);

		$this->assertCount(1, $subscriptionChanges);
		$this->assertFalse($subscriptionChanges[0]->isSubscribed());
	}

	public function testEmptyUrlListReturnsNoChanges(): void {
		$this->assertSame([], SubscriptionChangesReader::mapToSubscriptionsChanges([], true));
	}

}

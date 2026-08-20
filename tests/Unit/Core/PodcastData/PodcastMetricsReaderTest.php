<?php

declare(strict_types=1);

namespace OCA\NextPod\Tests\Unit\Core\PodcastData;

use DateTime;
use OCA\NextPod\Core\EpisodeAction\EpisodeAction;
use OCA\NextPod\Core\PodcastData\PodcastMetricsReader;
use OCA\NextPod\Db\EpisodeAction\EpisodeActionRepository;
use OCA\NextPod\Db\SubscriptionChange\SubscriptionChangeEntity;
use OCA\NextPod\Db\SubscriptionChange\SubscriptionChangeRepository;
use Test\TestCase;

class PodcastMetricsReaderTest extends TestCase {
	public function testMetricsAggregateActionsForSubscribedPodcasts(): void {
		$episodeActionRepository = $this->createMock(EpisodeActionRepository::class);
		$episodeActionRepository->expects($this->once())
			->method('findAll')
			->with(0, 'alice')
			->willReturn([
				$this->action('https://example.com/feed.xml', 'PLAY', 120),
				$this->action('https://example.com/feed.xml', 'play', 30),
				$this->action('https://example.com/feed.xml', 'DOWNLOAD', -1),
				$this->action('https://example.com/feed.xml', 'NEW', -1),
				$this->action('https://example.com/feed.xml', 'DELETE', -1),
				$this->action('https://example.com/feed.xml', 'FLATTR', -1),
				$this->action('https://unsubscribed.example.com/feed.xml', 'PLAY', 999),
			]);

		$subscriptionChangeRepository = $this->createMock(SubscriptionChangeRepository::class);
		$subscriptionChangeRepository->expects($this->once())
			->method('findAllSubscribed')
			->with(
				$this->callback(static fn (DateTime $since): bool => $since->getTimestamp() === 0),
				'alice'
			)
			->willReturn([$this->subscription('https://example.com/feed.xml')]);

		$metrics = (new PodcastMetricsReader(
			$subscriptionChangeRepository,
			$episodeActionRepository
		))->metrics('alice');

		$this->assertCount(1, $metrics);
		$this->assertSame([
			'url' => 'https://example.com/feed.xml',
			'listenedSeconds' => 150,
			'actionCounts' => [
				'delete' => 1,
				'download' => 1,
				'flattr' => 1,
				'new' => 1,
				'play' => 2,
			],
		], $metrics[0]->toArray());
	}

	public function testMetricsIncludeSubscriptionsWithoutActions(): void {
		$episodeActionRepository = $this->createMock(EpisodeActionRepository::class);
		$episodeActionRepository->method('findAll')->willReturn([]);

		$subscriptionChangeRepository = $this->createMock(SubscriptionChangeRepository::class);
		$subscriptionChangeRepository->method('findAllSubscribed')->willReturn([
			$this->subscription('https://example.com/feed.xml'),
		]);

		$metrics = (new PodcastMetricsReader(
			$subscriptionChangeRepository,
			$episodeActionRepository
		))->metrics('alice');

		$this->assertSame([
			'url' => 'https://example.com/feed.xml',
			'listenedSeconds' => 0,
			'actionCounts' => [
				'delete' => 0,
				'download' => 0,
				'flattr' => 0,
				'new' => 0,
				'play' => 0,
			],
		], $metrics[0]->jsonSerialize());
	}

	private function action(string $podcast, string $action, int $position): EpisodeAction {
		return new EpisodeAction(
			$podcast,
			'https://example.com/episode.mp3',
			$action,
			'2026-08-20T12:00:00',
			0,
			$position,
			1000,
			null,
			null
		);
	}

	private function subscription(string $url): SubscriptionChangeEntity {
		$subscription = new SubscriptionChangeEntity();
		$subscription->setUrl($url);
		$subscription->setSubscribed(true);
		$subscription->setUserId('alice');
		return $subscription;
	}
}

<?php

declare(strict_types=1);

namespace OCA\NextPod\Tests\Unit\Core\EpisodeAction;

use OCA\NextPod\Core\EpisodeAction\EpisodeActionExtraData;
use Test\TestCase;

class EpisodeActionExtraDataTest extends TestCase {
	public function testParseRssXmlFindsEpisodeAndPrefersContentDescription(): void {
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
	<channel>
		<title>Example Podcast</title>
		<item>
			<title>Other episode</title>
			<enclosure url="https://cdn.example.com/other.mp3" />
		</item>
		<item>
			<title>Selected episode</title>
			<link>https://example.com/episodes/selected</link>
			<enclosure url="https://cdn.example.com/selected.mp3" />
			<itunes:image href="https://example.com/selected.jpg" />
			<description>Plain description</description>
			<content:encoded><![CDATA[Read <a href="https://example.com/more">more</a>.]]></content:encoded>
		</item>
	</channel>
</rss>
XML;

		$data = EpisodeActionExtraData::parseRssXml(
			$xml,
			'https://cdn.example.com/selected.mp3',
			1337
		);

		$this->assertSame([
			'podcastName' => 'Example Podcast',
			'episodeUrl' => 'https://cdn.example.com/selected.mp3',
			'episodeName' => 'Selected episode',
			'episodeLink' => 'https://example.com/episodes/selected',
			'episodeImage' => 'https://example.com/selected.jpg',
			'episodeDescription' => 'Read <a class="description-link" target="_blank" href="https://example.com/more">more</a>.',
			'fetchedAtUnix' => 1337,
		], $data->toArray());
	}

	public function testParseRssXmlMatchesChangingQueryStringAndUsesChannelImage(): void {
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
	<channel>
		<title>Example Podcast</title>
		<image><url>https://example.com/podcast.jpg</url></image>
		<item>
			<title>Selected episode</title>
			<link>https://example.com/episodes/selected</link>
			<enclosure url="https://cdn.example.com/selected.mp3?token=old" />
			<description>Episode description</description>
		</item>
	</channel>
</rss>
XML;

		$data = EpisodeActionExtraData::parseRssXml(
			$xml,
			'https://cdn.example.com/selected.mp3?token=new',
			1337
		);

		$this->assertSame('Selected episode', $data->getEpisodeName());
		$this->assertSame('https://example.com/podcast.jpg', $data->getEpisodeImage());
		$this->assertSame('Episode description', $data->toArray()['episodeDescription']);
	}

	public function testParseRssXmlReturnsPodcastDataWhenEpisodeIsNotFound(): void {
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
	<channel>
		<title>Example Podcast</title>
		<item>
			<title>Other episode</title>
			<enclosure url="https://cdn.example.com/other.mp3" />
		</item>
	</channel>
</rss>
XML;

		$data = EpisodeActionExtraData::parseRssXml(
			$xml,
			'https://cdn.example.com/missing.mp3',
			1337
		);

		$this->assertSame('Example Podcast', $data->getPodcastName());
		$this->assertNull($data->getEpisodeName());
		$this->assertNull($data->getEpisodeLink());
		$this->assertNull($data->getEpisodeImage());
	}

	public function testArrayRoundTripPreservesData(): void {
		$expected = [
			'podcastName' => 'Example Podcast',
			'episodeUrl' => 'https://cdn.example.com/episode.mp3',
			'episodeName' => 'Episode',
			'episodeLink' => 'https://example.com/episode',
			'episodeImage' => null,
			'episodeDescription' => 'Description',
			'fetchedAtUnix' => 1337,
		];

		$data = EpisodeActionExtraData::fromArray($expected);

		$this->assertSame($expected, $data->jsonSerialize());
		$this->assertSame('https://cdn.example.com/episode.mp3', (string)$data);
	}
}

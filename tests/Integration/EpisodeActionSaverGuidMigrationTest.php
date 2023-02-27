<?php
declare(strict_types=1);

namespace tests\Integration;

use OCA\NextPod\Core\EpisodeAction\EpisodeActionSaver;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use Test\TestCase;

/**
 * @group DB
 */
class EpisodeActionSaverGuidMigrationTest extends TestCase
{
	private const USER_ID_0 = "testuser0";

	private IAppContainer $container;

	public function setUp(): void {
		parent::setUp();
		$app = new App('nextpod');
		$this->container = $app->getContainer();
	}

	public function testCreateEpisodeActionWithoutGuidThenCreateAgainWithGuid() : void
	{
		/** @var EpisodeActionSaver $episodeActionSaver */
		$episodeActionSaver = $this->container->get(EpisodeActionSaver::class);

		$episodeUrl = uniqid("test_https://dts.podtrac.com/redirect.mp3/chrt.fm/track");
		$guid = uniqid("test_gid://art19-episode-locator/V0/Ktd");

		$savedEpisodeActionEntityWithoutGuid = $episodeActionSaver->saveEpisodeActions(
			[["podcast" => 'https://rss.art19.com/dr-death-s3-miracle-man',	"episode" => $episodeUrl, "action" => "PLAY", "timestamp" => "2021-08-22T23:58:56", "started" => 47, "position" => 54, "total" => 2252]],
			self::USER_ID_0
		)[0];

		$savedEpisodeActionEntityWithGuid = $episodeActionSaver->saveEpisodeActions(
			[["podcast" => 'https://rss.art19.com/dr-death-s3-miracle-man',	"episode" => $episodeUrl, "guid" => $guid, "action" => "PLAY", "timestamp" => "2021-08-22T23:58:56", "started" => 47, "position" => 54, "total" => 2252]],
			self::USER_ID_0
		)[0];

		self::assertSame($savedEpisodeActionEntityWithoutGuid->getId(), $savedEpisodeActionEntityWithGuid->getId());
	}

	public function testCreateEpisodeActionWithGuidThenCreateAgainWithGuidButDifferentEpisodeUrl() : void
	{
		/** @var EpisodeActionSaver $episodeActionSaver */
		$episodeActionSaver = $this->container->get(EpisodeActionSaver::class);

		$episodeUrl = uniqid("test_https://dts.podtrac.com/redirect.mp3/chrt.fm/track");
		$guid = uniqid("test_gid://art19-episode-locator/V0/Ktd");

		$savedEpisodeActionEntity = $episodeActionSaver->saveEpisodeActions(
			[["podcast" => 'https://rss.art19.com/dr-death-s3-miracle-man',	"episode" => $episodeUrl, "guid" => $guid, "action" => "PLAY", "timestamp" => "2021-08-22T23:58:56", "started" => 47, "position" => 54, "total" => 2252]],
			self::USER_ID_0
		)[0];

		$savedEpisodeActionEntityWithDifferentEpisodeUrl = $episodeActionSaver->saveEpisodeActions(
			[["podcast" => 'https://rss.art19.com/dr-death-s3-miracle-man',	"episode" => $episodeUrl . "_different", "guid" => $guid, "action" => "PLAY", "timestamp" => "2021-08-22T23:58:56", "started" => 47, "position" => 54, "total" => 2252]],
			self::USER_ID_0
		)[0];

		self::assertSame($savedEpisodeActionEntity->getId(), $savedEpisodeActionEntityWithDifferentEpisodeUrl->getId());
	}

}

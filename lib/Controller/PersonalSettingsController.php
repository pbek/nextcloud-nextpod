<?php
declare(strict_types=1);

namespace OCA\NextPod\Controller;

use OCA\NextPod\Core\EpisodeAction\EpisodeActionReader;
use OCA\NextPod\Core\PodcastData\PodcastDataReader;
use OCA\NextPod\Core\PodcastData\PodcastMetricsReader;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PersonalSettingsController extends Controller {

	private string $userId;
	private EpisodeActionReader $actionsReader;
	private PodcastMetricsReader $metricsReader;
	private PodcastDataReader $dataReader;

	public function __construct(
		string $AppName,
		IRequest $request,
		?string $UserId,
		PodcastMetricsReader $metricsReader,
		PodcastDataReader $dataReader,
		EpisodeActionReader $actionsReader
	) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId ?? '';
		$this->metricsReader = $metricsReader;
		$this->dataReader = $dataReader;
		$this->actionsReader = $actionsReader;
	}

	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return JSONResponse
	 */
	public function metrics(): JSONResponse {
		$actions = $this->actionsReader->actions($this->userId, 'timestamp_epoch', 'DESC');
		$metrics = $this->metricsReader->metrics($this->userId);
		return new JSONResponse([
			'actions' => $actions,
			'subscriptions' => $metrics,
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $url
	 * @return JsonResponse
	 */
	public function podcastData(string $url = ''): JsonResponse {
		if ($url === '') {
			return new JSONResponse([
				'message' => "Missing query parameter 'url'.",
				'data' => null,
			], Http::STATUS_BAD_REQUEST);
		}
		return new JsonResponse([
			'data' => $this->dataReader->getCachedOrFetchPodcastData($url, $this->userId),
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $episodeUrl
	 * @return JsonResponse
	 */
	public function actionExtraData(string $episodeUrl = ''): JsonResponse {
		if ($episodeUrl === '') {
			return new JSONResponse([
				'message' => "Missing query parameter 'episodeUrl'.",
				'data' => null,
			], Http::STATUS_BAD_REQUEST);
		}
		return new JsonResponse([
			'data' => $this->actionsReader->getCachedOrFetchActionExtraData($episodeUrl, $this->userId),
		]);
	}
}

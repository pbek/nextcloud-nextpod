<?php

namespace OCA\NextPod\Controller;

use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;

class PageController extends Controller {
	private IAppManager $appManager;
	private IInitialState $initialState;

	public function __construct(
		string $appName,
		IRequest $request,
		IAppManager $appManager,
		IInitialState $initialState
	) {
		parent::__construct($appName, $request);

		$this->appManager = $appManager;
		$this->initialState = $initialState;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		$this->initialState->provideInitialState('has-notes-app', $this->appManager->isEnabledForUser('notes') === true);
		$response = new TemplateResponse('nextpod', 'main', []);

		// Set CSP to allow images and media from anywhere
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedImageDomain('*')
			->addAllowedMediaDomain('*')
			->addAllowedConnectDomain('*');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}

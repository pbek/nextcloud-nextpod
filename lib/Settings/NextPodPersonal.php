<?php

declare(strict_types=1);

namespace OCA\NextPod\Settings;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class NextPodPersonal implements ISettings {

	public function getForm(): TemplateResponse {
		$response = new TemplateResponse('nextpod', 'settings/personal', []);

		// Try to set CSP to allow images and media from anywhere
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedImageDomain('*')
			->addAllowedMediaDomain('*')
			->addAllowedConnectDomain('*');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	public function getSection(): string {
		return 'nextpod';
	}

	public function getPriority(): int {
		return 198;
	}
}

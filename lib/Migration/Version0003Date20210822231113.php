<?php
declare(strict_types=1);

namespace OCA\NextPod\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;

class Version0003Date20210822231113 extends \OCP\Migration\SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('nextpod_episode_action');
		$table->changeColumn('action', ['length' => 10]);

		return $schema;
	}
}

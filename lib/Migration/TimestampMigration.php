<?php
declare(strict_types=1);

namespace OCA\NextPod\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use Safe\DateTime;

class TimestampMigration implements \OCP\Migration\IRepairStep
{
	private IDBConnection $db;

	public function __construct(IDBConnection $db)
	{
		$this->db = $db;
	}

	/**
     * @inheritDoc
     */
    public function getName() : string
    {
        return "Migrate timestamp values to integer to store unix epoch";
    }

    /**
     * @inheritDoc
     */
    public function run(IOutput $output)
    {
		$queryTimestamps = 'SELECT id, timestamp FROM `*PREFIX*nextpod_episode_action` WHERE timestamp_epoch = 0';
		$timestamps = $this->db->executeQuery($queryTimestamps)->fetchAll();

		$result = 0;

		foreach ($timestamps as $timestamp) {
			$timestampEpoch = (new DateTime($timestamp["timestamp"]))->format("U");
			$sql = 'UPDATE `*PREFIX*nextpod_episode_action` '
				. 'SET `timestamp_epoch` = ' . $timestampEpoch . ' '
				. 'WHERE `id` = ' . $timestamp["id"];

			$result += $this->db->executeUpdate($sql);

		}

		return $result;
    }

}

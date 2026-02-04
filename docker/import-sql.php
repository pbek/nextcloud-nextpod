#!/usr/bin/env php
<?php

/**
 * Import SQL file into SQLite database
 * Usage: php import-sql.php <database-path> <sql-file-path>
 */

if ($argc < 3) {
	echo "Usage: php import-sql.php <database-path> <sql-file-path>\n";
	exit(1);
}

$dbPath = $argv[1];
$sqlFile = $argv[2];

if (!file_exists($sqlFile)) {
	echo "Error: SQL file not found: $sqlFile\n";
	exit(1);
}

try {
	// Open SQLite database
	$db = new PDO("sqlite:$dbPath");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// Read SQL file
	$sql = file_get_contents($sqlFile);
	
	// Convert MySQL backticks to SQLite double quotes
	$sql = str_replace('`', '"', $sql);
	
	// Execute the SQL
	$db->exec($sql);
	
	echo "Successfully imported $sqlFile into $dbPath\n";
	
	// Show statistics
	echo "\nDatabase statistics:\n";
	echo "----------------------------------------\n";
	
	$tables = ['oc_gpodder_subscriptions', 'oc_gpodder_episode_action'];
	foreach ($tables as $table) {
		try {
			$stmt = $db->query("SELECT COUNT(*) FROM \"$table\"");
			$count = $stmt->fetchColumn();
			echo "$table: $count rows\n";
		} catch (Exception $e) {
			echo "$table: table does not exist or error\n";
		}
	}
	echo "----------------------------------------\n";

} catch (Exception $e) {
	echo "Error: " . $e->getMessage() . "\n";
	exit(1);
}

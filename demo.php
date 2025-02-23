<?php

use Dotenv\Dotenv;
use PlexDNS\Service;

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get general API key and provider
$apiKey = $_ENV['API_KEY'] ?? null;
$provider = $_ENV['PROVIDER'] ?? null;

// Get provider-specific configurations
$bindip = $_ENV['BIND_IP'] ?? '127.0.0.1';
$powerdnsip = $_ENV['POWERDNS_IP'] ?? '127.0.0.1';

// ClouDNS authentication
$cloudnsAuthId = $_ENV['AUTH_ID'] ?? null;
$cloudnsAuthPassword = $_ENV['AUTH_PASSWORD'] ?? null;

if (!$apiKey || !$provider) {
    die("Error: Missing required environment variables in .env file (API_KEY or PROVIDER)\n");
}

// If using ClouDNS, ensure credentials are set
if ($provider === 'ClouDNS' && (!$cloudnsAuthId || !$cloudnsAuthPassword)) {
    die("Error: Missing ClouDNS credentials (AUTH_ID and AUTH_PASSWORD) in .env\n");
}

// Database configuration
$dbType = $_ENV['DB_TYPE'] ?? 'mysql'; // Default to MySQL
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbName = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASS'] ?? '';
$sqlitePath = __DIR__ . '/database.sqlite';

if ($dbType !== 'sqlite' && (!$dbName || !$username || !$password)) {
    die("Error: Missing required database configuration in .env file\n");
}

$logFilePath = '/var/log/plexdns/plexdns.log';
$log = setupPlexLogger($logFilePath, 'PlexDNS');
$log->info('job started.');

try {
    if ($dbType === 'mysql') {
        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
    } elseif ($dbType === 'pgsql') {
        $dsn = "pgsql:host=$host;dbname=$dbName";
    } elseif ($dbType === 'sqlite') {
        $dsn = "sqlite:$sqlitePath";
    } else {
        throw new Exception("Unsupported database type: $dbType");
    }

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
    ]);

    if ($dbType === 'sqlite') {
        $pdo->exec("PRAGMA foreign_keys = ON;"); // Enable foreign key constraints for SQLite
    }

    // Initialize the Service with the database connection
    $service = new Service($pdo);

    // Step 1: Install database structure
    echo "Installing database structure...\n";
    $service->install();

    // Step 2: Create a domain
    echo "Creating a domain...\n";
    $domainOrder = [
        'client_id' => 1,
        'config' => json_encode(['domain_name' => 'example.com', 'provider' => $provider, 'apikey' => $apiKey]),
    ];
    $domain = $service->createDomain($domainOrder);
    print_r($domain);

    // Step 3: Add a DNS record
    echo "Adding a DNS record...\n";
    $recordData = [
        'domain_name' => 'example.com',
        'record_name' => 'www',
        'record_type' => 'A',
        'record_value' => '192.168.1.1',
        'record_ttl' => 3600,
        'record_priority' => 10, // Optional
        'provider' => $provider,
        'apikey' => $apiKey
    ];
    $recordId = $service->addRecord($recordData);
    echo "DNS record added successfully.\n";

    // Step 4: Update a DNS record
    echo "Updating a DNS record...\n";
    $updateData = [
        'domain_name' => 'example.com',
        'record_id' => $recordId,
        'record_name' => 'www',
        'record_type' => 'A',
        'record_value' => '192.168.1.2',
        'record_ttl' => 7200,
        'provider' => $provider,
        'apikey' => $apiKey
    ];
    $service->updateRecord($updateData);
    echo "DNS record updated successfully.\n";

     // Step 5: Delete a DNS record
    echo "Deleting a DNS record...\n";
    $deleteData = [
        'domain_name' => 'example.com',
        'record_id' => $recordId,
        'record_name' => 'www',
        'record_type' => 'A',
        'record_value' => '192.168.1.2',
        'provider' => $provider,
        'apikey' => $apiKey
    ];
    $service->delRecord($deleteData);
    echo "DNS record deleted successfully.\n";

    // Step 6: Delete a domain
    echo "Deleting a domain...\n";
    $service->deleteDomain(['config' => json_encode(['domain_name' => 'example.com', 'provider' => $provider, 'apikey' => $apiKey])]);
    echo "Domain deleted successfully.\n";

    // Step 7: Uninstall database structure
    echo "Uninstalling database structure...\n";
    $service->uninstall();
    
    $log->info('job finished successfully.');

} catch (Exception $e) {
    $log->error('Error: ' . $e->getMessage());
} catch (PDOException $e) {
    $log->error('Database error: ' . $e->getMessage());
} catch (Throwable $e) {
    $log->error('Error: ' . $e->getMessage());
}
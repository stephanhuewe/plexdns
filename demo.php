<?php

use PlexDNS\Service;

require_once 'vendor/autoload.php';

// Database connection details
$dsn = 'mysql:host=127.0.0.1;dbname=dbname;charset=utf8mb4';
$username = 'user';
$password = 'pass';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
    ]);

    // Initialize the Service with the database connection
    $service = new Service($pdo);

    // Step 1: Install database structure
    echo "Installing database structure...\n";
    $service->install();

    // Step 2: Create a domain
    echo "Creating a domain...\n";
    $domainOrder = [
        'client_id' => 1,
        'config' => json_encode(['domain_name' => 'sofia-match.com', 'provider' => 'Desec', 'apikey' => 'XXX']),
    ];
    $domain = $service->createDomain($domainOrder);
    print_r($domain);

    // Step 3: Add a DNS record
    echo "Adding a DNS record...\n";
    $recordData = [
        'domain_name' => 'sofia-match.com',
        'record_name' => 'www',
        'record_type' => 'A',
        'record_value' => '192.168.1.1',
        'record_ttl' => 3600,
        'provider' => 'Desec',
        'apikey' => 'XXX'
    ];
    $recordId = $service->addRecord($recordData);
    echo "DNS record added successfully.\n";

    // Step 4: Update a DNS record
    echo "Updating a DNS record...\n";
    $updateData = [
        'domain_name' => 'sofia-match.com',
        'record_id' => $recordId,
        'record_name' => 'www',
        'record_type' => 'A',
        'record_value' => '192.168.1.2',
        'record_ttl' => 7200,
        'provider' => 'Desec',
        'apikey' => 'XXX'
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
    ];
    $service->delRecord($deleteData);
    echo "DNS record deleted successfully.\n";

    // Step 6: Delete a domain
    echo "Deleting a domain...\n";
    $service->deleteDomain(['config' => json_encode(['domain_name' => 'example.com', 'provider' => 'Bind'])]);
    echo "Domain deleted successfully.\n";

    // Step 7: Uninstall database structure
    echo "Uninstalling database structure...\n";
    $service->uninstall();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

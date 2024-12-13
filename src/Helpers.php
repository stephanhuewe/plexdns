<?php

/**
 * Updates the recordId for a specific DNS record in the database.
 *
 * @param PDO $pdo The PDO instance for database connection.
 * @param string $domainName The domain name associated with the record.
 * @param string $recordId The record ID to be saved.
 * @param array $rrsetData The RRset data including type, subname, and records.
 * @return int Number of rows updated in the database.
 * @throws Exception If the domain does not exist or the update fails.
 */
function saveRecordId(PDO $pdo, string $domainName, string $recordId, array $rrsetData): int
{
    // Step 1: Fetch the domain ID
    $sqlDomain = "SELECT id FROM service_dns WHERE domain_name = :domainName LIMIT 1";
    $stmtDomain = $pdo->prepare($sqlDomain);
    $stmtDomain->bindParam(':domainName', $domainName, PDO::PARAM_STR);
    $stmtDomain->execute();

    $domain = $stmtDomain->fetch(PDO::FETCH_ASSOC);

    if (!$domain) {
        throw new \Exception("Domain name does not exist.");
    }

    $domainId = $domain['id'];

    // Step 2: Update the recordId
    $sqlUpdate = "
        UPDATE service_dns_records 
        SET recordId = :recordId 
        WHERE type = :type AND host = :subname AND value = :value AND domain_id = :domain_id
    ";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':recordId', $recordId, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':type', $rrsetData['type'], PDO::PARAM_STR);
    $stmtUpdate->bindParam(':subname', $rrsetData['subname'], PDO::PARAM_STR);
    $stmtUpdate->bindParam(':value', $rrsetData['records'][0], PDO::PARAM_STR);
    $stmtUpdate->bindParam(':domain_id', $domainId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    // Step 3: Check rows updated
    $rowCount = $stmtUpdate->rowCount();
    if ($rowCount === 0) {
        throw new \Exception("No DB update made. Check if the record exists for the domain.");
    }

    return $rowCount; // Return the number of rows updated
}

/**
 * Fetches the recordId for a specific DNS record.
 *
 * @param PDO $pdo The PDO instance for database connection.
 * @param string $domainName The domain name associated with the record.
 * @param string $type The type of the DNS record (e.g., A, MX, CNAME).
 * @param string $subname The subdomain or hostname of the record.
 * @return string The recordId of the DNS record.
 * @throws Exception If the domain or record does not exist.
 */
function getRecordId(PDO $pdo, string $domainName, string $type, string $subname): string
{
    // Step 1: Fetch the domain ID
    $sqlDomain = "SELECT id FROM service_dns WHERE domain_name = :domainName LIMIT 1";
    $stmtDomain = $pdo->prepare($sqlDomain);
    $stmtDomain->execute([':domainName' => $domainName]);

    $domain = $stmtDomain->fetch(PDO::FETCH_ASSOC);

    if (!$domain) {
        throw new \Exception("Domain name does not exist.");
    }

    $domainId = $domain['id'];

    // Step 2: Fetch the record ID
    $sqlRecord = "
        SELECT recordId 
        FROM service_dns_records 
        WHERE type = :type AND host = :subname AND domain_id = :domain_id 
        LIMIT 1
    ";
    $stmtRecord = $pdo->prepare($sqlRecord);
    $stmtRecord->execute([
        ':type' => $type,
        ':subname' => $subname,
        ':domain_id' => $domainId,
    ]);

    $record = $stmtRecord->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new \Exception("Error: No record found with name '$subname' and type '$type'");
    }

    return $record['recordId'];
}

/**
 * Updates the zoneId for a specific domain in the database.
 *
 * @param PDO $pdo The PDO instance for database connection.
 * @param string $domainName The domain name to update.
 * @param string $zoneId The zone ID to set for the domain.
 * @return int Number of rows updated in the database.
 * @throws Exception If no rows are updated.
 */
function saveZoneId(PDO $pdo, string $domainName, string $zoneId): int
{
    $sql = "UPDATE service_dns SET zoneId = :zoneId WHERE domain_name = :domainName";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':zoneId', $zoneId, PDO::PARAM_STR);
    $stmt->bindParam(':domainName', $domainName, PDO::PARAM_STR);
    $stmt->execute();

    $rowCount = $stmt->rowCount();
    if ($rowCount === 0) {
        throw new \Exception("No DB update made. Check if the domain name exists.");
    }

    return $rowCount; // Return the number of rows updated
}

/**
 * Fetches the zoneId and domainId for a specific domain.
 *
 * @param PDO $pdo The PDO instance for database connection.
 * @param string $domainName The domain name to search for.
 * @return array An associative array containing 'zoneId' and 'domainId'.
 * @throws Exception If the domain does not exist.
 */
function getZoneId(PDO $pdo, string $domainName): array
{
    $sql = "SELECT id, zoneId FROM service_dns WHERE domain_name = :domainName LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':domainName', $domainName, PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new \Exception("Domain name does not exist.");
    }

    return [
        'zoneId' => $row['zoneId'],
        'domainId' => $row['id'],
    ];
}
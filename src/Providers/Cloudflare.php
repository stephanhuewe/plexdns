<?php

namespace PlexDNS\Providers;

use Cloudflare\API\Auth\APIKey as CloudflareAPIKey;
use Cloudflare\API\Adapter\Guzzle as CloudflareGuzzle;
use Cloudflare\API\Endpoints\Zones as CloudflareZones;
use Cloudflare\API\Endpoints\DNS as CloudflareDNS;

class Cloudflare implements DnsHostingProviderInterface {
    private $adapter;
    private $zones;
    private $dns;
    
    public function __construct($config) {
        if (empty($config['apikey'])) {
            throw new \Exception("API key cannot be empty");
        }

        // Extract email and API key from "email:apikey" format
        $parts = explode(":", $config['apikey'], 2);
        if (count($parts) !== 2) {
            throw new \Exception("Invalid API key format. Expected 'email:apikey'.");
        }

        list($email, $apiKey) = $parts;

        $key = new CloudflareAPIKey($email, $apiKey);
        $this->adapter = new CloudflareGuzzle($key);
        $this->zones = new CloudflareZones($this->adapter);
        $this->dns = new CloudflareDNS($this->adapter);
    }

    public function createDomain($domainName) {
        try {
            return $this->zones->addZone($domainName);
        } catch (CFEndpointException $e) {
            throw new \Exception("Error creating domain: " . $e->getMessage());
        }
    }

    public function listDomains() {
        try {
            return $this->zones->listZones()->result;
        } catch (CFEndpointException $e) {
            throw new \Exception("Error listing domains: " . $e->getMessage());
        }
    }

    public function getDomain($domainName) {
        try {
            $zone = $this->zones->getZoneID($domainName);
            $zoneDetails = $this->zones->getZoneByID($zone);

            return [
                'id' => $zoneDetails->id,
                'name' => $zoneDetails->name,
                'status' => $zoneDetails->status,
                'name_servers' => $zoneDetails->name_servers,
                'created_at' => $zoneDetails->created_on,
                'modified_at' => $zoneDetails->modified_on,
            ];
        } catch (CFEndpointException $e) {
            throw new \Exception("Error retrieving domain details: " . $e->getMessage());
        }
    }

    public function getResponsibleDomain($qname) {
        throw new \Exception("Not yet implemented");
    }

    public function exportDomainAsZonefile($domainName) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteDomain($domainName) {
        try {
            $zoneId = $this->zones->getZoneID($domainName);
            return $this->zones->deleteZone($zoneId);
        } catch (CFEndpointException $e) {
            throw new \Exception("Error deleting domain: " . $e->getMessage());
        }
    }

    public function createRRset($domainName, $rrsetData) {
        try {
            $zoneId = $this->zones->getZoneID($domainName);
            $priority = isset($rrsetData['priority']) ? (string) $rrsetData['priority'] : '';

            return $this->dns->addRecord(
                $zoneId,
                $rrsetData['type'],
                $rrsetData['subname'] . '.' . $domainName,
                $rrsetData['records'][0],
                isset($rrsetData['ttl']) ? $rrsetData['ttl'] : 1,
                false,
                $priority
            );
        } catch (CFEndpointException $e) {
            throw new \Exception("Error creating record: " . $e->getMessage());
        }
    }

    public function createBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

    public function retrieveAllRRsets($domainName) {
        try {
            $zoneId = $this->zones->getZoneID($domainName);
            return $this->dns->listRecords($zoneId);
        } catch (CFEndpointException $e) {
            throw new \Exception("Error retrieving records: " . $e->getMessage());
        }
    }

    public function retrieveSpecificRRset($domainName, $subname, $type) {
        try {
            $zoneId = $this->zones->getZoneID($domainName);
            return $this->dns->listRecords($zoneId, $type, $subname);
        } catch (CFEndpointException $e) {
            throw new \Exception("Error retrieving specific RRset: " . $e->getMessage());
        }
    }

    public function modifyRRset($domainName, $subname, $type, $rrsetData) {
        try {
            $zoneId = $this->zones->getZoneID($domainName);
            $records = $this->dns->listRecords($zoneId, $type, $subname);

            if (empty($records->result)) {
                throw new \Exception("No matching records found for $subname.$domainName ($type)");
            }

            foreach ($records->result as $record) {
                // Normalize names to ensure correct matching
                $recordName = strtolower($record->name);
                $expectedName = strtolower($subname === '@' ? $domainName : "$subname.$domainName");

                if ($recordName === $expectedName && $record->type === strtoupper($type)) {
                    return $this->dns->updateRecord(
                        $zoneId,
                        $record->id,
                        $type,
                        $rrsetData['records'][0],
                        isset($rrsetData['ttl']) ? $rrsetData['ttl'] : 1
                    );
                }
            }

            throw new \Exception("Record not found for $subname.$domainName ($type)");
        } catch (CFEndpointException $e) {
            throw new \Exception("Error modifying record: " . $e->getMessage());
        }
    }

    public function modifyBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteRRset($domainName, $subname, $type, $value) {
        try {
            $zoneId = $this->zones->getZoneID($domainName);
            $records = $this->dns->listRecords($zoneId, $type, $subname);

            if (empty($records->result)) {
                throw new \Exception("No matching records found for $subname.$domainName ($type)");
            }

            foreach ($records->result as $record) {
                // Normalize name comparison
                $recordName = strtolower($record->name);
                $expectedName = strtolower($subname === '@' ? $domainName : "$subname.$domainName");

                if ($recordName === $expectedName && $record->type === strtoupper($type) && $record->content === $value) {
                    $this->dns->deleteRecord($zoneId, $record->id);
                    return "Record deleted: $expectedName ($type -> $value)";
                }
            }

            throw new \Exception("Record not found for $subname.$domainName ($type)");
        } catch (CFEndpointException $e) {
            throw new \Exception("Error deleting record: " . $e->getMessage());
        }
    }

    public function deleteBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }
}

<?php

namespace PlexDNS\Providers;

use Qcloudns\Client;
use Qcloudns\ZoneManager;
use Qcloudns\RecordManager;
use Exception;

class ClouDNS implements DnsHostingProviderInterface {
    private $zoneManager;
    private $recordManager;

    public function __construct($config) {
        $authId = $config['cloudns_auth_id'];
        $authPassword = $config['cloudns_auth_password'];

        if (empty($authId) || empty($authPassword)) {
            throw new Exception("Authentication ID and Password cannot be empty");
        }

        $client = new Client($authId, $authPassword);
        $this->zoneManager = new ZoneManager($client);
        $this->recordManager = new RecordManager($client);
    }

    public function createDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $response = $this->zoneManager->createZone($domainName);
        return $response;
    }

    public function listDomains() {
        $response = $this->zoneManager->listZones();
        return $response;
    }

    public function getDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $response = $this->zoneManager->getZone($domainName);
        return $response;
    }
    
    public function getResponsibleDomain($qname) {
        throw new \Exception("Not yet implemented");
    }

    public function exportDomainAsZonefile($domainName) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $response = $this->zoneManager->deleteZone($domainName);
        return $response;
    }

    public function createRRset($domainName, $rrsetData) {
        if (empty($domainName)) {
            throw new \Exception("Domain name cannot be empty");
        }

        if (!isset($rrsetData['subname'], $rrsetData['type'], $rrsetData['ttl'], $rrsetData['records'])) {
            throw new \Exception("Missing data for creating RRset");
        }

        $response = $this->recordManager->addRecord($domainName, $rrsetData['type'], $rrsetData['subname'], $rrsetData['records'], $rrsetData['ttl']);
        return $response;
    }

    public function createBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

    public function retrieveAllRRsets($domainName) {
        throw new \Exception("Not yet implemented");
    }

    public function retrieveSpecificRRset($domainName, $subname, $type) {
        throw new \Exception("Not yet implemented");
    }

    public function modifyRRset($domainName, $subname, $type, $rrsetData) {
        if (empty($domainName)) {
            throw new \Exception("Domain name cannot be empty");
        }

        if (!isset($subname, $type, $rrsetData['ttl'], $rrsetData['records'])) {
            throw new \Exception("Missing data for creating RRset");
        }

        $response = $this->recordManager->updateRecord($domainName, $recordId, $recordType, $host, $recordData, $ttl);
        return $response;
    }

    public function modifyBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteRRset($domainName, $subname, $type, $value) {
        if (empty($domainName)) {
            throw new \Exception("Domain name cannot be empty");
        }

        if (!isset($subname, $type, $value)) {
            throw new \Exception("Missing data for creating RRset");
        }
        
        $record = [
            'name' => $subname,
            'type' => $type,
            'rdata' => $value
        ];

        $response = $this->recordManager->deleteRecord($domainName, $recordId);
        return $response;
    }

    public function deleteBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

}
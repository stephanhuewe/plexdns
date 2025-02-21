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
        $authId = $config['auth_id'];
        $authPassword = $config['auth_password'];

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

    public function deleteDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $response = $this->zoneManager->deleteZone($domainName);
        return $response;
    }

    public function createRecord($domainName, $recordType, $host, $recordData, $ttl = 3600) {
        if (empty($domainName) || empty($recordType) || empty($host) || empty($recordData)) {
            throw new Exception("Domain name, record type, host, and record data cannot be empty");
        }

        $response = $this->recordManager->addRecord($domainName, $recordType, $host, $recordData, $ttl);
        return $response;
    }

    public function listRecords($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $response = $this->recordManager->listRecords($domainName);
        return $response;
    }

    public function updateRecord($domainName, $recordId, $recordType, $host, $recordData, $ttl = 3600) {
        if (empty($domainName) || empty($recordId) || empty($recordType) || empty($host) || empty($recordData)) {
            throw new Exception("Domain name, record ID, record type, host, and record data cannot be empty");
        }

        $response = $this->recordManager->updateRecord($domainName, $recordId, $recordType, $host, $recordData, $ttl);
        return $response;
    }

    public function deleteRecord($domainName, $recordId) {
        if (empty($domainName) || empty($recordId)) {
            throw new Exception("Domain name and record ID cannot be empty");
        }

        $response = $this->recordManager->deleteRecord($domainName, $recordId);
        return $response;
    }
}
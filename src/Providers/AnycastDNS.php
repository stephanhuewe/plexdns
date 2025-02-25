<?php

namespace PlexDNS\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class AnycastDNS implements DnsHostingProviderInterface {
    private $client;
    private $apiKey;

    public function __construct($config) {
        $this->apiKey = $config['apikey'] ?? null;

        if (empty($this->apiKey)) {
            throw new Exception("API key cannot be empty");
        }

        $this->client = new Client([
            'base_uri' => 'https://api.anycastdns.app/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    private function request($method, $endpoint, $params = []) {
        try {
            $options = [];
            if ($method === 'GET') {
                $options['query'] = $params;
            } else {
                $options['json'] = $params;
            }

            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $message = $response ? $response->getBody()->getContents() : $e->getMessage();
            throw new Exception("HTTP request failed: " . $message);
        }
    }

    public function createDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $params = ['name' => $domainName];
        return $this->request('POST', 'domains', $params);
    }

    public function listDomains() {
        throw new \Exception("Not yet implemented");
    }

    public function getDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        return $this->request('GET', "domain/{$domainName}");
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

        return $this->request('DELETE', "domains/{$domainName}");
    }

    public function createRRset($domainName, $rrsetData) {
        if (empty($domainName) || !isset($rrsetData['subname'], $rrsetData['type'], $rrsetData['ttl'], $rrsetData['records'])) {
            throw new Exception("Missing data for creating RRset");
        }

        $params = [
            'type' => strtoupper($rrsetData['type']),
            'name' => $rrsetData['subname'],
            'content' => $rrsetData['records'],
            'ttl' => $rrsetData['ttl'],
        ];

        return $this->request('POST', "dns/{$domainName}/record", $params);
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
        if (empty($domainName) || empty($subname) || empty($type) || empty($rrsetData['ttl']) || empty($rrsetData['records'])) {
            throw new Exception("Missing data for modifying RRset");
        }

        $params = [
            'type' => strtoupper($type),
            'name' => $subname,
            'content' => $rrsetData['records'],
            'ttl' => $rrsetData['ttl'],
        ];

        return $this->request('PUT', "dns/{$domainName}/record/{$recordId}", $params);
    }
    
    public function modifyBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteRRset($domainName, $subname, $type, $value) {
        if (empty($domainName) || empty($subname) || empty($type) || empty($value)) {
            throw new Exception("Missing data for deleting RRset");
        }

        return $this->request('DELETE', "dns/{$domainName}/record/{$recordId}");
    }
    
    public function deleteBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }
}
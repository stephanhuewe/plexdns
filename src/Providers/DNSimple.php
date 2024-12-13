<?php

namespace DNS\Providers;

use Dnsimple\Client;
use PDO;

class DNSimple implements DnsHostingProviderInterface {
    private $client;
    private $account_id;
    private PDO $pdo;
    
    public function __construct($config, PDO $pdo) {
        $this->pdo = $pdo;

        $token = $config['apikey'];
        if (empty($token)) {
            throw new \Exception("API token cannot be empty");
        }

        $this->client = new Client($token);
        $this->account_id = $this->client->identity->whoami()->getData()->account->id;
    }

    public function createDomain($domainName) {
        if (empty($domainName)) {
            throw new \Exception("Domain name cannot be empty");
        }

        try {
            $response = $this->client->domains->createDomain($this->account_id, ["name" => $domainName]);
            return json_decode($response->getData()->name, true);
        } catch (\Exception $e) {
            throw new \Exception("Error creating domain: " . $e->getMessage());
        }
    }

    public function listDomains() {
        throw new \Exception("Not yet implemented");
    }

    public function getDomain($domainName) {
        throw new \Exception("Not yet implemented");
    }

    public function getResponsibleDomain($qname) {
        throw new \Exception("Not yet implemented");
    }

    public function exportDomainAsZonefile($domainName) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteDomain($domainName) {
        if (empty($domainName)) {
            throw new \Exception("Domain name cannot be empty");
        }

        try {
            $this->client->domains->deleteDomain($this->account_id, $domainName);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Error deleting domain: " . $e->getMessage());
        }
    }
    
    public function createRRset($domainName, $rrsetData) {
        try {
            $record = [];

            if (isset($rrsetData['type'])) {
                $record['type'] = $rrsetData['type'];
            }
            if (isset($rrsetData['subname'])) {
                $record['name'] = $rrsetData['subname'];
            }
            if (isset($rrsetData['records'])) {
                $record['content'] = $rrsetData['records'][0];
            }
            if (isset($rrsetData['priority'])) {
                $record['priority'] = $rrsetData['priority'];
            } else {
                $record['priority'] = 0;
            }
            if (isset($rrsetData['ttl'])) {
                $record['ttl'] = $rrsetData['ttl'];
            }
            
            $response = $this->client->zones->createRecord($this->account_id, $domainName, $record);
            $recordId = $response->getData()->id;

            try {
                saveRecordId($this->pdo, $domainName, $recordId, $rrsetData);
            } catch (\Exception $e) {
                echo "Error creating record: " . $e->getMessage() . "\n";
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception("Error creating record: " . $e->getMessage());
        }
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
        try {
            $recordId = getRecordId($this->pdo, $domainName, $type, $subname);

            $record = [];

            if (isset($type)) {
                $record['type'] = $type;
            }
            if (isset($subname)) {
                $record['name'] = $subname;
            }
            if (isset($rrsetData['records'])) {
                $record['content'] = $rrsetData['records'][0];
            }
            if (isset($rrsetData['priority'])) {
                $record['priority'] = $rrsetData['priority'];
            } else {
                $record['priority'] = 0;
            }
            if (isset($rrsetData['ttl'])) {
                $record['ttl'] = $rrsetData['ttl'];
            }

            $response = $this->client->zones->updateRecord($this->account_id, $domainName, $recordId, $record);
            
            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception("Error updating record: " . $e->getMessage());
        } catch (\PDOException $e) {
            throw new \Exception("Error in operation: " . $e->getMessage());
        }
    }

    public function modifyBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }

    public function deleteRRset($domainName, $subname, $type, $value) {
        try {
            $recordId = getRecordId($this->pdo, $domainName, $type, $subname);
            
            $response = $this->client->zones->deleteRecord($this->account_id, $domainName, $recordId);
            
            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception("Error deleting record: " . $e->getMessage());
        }
    }

    public function deleteBulkRRsets($domainName, $rrsetDataArray) {
        throw new \Exception("Not yet implemented");
    }
    
}
<?php

/**
 * Service to connect salesforce
 *
 * @author PGS
 */
declare(strict_types=1);

namespace Syncrasy\PimcoreSalesforceBundle\Service;

use Pimcore\Model\DataObject;
use Pimcore\Model\WebsiteSetting;

class Sfconnect {

   
    const SANDBOXWSDLFIlE = __DIR__ . '/wsdl/sandbox.wsdl';
    const PRODUCTIONWSDLFIlE = __DIR__ . '/wsdl/production.wsdl';
    public $authData = '';

    public function __construct() {
        $this->authData = $this->authenticate();
    }

    public function authenticate() {

        $sfUsername = WebsiteSetting::getByName('salesforce-username')->getData();
        $sfPassword = WebsiteSetting::getByName('salesforce-password')->getData();
        $sforg = WebsiteSetting::getByName('salesforce-org')->getData();
        $mySforceConnection = new SforceEnterpriseClient();
        $mySforceConnection->createConnection($sforg=='sandbox' ? self::SANDBOXWSDLFIlE : self::PRODUCTIONWSDLFIlE );
        $mySforceConnection->login($sfUsername, $sfPassword);
         \Pimcore\Log\Simple::log('salesForceConnectListener', "sf-create:" . json_encode($mySforceConnection));
        return $mySforceConnection;
    }

    public function insert($class, $data) {

        $records = array();
        $record = new \stdClass();
        foreach ($data as $key => $val) {
            if ($val) {
                $record->$key = $val;
            } else {
                $record->fieldsToNull[] = $key;
            }
        }
        
        $records[] = $record;
        try {
            \Pimcore\Log\Simple::log('salesForceConnectListener', "sf-create: body : " . json_encode($records));
            $response = $this->authData->create($records,$class);
            
            \Pimcore\Log\Simple::log('salesForceConnectListener', "sf-create:" . json_encode($response));
            $debug = \Pimcore\Log\ApplicationLogger::getInstance();
            $debug->debug("sf-create:" .json_encode($response));
            return $response;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            \Pimcore\Log\Simple::log('salesForceConnectListener', "sf-create:" . $e->getMessage());
            $debug = \Pimcore\Log\ApplicationLogger::getInstance();
            $debug->error("sf-create:" . $e->getMessage());
        }
    }

    public function update($class, $data, $id) {

        $records = array();
        $record = new \stdClass();
        foreach ($data as $key => $val) {
            if ($val) {
                $record->$key = $val;
            } else {
                $record->fieldsToNull[] = $key;
            }
        }
        if ($id) {
            $record->Id = $id;
        }
        $records[] = $record;
        $response = $this->authData->update($records,$class);
        \Pimcore\Log\Simple::log('salesForceConnectListener', "sf-update:" . \GuzzleHttp\json_encode($response));
        $debug = \Pimcore\Log\ApplicationLogger::getInstance();
        $debug->debug("sf-update:" . \GuzzleHttp\json_encode($response));
    }

    public function save($class, $data, $uniqueField = '') {
        
    }

    public function query(string $query) {

        $result = $this->authData->query($query);
        
        return $result->records ? $result->records[0]->Id : null;
    }

    public function recordExistsQuery($table, $fieldName, $fieldVal): string {

        return "select Id from $table where $fieldName = '" . $fieldVal . "'";
    }

    public function getObjects(): array {

        try {
            $result = $this->authData->describeGlobal();
            
            foreach ($result->sobjects as $key => $sobject) {

                if ($sobject->createable && $sobject->layoutable) {

                    $options[] = array("key" => $sobject->label, "value" => $sobject->name);
                }
            }
            return $options;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            \Pimcore\Log\Simple::log('salesForceConnectListener', "get object options:" . $e->getMessage());
            $debug = \Pimcore\Log\ApplicationLogger::getInstance();
            $debug->error("sf-fetch-data:" . $e->getMessage());
        }
    }

    public function getObjectsFields($type): array {

        
        try {
            $result = $this->authData->describeSObject($type);
            foreach ($result->fields as $key => $field) {
                if ($field->createable) {
                    $options[] = array("key" => $field->label, "value" => $field->name);
                }
            }
            return $options;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            \Pimcore\Log\Simple::log('salesForceConnectListener', "get object options:" . $e->getMessage());
            $debug = \Pimcore\Log\ApplicationLogger::getInstance();
            $debug->error("get object field options:" . $e->getMessage());
        }
    }

}

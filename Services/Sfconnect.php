<?php

/**
 * Service to connect salesforce
 *
 * @author PGS
 */
declare(strict_types=1);

namespace Syncrasy\PimcoreSalesforceBundle\Services;

use mysql_xdevapi\Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\WebsiteSetting;
use Syncrasy\PimcoreSalesforceBundle\Lib\PSCLogger;

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
        try {
            $mySforceConnection->login($sfUsername, $sfPassword);
            PSCLogger::log(json_encode($mySforceConnection),PSCLogger::INFO,'psc-sf-auth');
        }catch (\Exception $e){
            PSCLogger::log($e->getMessage(),PSCLogger::ERROR,'psc-sf-auth');
            return;
        }

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
            PSCLogger::log("body : " . json_encode($records),PSCLogger::INFO,'psc-sf-create');
            $response = $this->authData->create($records,$class);
            PSCLogger::log("response : " . json_encode($response),PSCLogger::INFO,'psc-sf-create');
            return $response;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            PSCLogger::log($e->getMessage(),PSCLogger::ERROR,'psc-sf-create');
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
        try {
            PSCLogger::log("body : " . json_encode($records),PSCLogger::INFO,'psc-sf-update');
        $response = $this->authData->update($records,$class);
            PSCLogger::log("response : " . json_encode($response),PSCLogger::INFO,'psc-sf-update');
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            PSCLogger::log($e->getMessage(),PSCLogger::ERROR,'psc-sf-update');
        }
    }

    public function save($class, $data, $uniqueField = '') {

    }

    public function query(string $query) {

        $result = $this->authData->query($query);

        return $result->records ? $result->records[0]->Id : null;
    }

    public function queryAll(string $query) {

        $result = $this->authData->queryAll($query);

        return $result->records ?? null;
    }

    public function recordExistsQuery($table, $fieldName, $fieldVal): string {

        return "select Id from $table where $fieldName = '" . $fieldVal . "'";
    }

    public function recordsQuery($table, $fields): string {

        $query = "Select Id";
        foreach ($fields as $field => $mapping){
            $query .= ", $field";
        }
        $query .= " from $table";
        return $query;
    }

    public function getObjects(): array {

        try {
            $result = $this->authData->describeGlobal();

            foreach ($result->sobjects as $key => $sobject) {

                if ($sobject->createable && $sobject->layoutable) {

                    $options[] = array("name" => $sobject->label, "id" => $sobject->name);
                }
            }
            return $options;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            PSCLogger::log($e->getMessage(),PSCLogger::ERROR,'psc');
        }
    }

    public function getObjectsFields($type): array {


        try {
            $result = $this->authData->describeSObject($type);
            foreach ($result->fields as $key => $field) {
                if ($field->createable) {
                    $options[] = array("name" => $field->label, "id" => $field->name);
                }
            }
            return $options;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            PSCLogger::log($e->getMessage(),PSCLogger::ERROR,'psc');
        }
    }

}

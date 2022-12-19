<?php

/**
 * Mapping file based on typo and pimcore pim.
 *
 * @author PGS
 * 
 */

namespace Syncrasy\SalesforceBundle\Service;

use Pimcore\Model\DataObject\SelesForceSetup as SalesForceSetupModel;
use Pimcore\Model\DataObject\ClassDefinition;
use SalesforceconnectBundle\EventListener;

class CommonService {

    /**
     * 
     * @return string|array file mapping typo to pimcore
     * 
     */
    public static function getClassMapping($className) {

        $clsMappingExists = new SalesForceSetupModel\Listing();
        $clsMappingExists->setCondition('pimcoreclass = ? And o_type = ?', [$className, 'object']);
        $clsMappingExists->setLimit(1);
        $clsMappingExists->load();
        $clsObje = '';
        if ($clsMappingExists) {
            foreach ($clsMappingExists->getObjects() as $obj) {
                $clsObje = $obj;
            }
        }
        $debug = \Pimcore\Log\ApplicationLogger::getInstance();
        $debug->debug('--------'.$clsObje->getId().'--------'.$className);
        return $clsObje;
    }

    public static function prepareData($mappingData, $pimObject) {
        $data = [];
        \Pimcore\Model\DataObject\AbstractObject::setGetInheritedValues(true);
        if (is_object($pimObject)) {
            foreach ($mappingData->getFieldmapping() as $val) {
                $fieldArray = explode('-', $val['pimcoreclassfield']->getData());
                $sfField = $val['salesforceobjectfield']->getData();


                if (count($fieldArray) == 1) {
                    $pimField = 'get' . $fieldArray[0];
                    \Pimcore\Log\Simple::log('salesForceConnectListener', 'Request JSON : ' . $pimField);
                    \Pimcore\Log\Simple::log('salesForceConnectListener', 'Request JSON : ' . is_array($pimObject->$pimField()));
                    // check if field value is relationship
                    
                    $data[$sfField] = self::getFieldValue($pimObject,$pimField);
                    
                } else if (count($fieldArray) == 3) {
                    if($pimObject->get($fieldArray[0]) != null && $pimObject->get($fieldArray[0])->get($fieldArray[1]) != null){
                        $data[$sfField] = $pimObject->get($fieldArray[0])->get($fieldArray[1])->get($fieldArray[2]);
                    }
                }
            }
            $debug = \Pimcore\Log\ApplicationLogger::getInstance();
            $debug->debug('Request JSON : ' . json_encode($data));
            \Pimcore\Log\Simple::log('salesForceConnectListener', 'Request JSON : ' . json_encode($data));
        }
         \Pimcore\Model\DataObject\AbstractObject::setGetInheritedValues(false);

        return $data;
    }

    

    public static function getFieldValue($pimObject,$pimField) {
        if (is_array($pimObject->$pimField())) {
            if (is_object($pimObject->$pimField()[0])) {
                $obj = $pimObject->$pimField()[0];
                $objClassName = $obj->getClassId();
                $mapping = self::getClassMapping($objClassName);
                // check if class has mapping
                if ($mapping) {
                    $fieldForSFId = $mapping->getFieldforsfid();
                    $getFieldForSFId = 'get' . $fieldForSFId;
                    if (!$obj->$getFieldForSFId()) {
                        EventListener\ObjectListener::syncDataToSalesforce($obj);
                    }
                    return $obj->$getFieldForSFId();
                }
            } else {
                return implode(',', $pimObject->$pimField());
            }
        } else if (is_object($pimObject->$pimField())) {

            if ($pimObject->$pimField() instanceof \Pimcore\Model\Asset) {
                return \Pimcore\Tool::getHostUrl() . $pimObject->$pimField()->getFullPath();
            }
            if ($pimObject->$pimField() instanceof \Pimcore\Model\DataObject) {
                $obj = $pimObject->$pimField();
                if (!($obj instanceof \Pimcore\Model\DataObject\Folder)) {
                    $objClassName = $obj->getClassId();
                    $mapping = self::getClassMapping($objClassName);

                    // check if class has mapping
                    if ($mapping) {
                        $fieldForSFId = $mapping->getFieldforsfid();
                        $getFieldForSFId = 'get' . $fieldForSFId;
                        if (!$obj->$getFieldForSFId()) {
                            EventListener\ObjectListener::syncDataToSalesforce($obj);
                        }
                        return $obj->$getFieldForSFId();
                    }
                }
            }
        } else {
            if ($pimObject->$pimField())
                 return $pimObject->$pimField();
            else {
               return null;
            }
        }
    }

    // for api get all class
    public static function getPimClasses() {

        $list = new ClassDefinition\Listing();
        $list->load();

        $options = [];
        if ($list) {
            foreach ($list->getClasses() as $class) {
                $options[] = $class->getName();
            }
        }
        return $options;
    }

    // for api get all class fields
    public static function getPimClassFields($pimClass, $type = 'Class') {

        if ($type == 'Class') {
            $fields = \Pimcore\Model\DataObject\ClassDefinition::getByName($pimClass);
            $options = ['o_id' => 'input'];
        } else {
            $fields = \Pimcore\Model\DataObject\Fieldcollection\Definition::getByKey($pimClass);
        }
        if ($fields) {
            foreach ($fields->getFieldDefinitions() as $field) {
                $options[$field->getName()] = $field->getFieldType();
            }
        }
        return $options;
    }

}

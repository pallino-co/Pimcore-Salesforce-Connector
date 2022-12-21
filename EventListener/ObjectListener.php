<?php

/**
 * Event Listener *
 * @author PGS
 */

namespace Syncrasy\PimcoreSalesforceBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Event\Model\DataObjectEvent;
use Syncrasy\PimcoreSalesforceBundle\Service\CommonService;
use Syncrasy\PimcoreSalesforceBundle\Service\Sfconnect;
use Pimcore\Model\DataObject;

class ObjectListener {

    /**
     * 
     * @param DataObjectEvent $e 
     */
    public static function onPostUpdate(DataObjectEvent $e) {
        if ($e instanceof DataObjectEvent) {
            if (($e->getObject()->getType() == 'object' || $e->getObject()->getType() == 'variant') && $e->getObject()->geto_classId() != 'salesforce_setup') {
                
                $pimDataObj = DataObject ::getById($e->getObject()->getId());
                self::syncDataToSalesforce($pimDataObj);
            }
        }
    }

    public static function syncDataToSalesforce(DataObject $pimDataObj) {
        $sfObject = new Sfconnect();
        $Children = $pimDataObj->getChildren();
        $array[] = $pimDataObj;
        foreach ($Children as $obj) {
            $array[] = $obj;
        }
        foreach ($array as $pimData) {
            unset($sfid);
            \Pimcore\Log\Simple::log('salesForceConnectListener', 'array data' . json_encode($array));
            $pimClassName = $pimData->geto_classId();
            \Pimcore\Log\Simple::log('salesForceConnectListener', $pimClassName);
            $mapping = CommonService::getClassMapping($pimClassName);
            if (is_object($mapping)) {
                $sObjectType = $mapping->getsalesforceobject();
                $sUniqueField = $mapping->getSfuniquefield();
                $pimUniqueField = $mapping->getpimuniquefield();
                $fieldforsfid = $mapping->getFieldforsfid();
                \Pimcore\Log\Simple::log('salesForceConnectListener', json_encode($pimData));
                $preparedArray = CommonService::prepareData($mapping, $pimData);
                \Pimcore\Log\Simple::log('salesForceConnectListener', json_encode($preparedArray));

                if (count($preparedArray)) {
                    if ($pimUniqueField && $sUniqueField) {
                        $sUniqueFieldVal = $preparedArray[$pimUniqueField];
                        \Pimcore\Log\Simple::log('salesForceConnectListener', $pimUniqueField . '-' . $sUniqueFieldVal);
                        $query = $sfObject->recordExistsQuery($sObjectType, $sUniqueField, $sUniqueFieldVal);
                        $sfid = $sfObject->query($query);
                    }
                    $fieldforsfidGetter = 'get' . $fieldforsfid;
                    if ($pimData->$fieldforsfidGetter()) {
                        $sfid = $pimData->$fieldforsfidGetter();
                    }
                    
                    if ($sfid) {
                        $sfObject->update($sObjectType, $preparedArray, $sfid);
                        \Pimcore\Log\Simple::log('salesForceConnectListener', $fieldforsfid . '---' . $pimData->$fieldforsfidGetter() . '-0-' . $sfid);
                        if ($pimData->$fieldforsfidGetter() != $sfid) {
                            $pimData->set($fieldforsfid, $sfid);
                        }
                        \Pimcore\Log\Simple::log('salesForceConnectListener', 'child' . $Children);
                        \Pimcore\Log\Simple::log('salesForceConnectListener', $sfid);
                    } else {
                        $result = $sfObject->insert($sObjectType, $preparedArray);
                        \Pimcore\Log\Simple::log('salesForceConnectListener', $result[0]->id);
                        if ($result[0]->id) {
                            $sfid = $result[0]->id;
                            $pimData->set($fieldforsfid, $sfid);
                            \Pimcore\Log\Simple::log('salesForceConnectListener', $fieldforsfid);
                            $pimData->save();
                           
                            \Pimcore\Log\Simple::log('salesForceConnectListener', $sfid);
                        } else {
                            \Pimcore\Log\Simple::log('salesForceConnectListener', 'dghudgvy8bdvyb' . json_encode($result));
                        }
                    }
                }
            }
        }
    }
}

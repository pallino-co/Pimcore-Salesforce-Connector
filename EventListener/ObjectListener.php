<?php

/**
 * Event Listener *
 * @author PGS
 */

namespace Syncrasy\PimcoreSalesforceBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Tool\Admin;
use Syncrasy\PimcoreSalesforceBundle\Lib\PSCLogger;
use Syncrasy\PimcoreSalesforceBundle\Services\Sfconnect;
use Syncrasy\PimcoreSalesforceBundle\Services\ExportService;
use Syncrasy\PimcoreSalesforceBundle\Model\Mapping;

class ObjectListener
{

    /**
     *
     * @param DataObjectEvent $e
     */
    public function onPostUpdate(DataObjectEvent $e)
    {
        if ($e instanceof DataObjectEvent && Admin::getCurrentUser()) {
            if (($e->getObject()->getType() == 'object' || $e->getObject()->getType() == 'variant')) {

                $mappingObject = Mapping::getByPimcoreId($e->getObject()->geto_classId());
                $pimData = $e->getObject();
                if ($mappingObject) {
                    $sfObject = new Sfconnect();
                    if ($sfObject->authData) {
                        $columnAttributeMapping = json_decode($mappingObject->getColumnAttributeMapping(), true);
                        $exportService = new ExportService();
                        $exportService->prepareFieldsAndHelperDefinition($columnAttributeMapping);
                        $fields = $exportService->getFieldsForExport();
                        $data = $exportService->getCsvDataForExport($e->getObject(), $fields, $mappingObject->getLanguage(), false);
                        $sObjectType = $mappingObject->getsalesforceobject();
                        $sUniqueField = $mappingObject->getSalesforceUniqueField();
                        $pimUniqueField = $mappingObject->getPimcoreUniqueField();
                        $fieldforsfid = $mappingObject->getFieldforsfid();
                        $sfid = $e->getObject()->get($fieldforsfid);
                        if ($sfid) {
                            $sfObject->update($sObjectType, $data->jsonData, $sfid);
                            PSCLogger::log($fieldforsfid . '----sf-update ---' . $sfid . '-0-' . $sfid,PSCLogger::INFO,'psc-listener');
                        } else {
                            $sUniqueFieldVal = $pimData->get($pimUniqueField);
                            $query = $sfObject->recordExistsQuery($sObjectType, $sUniqueField, $sUniqueFieldVal);
                            $sfid = $sfObject->query($query);
                            if ($sfid) {
                                $sfObject->update($sObjectType, $data->jsonData, $sfid);
                                PSCLogger::log($fieldforsfid . '--sf-update --' . $sfid . '-0-' . $sfid,PSCLogger::INFO,'psc-listener');
                                $pimData->set($fieldforsfid, $sfid);
                                $pimData->save();
                            } else {
                                $result = $sfObject->insert($sObjectType, $data->jsonData);
                                PSCLogger::log($fieldforsfid . '--sf-insert -' . $sfid . '-0-' . $sfid,PSCLogger::INFO,'psc-listener');
                                if ($result[0]->id) {
                                    $sfid = $result[0]->id;
                                    $pimData->set($fieldforsfid, $sfid);
                                    $pimData->save();
                                } else {
                                    PSCLogger::log(json_encode($result),PSCLogger::INFO,'psc-listener');
                                }
                            }
                        }
                    }
                }

            }
        }
    }
}

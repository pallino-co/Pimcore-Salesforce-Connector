<?php

namespace Syncrasy\PimcoreSalesforceBundle\Services;

use Syncrasy\PimcoreSalesforceBundle\Model\Mapping;

class MappingService
{
    /**
     * Prepare mapping info
     * @param [] $data
     * @param int $classId
     * @return array
     */
    public static function getMappingInfo($data, $classId = 0)
    {
        $dataField = [];
        $previewData = [];
        if (count($data)) {
            foreach ($data[0] as $k => $v) {
                $dataField[] = "field_$k";
                $previewData["field_$k"] = $v;
            }
        }
        return [
            'dataPreview' => count($previewData) ? [$previewData] : [],
            'dataFields' => $dataField,
            'templateAttribute' => $data[0] ?? [],
            'selectedGridColumns' => [],
            'cols' => count($previewData),
            'classId' => $classId,
        ];
    }

    /**
     * @param $objectId
     * @return array[]
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function getSheetColumns($objectId)
    {
        $object = Mapping::getById($objectId);
        $classId = $object->getPimcoreClassId();
        $salesforceId = $object->getSalesforceObject();
        $columns = ['col' => [], 'config' => []];
        $sfObject = new Sfconnect();
        $options = $sfObject->getObjectsFields($salesforceId);
        foreach($options as $key=>$value){
            $columns['col'][] = $value['id'];
        }
        if (count($columns['col']) > 0) {
            $columns['config'] = self::getMappingInfo([$columns['col']], $classId);
        }
        return $columns;
    }


}
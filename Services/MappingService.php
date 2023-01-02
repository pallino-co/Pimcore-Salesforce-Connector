<?php

namespace Syncrasy\PimcoreSalesforceBundle\Services;

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
            'importConfigId' => '',
            'dataPreview' => count($previewData) ? [$previewData] : [],
            'dataFields' => $dataField,
            'targetFields' => [],
            'templateAttribute' => $data[0] ?? [],
            'selectedGridColumns' => [],
            'resolverSettings' => [],
            'rows' => 1,
            'cols' => count($previewData),
            'classId' => $classId,
        ];
    }


}
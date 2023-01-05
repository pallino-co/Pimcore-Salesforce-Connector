<?php

namespace Syncrasy\PimcoreSalesforceBundle\Lib\Export;

use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;

class DataFetcher
{
    public function getCsvData($request, $object, $fields, $helperDefinitions,$addTitles = true, )
    {
        $requestedLanguage = $this->extractLanguage($request);
        $localeService = $request->getLocale();
        $mappedFieldnames = [];


        if ($fields) {
            $objectData =[];
            foreach ($fields as $key => $field) {

                if (empty($field)) {
                    $objectData[$key] = $field;
                    continue;
                }

                if (DataObject\Service::isHelperGridColumnConfig($field) && $validLanguages = DataObject\Service::expandGridColumnForExport($helperDefinitions, $field)) {

                    $currentLocale = $localeService->getLocale();
                    $mappedFieldnameBase = $this->mapFieldname($field, $helperDefinitions);

                    foreach ($validLanguages as $validLanguage) {
                        $localeService->setLocale($validLanguage);
                        $fieldData = $this->getCsvFieldData($request, $field, $object, $validLanguage, $helperDefinitions);
                        $localizedFieldKey = $field . '-' . $validLanguage;
                        if (!isset($mappedFieldnames[$localizedFieldKey])) {
                            $mappedFieldnames[$localizedFieldKey] = $mappedFieldnameBase . '-' . $validLanguage;
                        }
                        $objectData[$localizedFieldKey] = $fieldData;
                    }

                    $localeService->setLocale($currentLocale);
                } else {


                    $fieldData = $this->getCsvFieldData($request, $field, $object, $requestedLanguage, $helperDefinitions);

                    if (!isset($mappedFieldnames[$field])) {
                        $mappedFieldnames[$field] = $this->mapFieldname($field, $helperDefinitions);
                    }
                    $objectData[$field] = $fieldData;
                }
            }

        }

        return $objectData ;


    }

    private function getCsvFieldData(Request $request, $field, $object, $requestedLanguage, $helperDefinitions)
    {


        //check if field is systemfield
        $systemFieldMap = [
            'id' => 'getId',
            'fullpath' => 'getRealFullPath',
            'published' => 'getPublished',
            'creationDate' => 'getCreationDate',
            'modificationDate' => 'getModificationDate',
            'filename' => 'getKey',
            'key' => 'getKey',
            'classname' => 'getClassname'
        ];
        if (in_array($field, array_keys($systemFieldMap))) {
            $getter = $systemFieldMap[$field];

            return $object->$getter();
        } else {
            //check if field is standard object field
            $fieldDefinition = $object->getClass()->getFieldDefinition($field);

            if ($fieldDefinition) {
                $fieldType = $fieldDefinition->getFieldtype();
                if ($fieldType === "image" || $fieldType === "imageGallery" || $fieldType === "externalImage" || $fieldType === "hotspotimage") {
                    $dataForAssetExport = $fieldDefinition->getForCsvExport($object);

                    if (empty($dataForAssetExport)) {
                        return $dataForAssetExport;
                    }
                    // Prefix hostname with assets
                    return $dataForAssetExport;
                }
                return $fieldDefinition->getForCsvExport($object);
            } else {
                $fieldParts = explode('~', $field);

                // check for objects bricks and localized fields
                if (DataObject\Service::isHelperGridColumnConfig($field)) {
                    if ($helperDefinitions[$field]) {
                        return DataObject\Service::calculateCellValue($object, $helperDefinitions, $field, ['language' => $requestedLanguage]);
                    }
                } elseif (substr($field, 0, 1) == '~') {
                    $type = $fieldParts[1];

                    if ($type == 'classificationstore') {
                        $fieldname = $fieldParts[2];
                        $groupKeyId = explode('-', $fieldParts[3]);
                        $groupId = $groupKeyId[0];
                        $keyId = $groupKeyId[1];
                        $getter = 'get' . ucfirst($fieldname);
                        if (method_exists($object, $getter)) {
                            $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);
                            $type = $keyConfig->getType();
                            $definition = json_decode($keyConfig->getDefinition());
                            $fieldDefinition = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                            /** @var DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                            $csFieldDefinition = $object->getClass()->getFieldDefinition($fieldname);
                            $csLanguage = $requestedLanguage;
                            if (!$csFieldDefinition->isLocalized()) {
                                $csLanguage = 'default';
                            }

                            return $fieldDefinition->getForCsvExport(
                                $object,
                                ['context' => [
                                    'containerType' => 'classificationstore',
                                    'fieldname' => $fieldname,
                                    'groupId' => $groupId,
                                    'keyId' => $keyId,
                                    'language' => $csLanguage
                                ]]
                            );
                        }
                    }
                    //key value store - ignore for now
                } elseif (count($fieldParts) > 1) {
                    // brick
                    $brickType = $fieldParts[0];
                    $brickDescriptor = null;
                    $innerContainer = null;

                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                        $brickType = $brickDescriptor['containerKey'];
                    }
                    $brickKey = $fieldParts[1];

                    $key = DataObject\Service::getFieldForBrickType($object->getClass(), $brickType);

                    $brickClass = DataObject\Objectbrick\Definition::getByKey($brickType);

                    if ($brickDescriptor) {
                        /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedFields */
                        $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                        $fieldDefinition = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                    } else {
                        $fieldDefinition = $brickClass->getFieldDefinition($brickKey);
                    }

                    if ($fieldDefinition) {
                        $brickContainer = $object->{'get' . ucfirst($key)}();
                        if ($brickContainer && !empty($brickKey)) {
                            $brick = $brickContainer->{'get' . ucfirst($brickType)}();
                            if ($brick) {
                                $params = [
                                    'context' => [
                                        'containerType' => 'objectbrick',
                                        'containerKey' => $brickType,
                                        'fieldname' => $brickKey
                                    ]

                                ];

                                $value = $brick;

                                if ($brickDescriptor) {
                                    $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                                    $value = $brick->{'get' . ucfirst($innerContainer)}();
                                }

                                return $fieldDefinition->getForCsvExport($value, $params);
                            }
                        }
                    }
                } else {
                    // if the definition is not set try to get the definition from localized fields
                    /** @var DataObject\ClassDefinition\Data\Localizedfields|null $locFields */
                    $locFields = $object->getClass()->getFieldDefinition('localizedfields');

                    if ($locFields) {
                        $fieldDefinition = $locFields->getFieldDefinition($field);
                        if ($fieldDefinition) {
                            return $fieldDefinition->getForCsvExport($object->get('localizedFields'), ['language' => $request->get('language')]);
                        }
                    }
                }
            }
        }

        return null;
    }

    private function mapFieldname($field, $helperDefinitions)
    {


        if (strpos($field, '#') === 0) {
            if (isset($helperDefinitions[$field])) {
                if ($helperDefinitions[$field]->attributes) {
                    return $helperDefinitions[$field]->attributes->label ? $helperDefinitions[$field]->attributes->label : $field;
                }

                return $field;
            }
        } elseif (substr($field, 0, 1) == '~') {
            $fieldParts = explode('~', $field);
            $type = $fieldParts[1];

            if ($type == 'classificationstore') {
                $fieldname = $fieldParts[2];
                $groupKeyId = explode('-', $fieldParts[3]);
                $groupId = $groupKeyId[0];
                $keyId = $groupKeyId[1];

                $groupConfig = DataObject\Classificationstore\GroupConfig::getById($groupId);
                $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);

                $field = $fieldname . '~' . $groupConfig->getName() . '~' . $keyConfig->getName();
            }
        }

        return $field;
    }

    private function extractLanguage(Request $request)
    {
        $requestedLanguage = $request->get('language');
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        return $requestedLanguage;
    }
}

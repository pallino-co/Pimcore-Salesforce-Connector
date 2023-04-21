<?php

namespace Syncrasy\PimcoreSalesforceBundle\Services;

use Pimcore\Model\DataObject\Service;
use Syncrasy\PimcoreSalesforceBundle\Lib\Export\DataFetcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExportService
 * @package DistributionHubBundle\Services
 */
class ExportService
{
    /**
     * operator definition
     * @var array
     */
    private $helperDefinition = [];


    /**
     * the field names to be exported
     * @var array
     */
    private $fields = [];


    private $templateColumns = [];


    /**
     * preparing fields array (name of all the fields in column configuration) and
     * helperDefinition array (definition of operators in the column configuration)
     * which would be used to fetch the data for export
     *
     * @param array $columnAttributeMapping
     */


    public function prepareFieldsAndHelperDefinition(array $columnAttributeMapping)
    {
        $selectedGridColumnAssoc = $columnAttributeMapping['selectedGridColumns'];
        $templateColumns = $columnAttributeMapping['templateAttribute'];
        $templateColumnsWithLabel = [];
        $fields = [];
        $helperDefinition = [];
        foreach ($selectedGridColumnAssoc as $key => $selectedGridColumn) {
            if ($selectedGridColumn['attributes']['class'] != 'Ignore') {
                $templateColumnsWithLabel[$selectedGridColumn['key']] = $templateColumns[$key];
                $fields[trim($templateColumns[$key])] = $selectedGridColumn['key'];
                if (array_key_exists('isOperator', $selectedGridColumn) && $selectedGridColumn['isOperator']) {
                    $helperDefinition[$selectedGridColumn['key']] = json_decode(json_encode($selectedGridColumn));
                }
            }
        }
        $this->fields = $fields;
        $this->helperDefinition = $helperDefinition;
        $this->templateColumns = $templateColumnsWithLabel;
    }


    /**
     * trying to mimic DataObject\Service::getHelperDefinitions();
     *
     * @return array
     */
    public function getHelperDefinitions(): array
    {
        return $this->helperDefinition;
    }

    /**
     * trying to mimic the fields which are passed to the pimcore grid proxy or csv generator
     *
     * @return array
     */
    public function getFieldsForExport(): array
    {
        return $this->fields;
    }


    public function getCsvDataForExport($object, $fields, $language, $addTitles = true)
    {
        $request = new Request(['language' => $language]);
        $dataFetcher = new DataFetcher();
        $data = $dataFetcher->getCsvData($request, $object, $fields, $this->getHelperDefinitions(), $addTitles);
        $dataSalesforce = [];
        $object = new \stdClass();
        forEach($data as $key=>$value){

            if($this->templateColumns[$key] == 'Id'){
                $object->id = $value;
            }
            if(is_array($value) && count($value) == 1){
                $dataSalesforce[$this->templateColumns[$key]] = $value[0];
            }else{
                $dataSalesforce[$this->templateColumns[$key]] = $value;
            }
        }
        $object->jsonData = $dataSalesforce;
        return $object;
    }


}

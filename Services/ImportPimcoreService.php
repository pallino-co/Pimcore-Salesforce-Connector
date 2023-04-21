<?php


namespace Syncrasy\PimcoreSalesforceBundle\Services;


use Symfony\Component\Console\Helper\ProgressBar;
use Syncrasy\PimcoreSalesforceBundle\Services\ImportService as Service;
use Symfony\Component\HttpFoundation\Request;

class ImportPimcoreService
{



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

    public function setImportDataForPimcore($pimcoreClassName,$parentFolderId, $fields, $language,$records ,$fieldforsfid,$output,$addTitles = true){


        $request = new Request(['language' => $language]);
        $helperDefinitions = $this->getHelperDefinitions();
        $classNameListing = '\\Pimcore\\Model\\DataObject\\' . ucfirst($pimcoreClassName).'\\Listing';
        $className = '\\Pimcore\\Model\\DataObject\\' . ucfirst($pimcoreClassName);
        $progressBar = new ProgressBar($output,count($records));
        $progressBar->start();
        foreach ($records as $record){
            $elementList = new $classNameListing();
            $elementList->setCondition("$fieldforsfid = ?", [$record->{'Id'}]);
            $elementList->setUnpublished(true);
            $elementObjects = $elementList->load();
            if(!empty($elementObjects)){
                $element = $elementObjects[0];
            }else{
                $element = new $className();
            }
            foreach ($fields as $key => $field) {
                $fieldDefinition = $element->getClass()->getFieldDefinition($field);
                if (Service::isHelperGridColumnConfig($field)) {
                    if ($helperDefinitions[$field]) {
                        if(property_exists($record,$key)) {
                            $value = Service::calculateCellValues($element, $record->{$key}, $helperDefinitions, $field, ['language' => $language]);
                        }
                    }
                }else{
                    if(property_exists($record,$key)) {
                        $element->set($field, $record->{$key}, $language);
                    }
                }
            }
            $element->set($fieldforsfid,$record->{'Id'});
            $element->setParentId($parentFolderId);
            $element->setKey($record->{'Id'});
            $element->setPublished(1);
            $element->save();
            $progressBar->advance();
        }
        $progressBar->finish();

    }

}

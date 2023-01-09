<?php

namespace Syncrasy\PimcoreSalesforceBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Syncrasy\PimcoreSalesforceBundle\Model\Mapping;
use Syncrasy\PimcoreSalesforceBundle\Services\Sfconnect;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject;
use Syncrasy\PimcoreSalesforceBundle\Services;

/**
 * @package PimcoreSalesforceBundle\Controller
 * @Route("/admin/pimcoresalesforce/default")
 */
class DefaultController extends AdminController
{
    protected const SUCCESS = 'success';
    protected const ERROR = 'error';
    protected const LIMIT = 'limit';
    protected const CLASS_NAME = 'Channel';
    protected const MESSAGE = 'message';



    /**
     * @Route("/pimfields/{class_name}", name="get_pimfields")
     */
    public function getpimfieldsAction(Request $request)
    {

        $pimClass = $request->get('class_name');
        $fields = ClassDefinition::getById($pimClass);
        $options = [];
        if ($fields) {
            foreach ($fields->getFieldDefinitions() as $field) {

                if ($field->getFieldtype() == 'localizedfields') {
                    foreach ($field->getFieldDefinitions() as $child) {
                        $options[] = array("name" => $child->getTitle(), "id" => $child->getName());
                    }
                } else {
                    $options[] = array("name" => $field->getTitle(), "id" => $field->getName());
                }
            }
        }
        $result = array('fields' => $options);
        echo json_encode($result);
        die;
    }

    /**
     * @Route("/sffields/{class_name}", name="get_sffields")
     */
    public function getsffieldsAction(Request $request)
    {

        $sfClass = $request->get('class_name');
        $sfObject = new Sfconnect();
        if($sfObject->authData) {
            $options = $sfObject->getObjectsFields($sfClass);
            $result = array('objects' => $options);
            echo json_encode($result);
        }
        die;
    }

    /**
     * @Route("/sf-object", name="get_sfobject")
     */
    public function getSfObjectAction(Request $request)
    {


        $sfObject = new Sfconnect();
        if($sfObject->authData) {
            $options = $sfObject->getObjects();
            $result = array('objects' => $options);
            echo json_encode($result);
        }
        die;
    }

    /**
     * @Route("/pim-object", name="get_pimobject")
     */
    public function getPimObjectAction(Request $request)
    {


        $list = new ClassDefinition\Listing();
        $list->load();
        $options = [];
        if ($list) {
            foreach ($list->getClasses() as $class) {
                if ($class->getId() != 'salesforce_setup') {
                    $options[] = array("name" => $class->getName(), "id" => $class->getId());
                }
            }
        }
        $result = array('classes' => $options);
        echo json_encode($result);
        die;
    }

     /**
     * @Route("/save-basic-config")
     */
    public function saveBasicConfig(Request $request)
    {
        $mappingId = $request->get('mappingId');
        $pimcoreClassId = $request->get('pimcoreClassId'.$mappingId);
        $salesforceObjectId = $request->get('salesforceObjectId'.$mappingId);
        $pimUniqueField = $request->get('pimUniqueField'.$mappingId);
        $sfUniqueField = $request->get('sfUniqueField'.$mappingId);
        $fieldForSfId = $request->get('fieldForSfId'.$mappingId);
        $mappingJson = $request->get('mappingJson');
        $lang = $request->get('lang');

        $mapping = Mapping::getById($mappingId);
        if($mapping){
            if($pimcoreClassId ){
                $mapping->setPimcoreClassId($pimcoreClassId);
            }
            if($salesforceObjectId ){
                $mapping->setSalesforceObject($salesforceObjectId );
            }
            if($pimUniqueField ){
                $mapping->setPimcoreUniqueField($pimUniqueField);
            }
            if($sfUniqueField ){
                $mapping->setSalesforceUniqueField($sfUniqueField );
            }
            if($fieldForSfId ){
                $mapping->setFieldForSfId($fieldForSfId );
            }
            if($mappingJson){
                $mapping->setColumnAttributeMapping($mappingJson);
            }
            if($lang){
                $mapping->setLanguage($lang);
            }
            $mapping->save();
            return $this->json([
                'success' => true
            ]);
        }else {
            $msg = $this->trans('psc_channel_not_found_relaod_admin');
            return $this->json(['success' => false, 'msg' => $msg]);
        }
    }


    /**
     * @Route("/get-available-languages")
     * @return JsonResponse
     */
    public function getAvailableLanguagesAction() {

        $locales = \Pimcore\Tool::getSupportedLocales();
        $availableLanguages = \Pimcore\Tool::getValidLanguages();
        $fieldArray = array();

        foreach ($availableLanguages as $language) {
            $fieldArrayTemp = array();
            $fieldArrayTemp['name'] = $locales[$language];
            $fieldArrayTemp['value'] = $language;
            $fieldArray[] = $fieldArrayTemp;
        }

        return $this->json(["languages" => $fieldArray]);
    }

    /**
     * @Route("/get-class-definition-for-column-config", methods={"GET"})
     *
     * @param Request $request
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     * @throws Exception
     */
    public function getClassDefinitionForColumnConfigAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById($request->get('id'));
        $objectId = intval($request->get('oid'));
        $filteredDefinitions = DataObject\Service::getCustomLayoutDefinitionForGridColumnConfig($class, $objectId);
        $layoutDefinitions = isset($filteredDefinitions['layoutDefinition']) ? $filteredDefinitions['layoutDefinition'] : false;
        $filteredFieldDefinition = isset($filteredDefinitions['fieldDefinition']) ? $filteredDefinitions['fieldDefinition'] : false;
        $class->setFieldDefinitions([]);
        $result = [];
        $result['objectColumns']['childs'] = $layoutDefinitions->getChilds();
        $result['objectColumns']['nodeLabel'] = 'object_columns';
        $result['objectColumns']['nodeType'] = 'object';
        Services\ClassificationStoreService::updateObjectLayout($filteredDefinitions);
        Services\ClassificationStoreService::setBricksLayout($class, $result, $filteredFieldDefinition);

      return $this->adminJson($result);
    }

    /**
     * @Route("/get-classification-store-for-column-config", methods={"GET"})
     *
     * @param Request $request
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     * @throws Exception
     */
    public function getClassificationStoreForColumnConfigAction(Request $request){
        $request =  Services\ClassificationStoreService::getStore($request->get('id'));
        return $this->adminJson($request);
     }

     /**
     * Get sheet header columns
     * @Route("/mapping-header-import")
     * @param Request $request
     * @return string|JsonResponse
     */
    public function columnHeaderImportAction(Request $request) {
        $mappingId = intval($request->get('id'));
        $rowsColumns = array();
        $success = false;
        $config = [];
        try {
            $rowsColumns = Services\MappingService::getSheetColumns($mappingId);
        } catch (\Exception $e) {
           return $this->json([self::SUCCESS => false, self::MESSAGE => $e->getMessage()]);
        }
        $success = ((isset($rowsColumns['col']) && count($rowsColumns['col']) > 0));
        $msg = (isset($rowsColumns['col']) && count($rowsColumns['col']) > 0) ? $success : "psc_invalid_header_row_no";
        return $this->json([self::SUCCESS => $success, 'rowColumns' => $rowsColumns['col'], "config" => $rowsColumns['config'], self::MESSAGE => $msg]);
    }


}

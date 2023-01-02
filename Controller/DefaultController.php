<?php

namespace Syncrasy\PimcoreSalesforceBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syncrasy\PimcoreSalesforceBundle\Model\Mapping;
use Syncrasy\PimcoreSalesforceBundle\Services\Sfconnect;
use Syncrasy\PimcoreSalesforceBundle\Services\CommonService;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject;

/**
 * @package PimcoreSalesforceBundle\Controller
 * @Route("/admin/pimcoresalesforce/default")
 */
class DefaultController extends AdminController
{

    

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
        $options = $sfObject->getObjectsFields($sfClass);
        $result = array('objects' => $options);
        echo json_encode($result);
        die;
    }

    /**
     * @Route("/sf-object", name="get_sfobject")
     */
    public function getSfObjectAction(Request $request)
    {


        $sfObject = new Sfconnect();

        $options = $sfObject->getObjects();
        $result = array('objects' => $options);
        echo json_encode($result);
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
            $mapping->save();
            return $this->json([
                'success' => true
            ]);
        }else {
            $msg = $this->trans('dHub_channel_not_found_relaod_admin');
            return $this->json(['success' => false, 'msg' => $msg]);
        }
    }
}

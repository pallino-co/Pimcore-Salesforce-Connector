<?php

namespace Syncrasy\PimcoreSalesforceBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\SelesForceSetup as SalesForceSetupModel;
use Pimcore\Model\DataObject\User as UserModel;
use Pimcore\Model\DataObject\Contact as ContactModel;
use Pimcore\Model\DataObject\Account as AccountModel;
use Syncrasy\PimcoreSalesforceBundle\Service\Sfconnect;
use Syncrasy\PimcoreSalesforceBundle\Service\CommonService;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject;

class DefaultController extends FrontendController {

    /**
     * @Route("/salesforceconnect")
     */
    public function indexAction(Request $request) {

       

        die('index');

        $sfObject = new Sfconnect();
        $sfObject->getObjectsFields('Account');
        $k = $sfObject->authData;

        $clsMappingExists1 = new AccountModel\Listing();
        $clsMappingExists1->setCondition('AccountNumber = ? And o_type = ?', ['5555', 'object']);
        $clsMappingExists1->setLimit(1);
        $clsMappingExists1->load();
        $clsObje1 = '';
        if ($clsMappingExists1) {
            foreach ($clsMappingExists1->getObjects() as $obj1) {
                $clsObje1 = $obj1;
            }
        }


        $clsMappingExists = new SalesForceSetupModel\Listing();
        $clsMappingExists->setCondition('pimcoreclass = ? And o_type = ?', ['account', 'object']);
        $clsMappingExists->setLimit(1);
        $clsMappingExists->load();
        $clsObje = '';
        if ($clsMappingExists) {
            foreach ($clsMappingExists->getObjects() as $obj) {
                $clsObje = $obj;
            }
        }

        // print_r($clsObje->getFieldmapping());

        $data = [];
        foreach ($clsObje->getFieldmapping() as $val) {

            ///$pimField = 'get'.$val['pimcoreclassfield']->getData();
            //$sfField = $val['salesforceobjectfield']->getData();
            $data[] = $val['salesforceobjectfield']->getData();
        }
        p_r($data);
        if (is_object($clsObje) && is_object($clsObje1)) {

            print_r($clsObje1);
            $s = 'SalesforceId';
            $k = 'get' . $s;
            echo $clsObje1->$k();
            die;
            $data = CommonService::prepareData($clsObje, $clsObje1);
            $sObjectType = $clsObje->getsalesforceobject();
            $uniqueField = $clsObje->getpimuniquefield();


            if (count($data)) {

                /* $uniqueFieldVal = $data[$uniqueField];
                  $query =   $sfObject->recordExistsQuery($sObjectType, $uniqueField, $uniqueFieldVal);
                  $id = $sfObject->query($query);
                  if($id) {
                  $sfObject->update($sObjectType, $data, $id);
                  }else { */
                $s = $sfObject->insert($sObjectType, $data);

                var_dump($s[0]->success);
                p_r($s);
                die;
                /* } */
            }
        }


        die('dd');
        return new Response('Hello world from salesforceconnect');
    }

    /**
     * @Route("/pimfields/{class_name}", name="get_pimfields")
     */
    public function getpimfieldsAction(Request $request) {

        $pimClass = $request->get('class_name');
        $fields = \Pimcore\Model\DataObject\ClassDefinition::getById($pimClass);
        $options = [];
        if ($fields) {
            foreach ($fields->getFieldDefinitions() as $field) {
                if ($field->getFieldtype() != 'fieldcollections') {
                    if ($field->getFieldtype() == 'objectbricks') {
                        foreach ($field->allowedTypes as $bricks) {
                            $brickDef = \Pimcore\Model\DataObject\Objectbrick\Definition :: getByKey($bricks);
                            foreach ($brickDef->getFieldDefinitions() as $child) {
                                $options[] = array("key" => $field->getTitle() . '-' . $bricks . '-' . $child->getName(), "value" => $field->getName() . '-' . $bricks . '-' . $child->getName());
                            }
                        }
                    }
                    if ($field->getFieldtype() == 'localizedfields') {
                        foreach ($field->getFieldDefinitions() as $child) {
                            $options[] = array("key" => $child->getTitle(), "value" => $child->getName());
                        }
                    } else {
                        $options[] = array("key" => $field->getTitle(), "value" => $field->getName());
                    }
                }
            }
        }
        $options[] = array("key" => 'Parent Id', "value" => 'parent');
        $result = array('success' => 1, 'data' => $options);
        echo json_encode($result);
        die;
    }

    /**
     * @Route("/sffields/{class_name}", name="get_sffields")
     */
    public function getsffieldsAction(Request $request) {

        $sfClass = $request->get('class_name');
        $sfObject = new Sfconnect();
        $options = $sfObject->getObjectsFields($sfClass);

        $result = array('success' => 1, 'data' => $options);
        echo json_encode($result);
        die;
    }

    /**
     * @Route("/sf-object", name="get_sfobject")
     */
    public function getSfObjectAction(Request $request) {


        $sfObject = new Sfconnect();

        $options = $sfObject->getObjects();
        $result = array('success' => 1, 'data' => $options);
        echo json_encode($result);
        die;
    }

    /**
     * @Route("/pim-object", name="get_pimobject")
     */
    public function getPimObjectAction(Request $request) {


        $list = new ClassDefinition\Listing();
        $list->load();
        $options = [];
        if ($list) {
            foreach ($list->getClasses() as $class) {
                if ($class->getId() != 'salesforce_setup') {
                    $options[] = array("key" => $class->getName(), "value" => $class->getId());
                }
            }
        }
        $result = array('success' => 1, 'data' => $options);
        echo json_encode($result);
        die;
    }

}

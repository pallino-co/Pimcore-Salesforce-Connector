<?php

namespace Syncrasy\SalesforceBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use SalesforceconnectBundle\Service\CommonService;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomRestController extends FrontendController {

    /**
     * @Route("/api/get-data")
     */
    public function defaultAction(Request $request) {
        $className = $request->get('cname');
        $fieldName = $request->get('fname');
        $fieldValue = $request->get('fvalue');

        $allPimClass = CommonService::getPimClasses();
        $allClassFieldArray = CommonService::getPimClassFields($className);
        $allClassField = array_keys($allClassFieldArray);
        if ($className == '') {
            return new JsonResponse(["error" => true, "errorMsg" => "Please provide Class Name"], 200);
        }
        if (!in_array($className, $allPimClass)) {
            return new JsonResponse(["error" => true, "errorMsg" => "ClassName " . $className . " does not Exists"], 200);
        }

        if ($fieldName != '' && $fieldValue != '' && $fieldName !='oo_id') {
            if (!in_array($fieldName, $allClassField)) {
                return new JsonResponse(["error" => true, "errorMsg" => "FieldName " . $fieldName . " does not Exists"], 200);
            }
        }

        $classObjectString = 'Pimcore\Model\DataObject\\' . ucfirst($className) . '\Listing';
        $entries = new $classObjectString();
        if ($fieldName != '' && $fieldValue != '') {
            if ($allClassFieldArray[$fieldName] != 'manyToManyObjectRelation') {
                $entries->setCondition("$fieldName = ?", [$fieldValue]);
            } else {
                $entries->setCondition("$fieldName like ?", ['%,' . $fieldValue . ',%']);
            }
        }

        $data = $entries->load();
        $result = [];
        foreach ($data as $val) {
            $array = [];
            foreach ($allClassField as $field) {
                $getfield = 'get' . $field;
                $value = $val->$getfield();
                if ($value instanceof \Pimcore\Model\DataObject\Fieldcollection) {
                    $array[$field] = null;
                    if (count($value->getItems()) > 0){
                        $allClassFieldObj = CommonService::getPimClassFields('Address', 'Fieldcollection');
                        foreach ($value->getItems() as $objInn) {
                            $arrayInerObject = [];
                            foreach ($allClassFieldObj as $key => $fieldInn) {
                                try {
                                    $getfieldInn = 'get' . $key;
                                    $valueInn = $objInn->$getfieldInn();
                                    if (!is_array($valueInn)) {
                                        $arrayInerObject[$key] = $valueInn;
                                    }
                                } catch (\Exception $e) {
                                    
                                }
                            }
                            $array[$field] = $arrayInerObject;
                        }
                        
                    }
                } else if (!is_array($value)) {
                    $array[$field] = $value;
                } else if (is_array($value)) {
                    $arrayObject = [];
                    foreach ($value as $objInn) {
                        $arrayInerObject = [];
                        $allClassFieldObj = CommonService::getPimClassFields($objInn->getclassId());
                        foreach ($allClassFieldObj as $key => $fieldInn) {
                            try {
                                $getfieldInn = 'get' . $key;
                                $valueInn = $objInn->$getfieldInn();
                                if (!is_array($valueInn)) {
                                    $arrayInerObject[$key] = $valueInn;
                                }
                            } catch (\Exception $e) {
                                
                            }
                        }
                        $arrayObject[] = $arrayInerObject;
                    }
                    $array[$field] = $arrayObject;
                }
            }
            $result[] = $array;
        }
        return new JsonResponse($result, 200);
    }

}

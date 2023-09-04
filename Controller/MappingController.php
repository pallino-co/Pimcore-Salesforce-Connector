<?php

namespace Syncrasy\PimcoreSalesforceBundle\Controller;

use Exception;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Tool\Admin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Syncrasy\PimcoreSalesforceBundle\Model\Mapping;
use Syncrasy\PimcoreSalesforceBundle\Services\MappingService;

/**
 * @package PimcoreSalesforceBundle\Controller
 * @Route("/admin/pimcoresalesforce/mapping")
 */
class MappingController extends AdminController

{
    public const CONFIG_NAME = 'plugin_psc';
    protected const SUCCESS = 'success';
    protected const ERROR = 'error';
    protected const LIMIT = 'limit';
    protected const CLASS_NAME = 'Mapping';
    protected const MESSAGE = 'message';


    /**
     * add mapping
     * @Route("/tree")
     * @param Request $request
     * @return JsonResponse
     */
    public function getMappingTreeAction(Request $request)
    {
        $type = $request->get('type');
        $obj = new Mapping\Listing();
        $obj->setCondition("type = ?",[$type]);
        $mapping = $obj->load();
        $mappings = [];
        foreach ($mapping as $row) {
            $mappings[] = $this->buildItem($row);
        }
        return $this->json(['nodes' => $mappings]);
    }

    /**
     * add mapping
     * @Route("/add")
     * @param Request $request
     * @return JsonResponse
     */
    public function mappingTreeAddAction(Request $request)
    {
        try {
            $name = $request->get('name');
            $type = $request->get('type');
            $object = Mapping::getByName(trim($name));
            $this->checkPermission(self::CONFIG_NAME);
            if (!$object instanceof Mapping) {
                $newObject = new Mapping();
                $newObject->setName($name);
                $newObject->setType($type);
                $mappingAttributes = $this->encodeJson(MappingService::getMappingInfo([]));
                $newObject->setLanguage(Admin::getCurrentUser()->getLanguage());
                $newObject->setColumnAttributeMapping($mappingAttributes);
                $userOwner = (int)Admin::getCurrentUser()->getId();
                $newObject->setUserOwner($userOwner);
                $newObject->save();
                return $this->json([self::SUCCESS => true, "id" => $newObject->getId(), 'message' => '']);
            } else {
                $message = 'prevented creating object because object with same path+key already exists';
                return $this->json([self::SUCCESS => false, self::MESSAGE => $message, 'id' => $object->getId()]);
            }
        } catch (Exception $ex) {
            return $this->json([self::SUCCESS => false, self::MESSAGE => $ex->getMessage()]);
        }
    }

    /**
     * add mapping
     * @Route("/get")
     * @param Request $request
     * @return JsonResponse
     */

    public function mappingGetAction(Request $request)
    {

        try {
            $id = $request->get('id');
            $object = Mapping::getById(trim($id));
            
            $data[] = $this->buildItem($object);
            $data['lang'] = !empty($object->getLanguage()) ? $object->getLanguage() : Admin::getCurrentUser()->getLanguage();
            $data['columnAttributeMapping'] = json_decode(json_encode($object->getColumnAttributeMapping()), true);

            return $this->json(['result' => true, 'general' => ['o_id' => $object->getId(), 'o_key' => $object->getName()], 'data' => $data, "msg" => '']);
        } catch (Exception $ex) {
            return $this->json([self::SUCCESS => false, self::MESSAGE => $ex->getMessage()]);
        }
    }

    /**
     * @param Mapping $mapping
     *
     * @return array
     */
    private function buildItem($mapping): array
    {
        return [
            'id' => $mapping->getId(),
            'text' => $mapping->getName(),
            'key' => $mapping->getName(),
            'pimcoreClassId' => $mapping->getPimcoreClassId(),
            'pimcoreUniqueField' => $mapping->getPimcoreUniqueField(),
            'salesforceObject'=> $mapping->getSalesforceObject(),
            'salesforceUniqueField' => $mapping->getSalesforceUniqueField(),
            'fieldForSfId' => $mapping->getFieldForSfId(),
            'type' => $mapping->getType(),
            'importFilePath' => $mapping->getImportFileUploadPath(),
            'importFilePathId' => $mapping->getImportFilePathId()
        ];
    }

    /**
     * delete mapping
     * @Route("/delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        $this->checkPermission(self::CONFIG_NAME);
        try {
            $id = intval($request->query->get('id'));
            $object = Mapping::getById($id);
            $object->delete();
            return $this->json([self::SUCCESS => true]);
        } catch (Exception $ex) {
            return $this->json([self::SUCCESS => false]);
        }
    }

    /**
     * rename mapping
     * @Route("/rename")
     * @param Request $request
     * @return JsonResponse
     */
    public function renameAction(Request $request)
    {
        $this->checkPermission(self::CONFIG_NAME);
        try {
            $id = intval($request->query->get('id'));
            $name = trim($request->query->get('name'));
            $object = Mapping::getByName(trim($name));
            if(!$object instanceof Channel){
                $object = Mapping::getById($id);
                $object->setName($name);
                $object->save();
                return $this->json([self::SUCCESS => true, "id" => $object->getId(), self::MESSAGE => '']);
            } else {
                $message = 'prevented renaming object because object with same path+key already exists';
                return $this->json([self::SUCCESS => false, self::MESSAGE => $message, 'id' => $object->getId(),]);
            }
        } catch (Exception $ex) {
            return $this->json([self::SUCCESS => false, self::MESSAGE => $ex->getMessage()]);
        }
    }
}

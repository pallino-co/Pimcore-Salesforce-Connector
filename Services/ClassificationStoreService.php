<?php

namespace Syncrasy\PimcoreSalesforceBundle\Services;

use Syncrasy\PimcoreSalesforceBundle\Lib\PSCLogger;
use Doctrine\DBAL\DBALException;
use Pimcore\Db;
use Pimcore\Model\DataObject;

/**
 * Class ClassificationStoreService
 * @package Syncrasy\PimcoreSalesforceBundle\Services
 */
class ClassificationStoreService
{
    /**
     * @var \Pimcore\Db\Connection|\Pimcore\Db\ConnectionInterface
     */
    private $db='';


    /**
     * ClassificationStoreService constructor.
     */
    public function  __construct() {
        $this->db = Db::get();
    }

    /**
     * Returns all classification store details associated with class based on class Id
     *
     * @static
     *
     * @param $classId
     * @return string
     * @throws \Exception
     */
    public static function getStore($classId)
    {
        $class = DataObject\ClassDefinition::getById($classId);
        $classificationStore = [];
        if($class){
           $definitions = $class->getFieldDefinitions();
           foreach ($definitions as $definition){
               if ($definition->fieldtype === "classificationstore") {
                   $cDetails =  ['name' => $definition->getName(),'title' => $definition->getTitle(), 'storeId' => $definition->getStoreId()];
                   array_push($classificationStore, $cDetails);
               }
           }
          return  $classificationStore;
        }else{
            throw new \Exception('psc_class_not_found');
        }
    }

    /**
     * Get Group Keys based on store Id
     * @param $filter
     * @param $start
     * @param $limit
     * @param $storeId
     * @param $name
     * @param $editor
     * @param $translator
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getGroupKeys($filter, $start, $limit, $storeId, $name, $editor, $translator) {
        $classificationStoreKeys = [];
        $total = $this->getTotalCount($filter, $storeId, $editor);
        $sqlResult = $this->getRecords($filter, $start, $limit, $storeId, $editor);
        foreach ($sqlResult as $row) {
            $keyDetails = array();
            $id = (int) $row['KeyId'];
            $groupId = (int) $row['groupId'];
            $keyDetails['id'] = (int) $id."-".$groupId;
            $keyDetails['text'] = $translator->trans($row['groupName'], [], 'admin')."~".$translator->trans($row['KeyName'], [], 'admin');
            $keyDetails['key'] = "~classificationstore~".$name."~".$groupId."-".$id;
            $keyDetails['type'] = 'data';
            $keyDetails['leaf'] = true;
            $keyDetails['allowDrag'] = true;
            $keyDetails['dataType'] = $row['KeyType'];
            $keyDetails['iconCls'] = ($row['KeyType'] == 'advancedQuantityValue') ? "pimcore_icon_quantityValue" : "pimcore_icon_" . $row['KeyType'];
            $keyDetails['expanded'] = false;
            $keyDetails['brickDescriptor'] = 'brickDescriptor';
            $keyDetails['layout'] = $keyDetails;
            array_push($classificationStoreKeys, $keyDetails);
        }
        return ['key'=>$classificationStoreKeys, 'count'=>$total];
    }

    /**
     *  Get total store attributes count based on filter and store Id
     * @param $filter
     * @param $storeId
     * @param string $editor
     * @return int|mixed
     */
    public function getTotalCount($filter, $storeId, $editor=""){
        $field = 'lcase(name)';
        $filter = strtolower($filter);
        $param=[];
        if(!empty($editor)){
            $field = 'k.title';
        }
        if(!empty($filter)) {
            $sql = 'SELECT count(id) as total FROM classificationstore_relations as kg INNER JOIN classificationstore_keys k ON k.id=kg.keyId WHERE k.storeId = :storeId AND '.$field.' LIKE :title';
            $param+=[":storeId"=>$storeId];
            $param+=[":title"=>$filter . '%'];
        }else{
            $sql = 'SELECT count(id) as total FROM classificationstore_relations as kg INNER JOIN classificationstore_keys k ON k.id=kg.keyId WHERE k.storeId = :storeId';
            $param+=[":storeId"=>$storeId];
        }
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($param);
            $result =  $stmt->fetch();
            return $result['total'] ?? 0;
        }catch(DBALException $ex) {
            PSCLogger::log("Classifications store keys total count error:".$ex->getMessage(), PSCLogger::CRITICAL);
            return 0;
        }catch(\Exception $ex) {
            PSCLogger::log("Classifications store keys total count error:".$ex->getMessage(), PSCLogger::CRITICAL);
            return 0;
        }
    }

    /**
     * Get all  store attributes based on filter, limit and store Id
     * @param $filter
     * @param $offset
     * @param $limit
     * @param $storeId
     * @param string $editor
     * @return array|mixed[]
     */
    public function getRecords($filter, $offset, $limit, $storeId, $editor=""){
        $wfield = 'lcase(name)';
        $param=[];
        $filter = strtolower($filter);
        $field = "k.id as KeyId, k.title as KeyName, k.type as KeyType, k.name, kg.groupId, g.name as groupName";
        if(!empty($editor)){
            $wfield = 'k.title';
            $field = "k.id as KeyId, k.title as KeyName, k.type as KeyType, k.name, kg.groupId, g.name as groupName";
        }
        if(!empty($filter)) {
            $sql  = "SELECT   $field  FROM classificationstore_relations as kg INNER JOIN classificationstore_keys k ON k.id=kg.keyId INNER JOIN classificationstore_groups as g ON g.id=kg.groupId WHERE k.storeId = :storeId AND $wfield LIKE :title ORDER BY k.name ASC limit $offset,$limit";
            $param+=[":storeId"=>$storeId];
            $param+=[":title"=>$filter . '%'];
        }else{
          $sql  = "SELECT $field  FROM classificationstore_relations as kg INNER JOIN classificationstore_keys k ON k.id=kg.keyId INNER JOIN classificationstore_groups as g ON g.id=kg.groupId WHERE k.storeId = :storeId ORDER BY k.name ASC limit $offset,$limit";
          $param+=[":storeId"=>$storeId];
        }
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($param);
            return $stmt->fetchAll() ?? [];
        }catch(DBALException $ex) {
            PSCLogger::log("Classifications store keys error:".$ex->getMessage(), PSCLogger::CRITICAL);
            return [];
        }catch(\Exception $ex) {
            PSCLogger::log("Classifications store keys error:".$ex->getMessage(), PSCLogger::CRITICAL);
            return [];
        }
    }

    /**
     * Update object column layout
     * @param $filteredDefinitions
     */
    public static function  updateObjectLayout($filteredDefinitions){
        $childArray = [];
        foreach ($filteredDefinitions["layoutDefinition"]->getChilds() as $childPanel) {
            foreach ($childPanel->getChilds() as $key => $child) {
                if(method_exists($child, "getChilds")){
                    foreach ($child->getChilds() as $key => $childLevel) {
                        if ($childLevel->fieldtype !== "classificationstore") {
                            $childArray[] = $childLevel;
                        }
                    }
                }else{
                    if ($child->fieldtype !== "classificationstore") {
                        $childArray[] = $child;
                    }
                }
            }
            $childPanel->setChilds($childArray);
        }
    }

    /**
     * Set Object Bricks layout
     * @param $class
     * @param $result
     * @param $filteredFieldDefinition
     */
    public static function setBricksLayout($class, &$result, $filteredFieldDefinition){
        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $classDefs = $brickDefinition->getClassDefinitions();
            if (!empty($classDefs)) {
                foreach ($classDefs as $classDef) {
                    if ($classDef['classname'] == $class->getName()) {
                        $fieldName = $classDef['fieldname'];
                        if ($filteredFieldDefinition && !$filteredFieldDefinition[$fieldName]) {
                            continue;
                        }
                        $key = $brickDefinition->getKey();
                        $result[$key]['nodeLabel'] = $key;
                        $result[$key]['brickField'] = $fieldName;
                        $result[$key]['nodeType'] = 'objectbricks';
                        $result[$key]['childs'] = $brickDefinition->getLayoutDefinitions()->getChildren();
                        break;
                    }
                }
            }
        }
    }
}

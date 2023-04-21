<?php


namespace Syncrasy\PimcoreSalesforceBundle\Services;


use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\AbstractObject;

class ImportService extends Service
{
    /**
     * @param AbstractObject $object
     * @param array $helperDefinitions
     * @param string $key
     * @param array $context
     *
     * @return \stdClass|array|null
     */
    public static function calculateCellValues($object,$value,$helperDefinitions, $key, $context = [])
    {
        $definition = $helperDefinitions[$key];
        $attributes = json_decode(json_encode($definition->attributes));
        try {
            self::doConfig($object, $value, $attributes, $key, $context = []);
        }catch (\Exception $e){
        }
        return null;
    }
    public static function doConfig($object,$value,$helperDefinitions, $key, $context = [])
    {
        if($helperDefinitions->type === 'operator'){
            if($helperDefinitions->class ==='LocaleSwitcher'){
                $config = self::localeSwitcher($object,$value,$helperDefinitions, $key, $context = []);
            }
            if($helperDefinitions->class ==='AnyGetter'){
                $config = self::AnyGetter($object,$value,$helperDefinitions, $key, $context = []);
            }
        }
        $object = new \stdClass();
        $object->value = $value;
        $object->attribute = $helperDefinitions->attribute;
        return $object;

    }
    public static function localeSwitcher($object,$value,$helperDefinitions, $key, $context = []){
        foreach($helperDefinitions->childs as $helperDefinition) {
            $config = self:: doConfig($object, $value, $helperDefinition, $key, $context = []);
            $object->set($config->attribute,$config->value,$helperDefinitions->local);
        }
    }

    public static function AnyGetter($object,$value,$helperDefinitions, $key, $context = []){
        foreach($helperDefinitions->childs as $helperDefinition) {
            $config = self:: doConfig($object, $value, $helperDefinition, $key, $context = []);
            $classNameListing = '\\Pimcore\\Model\\DataObject\\' . ucfirst($helperDefinitions->param1).'\\Listing';
            $elementList = new $classNameListing();
            $elementList->setCondition("$helperDefinitions->attribute = ?", [$config->value]);
            $elementList->setUnpublished(true);
            $elementObjects = $elementList->load();
            if(!empty($elementObjects)) {
                if ($helperDefinitions->isArrayType) {
                    $object->set($config->attribute, $elementObjects);
                } else {
                    $object->set($config->attribute, $elementObjects[0]);
                }
            }
        }
    }

}

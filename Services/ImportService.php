<?php


namespace Syncrasy\PimcoreSalesforceBundle\Services;


use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\Service;
use Pimcore\DataObject\GridColumnConfig\ConfigElementInterface;
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
        self::doConfig($object,$value,$attributes, $key, $context = []);
        return null;
    }
    public static function doConfig($object,$value,$helperDefinitions, $key, $context = [])
    {
        if($helperDefinitions->type === 'operator'){
            if($helperDefinitions->class ==='LocaleSwitcher'){
                $config = self::localeSwitcher($object,$value,$helperDefinitions->childs, $key, $context = []);
            }
        }
        if($helperDefinitions->type === 'value'){
            $object = new \stdClass();
            $object->value = $value;
            $object->attribute = $helperDefinitions->attribute;
            return $object;
        }
    }
    public static function localeSwitcher($object,$value,$helperDefinitions, $key, $context = []){
        foreach($helperDefinitions as $helperDefinition) {
            $config = self:: doConfig($object, $value, $helperDefinition, $key, $context = []);
            $object->set($config->attribute,$config->value);
        }
    }

}

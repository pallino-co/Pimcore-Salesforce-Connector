<?php

namespace Syncrasy\PimcoreSalesforceBundle\Installer;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Db;

class Installer extends SettingsStoreAwareInstaller {

    

    public function install() {

       
            $sourceInstallPath = __DIR__ ;
            $rootPath = substr($sourceInstallPath,0,strpos($sourceInstallPath,'src'));
            $this->installClasses();
            $this->installWebSiteSetting();
            \Pimcore\Tool\Console::runPhpScript('./bin/console assets:install');
           parent::install();
    }

    public function installClasses() {
        $this->createClass('SelesForceSetup', '/class_SelesForceSetup_export.json');
    }

    private static function createClass($classname, $filepath) {
        $sourceInstallPath = __DIR__ . '/Install';
        $class = new \Pimcore\Model\DataObject\ClassDefinition();
        $class->setName($classname);
        $json = file_get_contents($sourceInstallPath . $filepath);
        $success = \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        if (!$success) {
            throw new \Exception("Could not import {$classname} Class.");
        }
    }
    
    public function installWebSiteSetting() {
        $sourceInstallPath = __DIR__ . '/Install';
        $website_settings = file_get_contents($sourceInstallPath . '/website-settings.json');
        $website_settings = json_decode($website_settings, true);
        foreach ($website_settings as $name => $values) {
            $websitesetting = new \Pimcore\Model\WebsiteSetting();
            $websitesetting->setName($values['name']);
            $websitesetting->setType($values['type']);
            $websitesetting->setData($values['data']);
            $websitesetting->setSiteId($values['siteId']);
            $websitesetting->save();
        }
    }
   
    /**
     * {@inheritdoc}
     */
    public function uninstall() {
        $sourceInstallPath = __DIR__ ;
        $rootPath = substr($sourceInstallPath,0,strpos($sourceInstallPath,'src'));
        $this->deleteClasses();
        $this->deleteWebSiteSetting();
        parent::uninstall();
    }

    private function deleteClasses() {
        if (\Pimcore\Model\DataObject\ClassDefinition::getByName('SelesForceSetup')) {
            $topicClass = \Pimcore\Model\DataObject\ClassDefinition::getByName('SelesForceSetup');
            $topicClass->delete();
        }
    }

    
    public function deleteWebSiteSetting() {
        $sourceInstallPath = __DIR__ . '/Install';
        $website_settings = file_get_contents($sourceInstallPath . '/website-settings.json');
        $website_settings = json_decode($website_settings, true);
        foreach ($website_settings as $name => $values) {
            $websitesetting = \Pimcore\Model\WebsiteSetting::getByName($values['name']);
            if($websitesetting)
            $websitesetting->delete();
        }
    }

    
  /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }


}

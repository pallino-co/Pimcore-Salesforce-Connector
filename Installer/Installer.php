<?php

namespace Syncrasy\PimcoreSalesforceBundle\Installer;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Db;

class Installer extends SettingsStoreAwareInstaller
{


    public function install()
    {

        $sourceInstallPath = __DIR__;
        $rootPath = substr($sourceInstallPath, 0, strpos($sourceInstallPath, 'src'));
        $this->installClasses();
        $this->installWebSiteSetting();
        \Pimcore\Tool\Console::runPhpScript('./bin/console assets:install');
        parent::install();
    }

    /**
     * @return \Pimcore\Db\Connection|\Pimcore\Db\ConnectionInterface
     */
    protected function getDb()
    {
        return \Pimcore\Db::get();
    }

    public function installClasses()
    {

        $db = $this->getDb();
        $db->query("CREATE TABLE `syncrasy_salesforce_mapping` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `columnAttributeMapping` text NOT NULL,
                      `userOwner` int(11) DEFAULT NULL,
                      `language` varchar(45) NOT NULL,
                      `description` text DEFAULT NULL,
                      `creationDate` int(11) NOT NULL,
                      `modificationDate` int(11) NOT NULL,
                      `pimcoreClassId` varchar(80) NOT NULL,
                      `salesforceObject` varchar(80) NOT NULL,
                      `fieldForSfId` varchar(80) NOT NULL,
                      `pimcoreUniqueField` varchar(80) NOT NULL,
                      `salesforceUniqueField` varchar(80) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );

    }


    public function installWebSiteSetting()
    {
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
    public function uninstall()
    {
        $sourceInstallPath = __DIR__;
        $rootPath = substr($sourceInstallPath, 0, strpos($sourceInstallPath, 'src'));
        $this->deleteClasses();
        $this->deleteWebSiteSetting();
        parent::uninstall();
    }

    private function deleteClasses()
    {
        $db = $this->getDb();
        $db->query("DROP TABLE IF EXISTS `syncrasy_salesforce_mapping`;");
    }


    public function deleteWebSiteSetting()
    {
        $sourceInstallPath = __DIR__ . '/Install';
        $website_settings = file_get_contents($sourceInstallPath . '/website-settings.json');
        $website_settings = json_decode($website_settings, true);
        foreach ($website_settings as $name => $values) {
            $websitesetting = \Pimcore\Model\WebsiteSetting::getByName($values['name']);
            if ($websitesetting)
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

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
        $db->query("CREATE TABLE `application_logs` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      `pid` int(11) DEFAULT NULL,
                      `timestamp` datetime NOT NULL,
                      `message` text DEFAULT NULL,
                      `priority` enum('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
                      `fileobject` varchar(1024) DEFAULT NULL,
                      `info` varchar(1024) DEFAULT NULL,
                      `component` varchar(190) DEFAULT NULL,
                      `source` varchar(190) DEFAULT NULL,
                      `relatedobject` int(11) unsigned DEFAULT NULL,
                      `relatedobjecttype` enum('object','document','asset') DEFAULT NULL,
                      `maintenanceChecked` tinyint(1) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `component` (`component`),
                      KEY `timestamp` (`timestamp`),
                      KEY `relatedobject` (`relatedobject`),
                      KEY `priority` (`priority`),
                      KEY `maintenanceChecked` (`maintenanceChecked`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
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

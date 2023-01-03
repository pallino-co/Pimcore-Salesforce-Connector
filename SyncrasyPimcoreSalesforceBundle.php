<?php

namespace Syncrasy\PimcoreSalesforceBundle;


use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Syncrasy\PimcoreSalesforceBundle\Installer\Installer;


class SyncrasyPimcoreSalesforceBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    const BUNDLE_NAME = 'SyncrasyPimcoreSalesforceBundle';
    
    public function getJsPaths()
    {
        return [
            '/bundles/syncrasypimcoresalesforce/js/pimcore/startup.js',
            '/bundles/syncrasypimcoresalesforce/js/pimcore/panel/main.js',
            '/bundles/syncrasypimcoresalesforce/js/pimcore/panel/mappingLeftPanel.js',
            '/bundles/syncrasypimcoresalesforce/js/pimcore/panel/configItem.js',
            '/bundles/syncrasypimcoresalesforce/js/pimcore/panel/tabs/basicConfig.js',
            '/bundles/syncrasypimcoresalesforce/js/pimcore/panel/tabs/columnConfiguration.js',
            '/bundles/syncrasypimcoresalesforce/js/pimcore/panel/helpers/classTree.js'
        ];
    }

    public function getInstaller(){
        return $this->container->get(Installer::class);
    }

    protected function getComposerPackageName(): string
    {
        return 'syncrasy/pimcore-salesforce-bundle';
    }

    public function getNiceName()
    {
        return self::BUNDLE_NAME;
    }

}
<?php

namespace Syncrasy\PimcoreSalesforceBundle;


use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Syncrasy\PimcoreSalesforceBundle\Installer\Installer;

class SyncrasyPimcorePimcoreSalesforceBundle extends AbstractPimcoreBundle
{
    const BUNDLE_NAME = 'SyncrasyPimcorePimcoreSalesforceBundle';
    
    public function getJsPaths()
    {
        return [
            '/bundles/syncrasypimcoresalesforce/js/pimcore/startup.js'
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
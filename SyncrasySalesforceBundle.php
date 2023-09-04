<?php

namespace Syncrasy\SalesforceBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Syncrasy\SalesforceBundle\Installer\Installer;

class SyncrasySalesforceBundle extends AbstractPimcoreBundle
{
    const BUNDLE_NAME = 'SyncrasySalesforceBundle';

    public function getJsPaths()
    {
        return [
            '/bundles/syncrasysalesforce/js/pimcore/startup.js',
        ];
    }

    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }

    protected function getComposerPackageName(): string
    {
        return 'salesforce/syncrasy/salesforce-bundle';
    }

    public function getNiceName(): string
    {
        return self::BUNDLE_NAME;
    }
}

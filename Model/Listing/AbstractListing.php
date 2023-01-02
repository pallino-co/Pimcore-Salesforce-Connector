<?php

namespace Syncrasy\PimcoreSalesforceBundle\Model\Listing;


class AbstractListing extends \Pimcore\Model\Listing\AbstractListing
{

    public $items = array();


    public function isValidOrderKey($key)
    {
        return true;
    }


    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

}
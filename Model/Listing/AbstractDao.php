<?php

namespace Syncrasy\PimcoreSalesforceBundle\Model\Listing;


abstract class AbstractDao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    public function load()
    {

        $searchIds = $this->loadIdList();
        $searches = array();
        foreach ($searchIds as $id) {
            $className = static::CLASS_NAME;
            if ($savedSearch = $className::getById($id)) {
                $searches[] = $savedSearch;
            }
        }

        $this->model->setItems($searches);
        return $searches;
    }


    public function loadIdList()
    {
        $searchIds = $this->db->fetchCol("SELECT id FROM " . $this->db->quoteIdentifier(static::TABLE_NAME) . " " . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $searchIds;
    }

    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . $this->db->quoteIdentifier(static::TABLE_NAME) . " " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
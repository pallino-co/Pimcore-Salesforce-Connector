<?php

namespace Syncrasy\PimcoreSalesforceBundle\Model\Mapping;

use Syncrasy\PimcoreSalesforceBundle\Model\AbstractDao;

class Dao extends AbstractDao
{
    const TABLE_NAME = 'syncrasy_salesforce_mapping';

    


    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT * FROM " . $this->db->quoteIdentifier(static::TABLE_NAME) . " WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("record with id " . $id . " not found");
        }
        return $this->model->setValues($data);
    }

    public function getByName($name)
    {
        $data = $this->db->fetchRow("SELECT * FROM " . $this->db->quoteIdentifier(self::TABLE_NAME) . " WHERE name = ?", $name);
        if (!$data["id"]) {
            throw new \Exception("mapping with id " . $name . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    public function getByPimcoreId($pimcoreId)
    {
        $data = $this->db->fetchRow("SELECT * FROM " . $this->db->quoteIdentifier(self::TABLE_NAME) . " WHERE pimcoreClassId = ?", $pimcoreId);
        if (!$data["id"]) {
            throw new \Exception("mapping with id " . $pimcoreId . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return bool
     */
    public function save()
    {
        $this->db->beginTransaction();
        try {
            $dateTime = new \DateTime();
            if(!$this->model->getId()){
                $this->model->setCreationDate($dateTime);
            }
            $this->model->setModificationDate($dateTime);

            $dataAttributes = get_object_vars($this->model);
            $data = [];
            
            foreach ($dataAttributes as $key => $value) {
                if (in_array($key, $this->getValidTableColumns(static::TABLE_NAME))) {
                    $data[$key] = $value;
                }
            }
            if ($data['id']){
                $this->db->update(static::TABLE_NAME, $data, ['id' => $data['id']]);
            } else {
                $this->db->insert(static::TABLE_NAME, $data);
            }

            $lastInsertId = $this->db->lastInsertId();
            if (!$this->model->getId() && $lastInsertId) {
                $this->model->setId($lastInsertId);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {

        $this->db->beginTransaction();
        try {
            $this->db->delete(static::TABLE_NAME, ['id' => $this->model->getId()]);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

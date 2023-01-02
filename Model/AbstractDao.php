<?php

namespace Syncrasy\PimcoreSalesforceBundle\Model;


abstract class AbstractDao extends \Pimcore\Model\Dao\AbstractDao
{
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
            $dataAttributes = get_object_vars($this->model);


            $data = [];
            foreach ($dataAttributes as $key => $value) {
                if (in_array($key, $this->getValidTableColumns(static::TABLE_NAME))) {
                    $data[$key] = $value;
                }
            }

            $this->db->insertOrUpdate(static::TABLE_NAME, $data);

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
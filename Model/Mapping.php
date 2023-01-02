<?php

namespace Syncrasy\PimcoreSalesforceBundle\Model;


class Mapping extends  \Pimcore\Model\AbstractModel
{
    public $id;
    public $name;

    public $pimcoreClassId;
    public $salesforceObject;
    public $fieldForSfId;
    public $pimcoreUniqueField;
    public $salesforceUniqueField;
    public $columnAttributeMapping;
    public $language;
    public $userOwner;
    public $description;
    public $creationDate;

    public $modificationDate;

    

    public function save(){
        parent::save();
    }

    public static function getById($id)
    {
        $self = new self();
        return $self->getDao()->getById($id);
    }


    public static function getByName(string $name)
    {
        try {
            $tag = new self();
            $tag->getDao()->getByName($name);

            return $tag;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setValues($data = [])
    {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                if ($key == 'message') {
                    $this->setMessage($value, false);
                } else {
                    $this->setValue($key, $value);
                }
            }
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPimcoreClassId()
    {
        return $this->pimcoreClassId;
    }

    /**
     * @param mixed $name
     */
    public function setPimcoreClassId(?string $pimcoreClassId): void
    {
        $this->pimcoreClassId = $pimcoreClassId;
    }

    /**
     * @return mixed
     */
    public function getSalesforceObject()
    {
        return $this->salesforceObject;
    }

    /**
     * @param mixed $name
     */
    public function setSalesforceObject(?string $salesforceObject): void
    {
        $this->salesforceObject = $salesforceObject;
    }

    /**
     * @return mixed
     */
    public function getFieldForSfId()
    {
        return $this->fieldForSfId;
    }

    /**
     * @param mixed $name
     */
    public function setFieldForSfId(?string $fieldForSfId): void
    {
        $this->fieldForSfId = $fieldForSfId;
    }

    /**
     * @return mixed
     */
    public function getPimcoreUniqueField()
    {
        return $this->pimcoreUniqueField;
    }

    /**
     * @param mixed $name
     */
    public function setPimcoreUniqueField(?string $pimcoreUniqueField): void
    {
        $this->pimcoreUniqueField = $pimcoreUniqueField;
    }

    /**
     * @return mixed
     */
    public function getSalesforceUniqueField()
    {
        return $this->salesforceUniqueField;
    }

    /**
     * @param mixed $name
     */
    public function setSalesforceUniqueField(?string $salesforceUniqueField): void
    {
        $this->salesforceUniqueField = $salesforceUniqueField;
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @param mixed $userOwner
     */
    public function setUserOwner(?int $userOwner): void
    {
        $this->userOwner = $userOwner;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getColumnAttributeMapping()
    {
        return $this->columnAttributeMapping;
    }

    /**
     * @param mixed $columnAttributeMapping
     */
    public function setColumnAttributeMapping(?string $columnAttributeMapping): void
    {
        $this->columnAttributeMapping = $columnAttributeMapping;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        if($this->creationDate){
            return $this->getDateFromTimestamp($this->creationDate);
        }
        return $this->creationDate;
    }

    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate): void
    {
        if($creationDate instanceof \DateTime){
            $creationDate = $creationDate->getTimestamp();
        }
        $this->creationDate = $creationDate;
    }

    /**
     * @return mixed
     */
    public function getModificationDate()
    {
        if($this->modificationDate){
            return $this->getDateFromTimestamp($this->modificationDate);
        }
        return $this->modificationDate;
    }

    /**
     * @param mixed $modificationDate
     */
    public function setModificationDate($modificationDate): void
    {
        if($modificationDate instanceof \DateTime){
            $modificationDate = $modificationDate->getTimestamp();
        }
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }


}
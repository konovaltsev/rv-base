<?php

namespace RvBase\Table;

use RvBase\Entity\AbstractArrayEntity;
use RvBase\Table\Exception;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\TableGateway\TableGateway;

/**
 * Class AbstractArrayTableGatewayService
 * @package RvBase\Table
 */
class AbstractTableArrayEntity
{
    protected $tableGateway;

    protected $primaryKey = null;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * @return string|array
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getEntity($id)
    {
        $entity = $this->findEntity($id);
        if (!$entity)
        {
            throw new Exception\RuntimeException(
                sprintf(
                    'Could not find entity with id `%s` (%s)',
                    $id,
                    get_class($this)
                )
            );
        }
        return $entity;
    }

    public function findEntity($id)
    {
        $rowSet = $this->getTableGateway()->select($this->getIdFieldsFromData($id));
        return $rowSet->current();
    }

    public function saveEntity(AbstractArrayEntity $entity)
    {
        $data = $entity->getArrayData();

        $primary = $this->getIdFieldsFromData($data);

        $data = array_intersect_key($data, $entity->getArrayChangedFields());
        if(empty($data))
        {
            return $this;
        }

        $result = $this->getTableGateway()->update(
            $data,
            $primary
        );

        $entity->clearChanged();

        return $result;
    }

    /**
     * @return TableGateway
     */
    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    /**
     * @return \Zend\Db\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->getTableGateway()->getAdapter();
    }

    /**
     * @return \Zend\Db\Sql\Sql
     */
    public function getSql()
    {
        return $this->getTableGateway()->getSql();
    }

    /**
     * @return string|TableIdentifier
     */
    public function getTable()
    {
        return $this->getTableGateway()->getTable();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        $table = $this->getTable();
        if($table instanceof TableIdentifier)
        {
            return $table->getTable();
        }
        return $table;
    }

    /**
     * @return string
     */
    public function getSchemaTableName()
    {
        $table = $this->getTable();
        if($table instanceof TableIdentifier)
        {
            if($table->hasSchema())
            {
                return $table->getSchema() . $this->getIdentifierSeparator() . $table->getTable();
            }
            return $table->getTable();
        }
        return $table;
    }

    public function getIdentifierSeparator()
    {
        return $this->getTableGateway()->getAdapter()->getPlatform()->getIdentifierSeparator();
    }

    public function getTableColumnName($columnName)
    {
        return $this->getTableName() . $this->getIdentifierSeparator() . $columnName;
    }

    protected function getIdFieldsFromData($data)
    {
        $primary = $this->getPrimaryKey();
        if(!is_array($primary))
        {
            if(!is_array($data))
            {
                $data = array($primary => $data);
            }
            $primary = array($primary);
        }
        $id = array();
        foreach($primary as $field)
        {
            $id[$field] = $data[$field];
        }
        return $id;
    }
}

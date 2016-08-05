<?php
/**
 * rmayne
 *
 * @link      http://github.com/rmayne
 * @copyright Copyright (c)2015 rmayne
 * @license   MIT
 */
//WARNING, THIS DOES NOT PRODUCE A PROPERLY PREPARED SQL QUERY

namespace Dyanabol\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;




class ObjectTable
{
    protected $dbAdapter;

    public function __construct(Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    }

    public function getObjects()
    {
        $select = new \Zend\Db\Sql\Select;
        $select->from(array('e' => 'objects')); 
        $select->columns(array('object_class'));
        $select->group('e.object_class');
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $select->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($driverResult);
        return $resultSet;
    }

    public function saveObject(Object $entity)
    {
        if(!$entity->id){
            // Create Object Row
            $insert = new \Zend\Db\Sql\Insert;
            $insert->into('objects');
            $insert->columns(array('object_class')); // set the valid columns
            $insert->values(array('object_class' => $entity->object_class));
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $insert->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();
            $entityId = $driverResult->getGeneratedValue();

            // create attribute and value rows , excluding id and name
            foreach (get_object_vars($entity) as $key => $value) {
                if($key == 'object_class' || $key == 'id'){
                    continue;
                }
                switch(gettype($value)){
                    case 'string' :
                        $type = 'varchar';
                        break;
                    case 'integer' :
                        $type = 'double';
                        break;
                    case 'double' :
                        $type = 'double';
                        break;
                    default :
                        throw new \Exception('field data type unknown');
                }

                // create attribute row
                $insert = new \Zend\Db\Sql\Insert;
                $insert->into('attributes');
                $insert->columns(array('parent_id', 'name', 'type')); // set the valid columns
                $insert->values(array('parent_id' => $entityId, 'name' => $key, 'type' => $type));
                $statement = $dbAdapter->createStatement();
                $insert->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $attributeId = $driverResult->getGeneratedValue();

                //create value row
                $insert = new \Zend\Db\Sql\Insert;
                $insert->into('value_' . $type);
                $insert->columns(array('parent_id', 'value')); // set the valid columns
                $insert->values(array('parent_id' => $attributeId, 'value' => $value));
                $statement = $dbAdapter->createStatement();
                $insert->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $valueId = $driverResult->getGeneratedValue();
            }

            $resultSet = new ResultSet();
            $resultSet->initialize(array('id' => $entityId));

            return $resultSet;
        } else {
            // retrieve entity to be deleted
            $select = new \Zend\Db\Sql\Select;
            $select->from(array('e' => 'objects'))
            ->columns(array('object_class' => 'object_class', 'id' => 'id'))
            ->join(array('a' => 'attributes'), 'e.id = a.parent_id',  array('attribute_id' => 'id'), $select::JOIN_LEFT )
            ->where("e.id = $entity->id");
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $select->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();

            // get the ids for the attributes
            $attributeIds = array();
            foreach ($driverResult as $row) {
                if($row['attribute_id']){
                    $attributeIds[] = $row['attribute_id'];
                }
            }
            //delete the attribute row(s)
            $delete = new \Zend\Db\Sql\Delete;
            $delete->from('attributes');
            $delete->where(array('parent_id' => $entity->id));
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $delete->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();
            $affectedRows = $driverResult->getAffectedRows();

            // delete the value rows
            foreach ($attributeIds as $attributeId) {
                // varchar
                $delete = new \Zend\Db\Sql\Delete;
                $delete->from('value_varchar');
                $delete->where(array('parent_id' => $attributeId));
                $dbAdapter = $this->dbAdapter;
                $statement = $dbAdapter->createStatement();
                $delete->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $affectedRows += $driverResult->getAffectedRows();

                // double
                $delete = new \Zend\Db\Sql\Delete;
                $delete->from('value_double');
                $delete->where(array('parent_id' => $attributeId));
                $dbAdapter = $this->dbAdapter;
                $statement = $dbAdapter->createStatement();
                $delete->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $affectedRows += $driverResult->getAffectedRows();

                // timestamp
                $delete = new \Zend\Db\Sql\Delete;
                $delete->from('value_timestamp');
                $delete->where(array('parent_id' => $attributeId));
                $dbAdapter = $this->dbAdapter;
                $statement = $dbAdapter->createStatement();
                $delete->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $affectedRows += $driverResult->getAffectedRows();
            }

             // create attribute and value rows , excluding id and name
            foreach (get_object_vars($entity) as $key => $value) {
                if($key == 'object_class' || $key == 'id'){
                    continue;
                }
                switch(gettype($value)){
                    case 'string' :
                        $type = 'varchar';
                        break;
                    case 'integer' :
                        $type = 'double';
                        break;
                    case 'double' :
                        $type = 'double';
                        break;
                    default :
                        throw new \Exception('field data type unknown');
                }

                // create attribute row
                $insert = new \Zend\Db\Sql\Insert;
                $insert->into('attributes');
                $insert->columns(array('parent_id', 'name', 'type')); // set the valid columns
                $insert->values(array('parent_id' => $entity->id, 'name' => $key, 'type' => $type));
                $statement = $dbAdapter->createStatement();
                $insert->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $attributeId = $driverResult->getGeneratedValue();

                //create value row
                $insert = new \Zend\Db\Sql\Insert;
                $insert->into('value_' . $type);
                $insert->columns(array('parent_id', 'value')); // set the valid columns
                $insert->values(array('parent_id' => $attributeId, 'value' => $value));
                $statement = $dbAdapter->createStatement();
                $insert->prepareStatement($dbAdapter, $statement);
                $driverResult = $statement->execute();
                $valueId = $driverResult->getGeneratedValue();
            }

            $resultSet = new ResultSet();
            $resultSet->initialize(array('id' => $entity->id));
            return $resultSet;
        }
    }

    public function deleteObject($id)
    {
        // retrieve entity to be deleted
        $select = new \Zend\Db\Sql\Select;
        $select->from(array('e' => 'objects'))
        ->columns(array('object_class' => 'object_class', 'id' => 'id'))
        ->join(array('a' => 'attributes'), 'e.id = a.parent_id',  array('attribute_id' => 'id'), $select::JOIN_LEFT )
        ->where("e.id = $id");
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $select->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();

        // get the ids for the attributes
        $attributeIds = array();
        foreach ($driverResult as $row) {
            if($row['attribute_id']){
                $attributeIds[] = $row['attribute_id'];
            }
        }

        // delete the entity row
        $delete = new \Zend\Db\Sql\Delete;
        $delete->from('objects');
        $delete->where(array('id' => $id));
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $delete->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $affectedRows += $driverResult->getAffectedRows();

        //delete the attribute row(s)
        $delete = new \Zend\Db\Sql\Delete;
        $delete->from('attributes');
        $delete->where(array('parent_id' => $id));
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $delete->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $affectedRows += $driverResult->getAffectedRows();

        // delete the value rows
        foreach ($attributeIds as $attributeId) {
            // varchar
            $delete = new \Zend\Db\Sql\Delete;
            $delete->from('value_varchar');
            $delete->where(array('parent_id' => $attributeId));
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $delete->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();
            $affectedRows += $driverResult->getAffectedRows();

            // double
            $delete = new \Zend\Db\Sql\Delete;
            $delete->from('value_double');
            $delete->where(array('parent_id' => $attributeId));
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $delete->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();
            $affectedRows += $driverResult->getAffectedRows();

            // timestamp
            $delete = new \Zend\Db\Sql\Delete;
            $delete->from('value_timestamp');
            $delete->where(array('parent_id' => $attributeId));
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $delete->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();
            $affectedRows += $driverResult->getAffectedRows();
        }

        $resultSet = new ResultSet();
        $resultSet->initialize(array('affectedRows' => $affectedRows));
        return $resultSet;
    }

    public function getObjectCollection($name)
    {

        $select = new \Zend\Db\Sql\Select;
        $select->from(array('e' => 'objects'))
        ->columns(array('object_class' => 'object_class', 'id' => 'id'))
        ->join(array('a' => 'attributes'), 'e.id = a.parent_id',  array('attribute_name' => 'name', 'attribute_type' => 'type'), $select::JOIN_LEFT )
        ->join(array('vd' => 'value_double'), 'a.id = vd.parent_id', array('double' => 'value'), $select::JOIN_LEFT )
        ->join(array('vts' => 'value_timestamp'), 'a.id = vts.parent_id', array('timestamp' => 'value'), $select::JOIN_LEFT )
        ->join(array('vvc' => 'value_varchar'), 'a.id = vvc.parent_id', array('varchar' => 'value'), $select::JOIN_LEFT )
        ->where("e.object_class = '$name'")
        ->order('id DESC');

        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $select->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();

        $newDataSource = array();
        foreach ($driverResult as $row) {
            // first run $newDataSource
            if(empty($newDataSource)){
                $newDataSource[] = array(
                    'id' => $row['id'],
                    'object_class' => $row['object_class'], 
                    $row['attribute_name'] => ($row['attribute_type'] == 'double') ? (float) $row[$row['attribute_type']] : $row[$row['attribute_type']]
                ); 
            // same object id
            } elseif ($row['id'] == $newDataSource[(count($newDataSource) - 1)]['id']){
                $newDataSource[(count($newDataSource) - 1)][$row['attribute_name']] = ($row['attribute_type'] == 'double') ? (float) $row[$row['attribute_type']] : $row[$row['attribute_type']];
            // new object id
            } else {
                $newDataSource[] = array('id' => $row['id'],
                    'object_class' => $row['object_class'], 
                    $row['attribute_name'] => ($row['attribute_type'] == 'double') ? (float) $row[$row['attribute_type']] : $row[$row['attribute_type']]
                );
            }
        }

        $resultSet = new ResultSet();
        $resultSet->initialize($newDataSource);
        return $resultSet;
    }

    public function getObject($id)
    {
        $select = new \Zend\Db\Sql\Select;

        $select->from(array('e' => 'objects'))
        ->columns(array('object_class', 'id'))
        ->join(array('a' => 'attributes'), 'e.id = a.parent_id',  array('attribute_name' => 'name', 'attribute_type' => 'type'), $select::JOIN_LEFT )
        ->join(array('vd' => 'value_double'), 'a.id = vd.parent_id', array('double' => 'value'), $select::JOIN_LEFT )
        ->join(array('vts' => 'value_timestamp'), 'a.id = vts.parent_id', array('timestamp' => 'value'), $select::JOIN_LEFT )
        ->join(array('vvc' => 'value_varchar'), 'a.id = vvc.parent_id', array('varchar' => 'value'), $select::JOIN_LEFT )
        ->where("e.id = $id")
        ->order('id DESC');

        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $select->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();

        $newDataSource = array();
        foreach ($driverResult as $row) {
            if(empty($newDataSource)){
                if($row['attribute_name']){
                    $newDataSource[] = array('id' => $row['id'],
                        'object_class' => $row['object_class'], 
                        $row['attribute_name'] => $row[$row['attribute_type']]);
                } else {
                    $newDataSource[] = array('id' => $row['id'],
                        'object_class' => $row['object_class']);
                }
            } elseif ($row['id'] == $newDataSource[(count($newDataSource) - 1)]['id']){
                $newDataSource[(count($newDataSource) - 1)][$row['attribute_name']] = $row[$row['attribute_type']];
            } else {
                if($row['attribute_name']){
                    $newDataSource[] = array('id' => $row['id'],
                        'object_class' => $row['object_class'], 
                        $row['attribute_name'] => $row[$row['attribute_type']]);
                } else {
                    $newDataSource[] = array('id' => $row['id'],
                        'object_class' => $row['object_class']);
                } 
            }
        }

        $resultSet = new ResultSet();
        $resultSet->initialize($newDataSource);
        return $resultSet;
    }
}
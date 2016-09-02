<?php
/**
 * rmayne
 *
 * @link      http://github.com/rmayne
 * @copyright Copyright (c)2016 rmayne
 * @license   MIT
 */
//WARNING, THIS DOES NOT PRODUCE A PROPERLY PREPARED SQL QUERY

namespace Dyanabol\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Exception;



class ObjectTable
{
    protected $dbAdapter;

    public function __construct(Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }

    /*
    * return: array upon success, false on failure
    */
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
        if(!$driverResult->count() > 0){
            return false;
        }
        $resultSet = new ResultSet();
        $resultSet->initialize($driverResult);
        return $resultSet->toArray();
    }

    /*
    * return: array on success, false on failure
    */
    public function saveObject(Object $object)
    {
        // create new object
        if(!$object->id){
            // Create Object table Row
            $insert = new \Zend\Db\Sql\Insert;
            $insert->into('objects');
            $insert->columns(array('object_class')); // set the valid columns
            $insert->values(array('object_class' => $object->object_class));
            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $insert->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();
            $objectId = $driverResult->getGeneratedValue();

            if(!$objectId){
                return false;
            }

            // create attribute and value rows , excluding id and name
            foreach (get_object_vars($object) as $key => $value) {
                if($key == 'object_class' || $key == 'id'){
                    continue;
                }

                $attributeId = $this->createAttribute($objectId, $key, $value);

                $valueId =  $this->createValue($attributeId, $value);
            }

            $resultSet = new ResultSet();
            $resultSet->initialize(array('id' => $objectId));

            return $resultSet->getDataSource();
        } else {
            // get passed obejct vars
            $passedProps = get_object_vars($object);

            //get db vars
            $select = new \Zend\Db\Sql\Select;
            $select->from(array('a' => 'attributes'))
            ->columns(array('attribute_id' => 'id', 'attribute_name' => 'name', 'attribute_type' => 'type'))
            ->join(array('vd' => 'value_double'), 'a.id = vd.parent_id', array('double_id' => 'id', 'double' => 'value'), $select::JOIN_LEFT )
            ->join(array('vts' => 'value_timestamp'), 'a.id = vts.parent_id', array('timestamp_id' => 'id', 'timestamp' => 'value'), $select::JOIN_LEFT )
            ->join(array('vvc' => 'value_varchar'), 'a.id = vvc.parent_id', array('varchar_id' => 'id', 'varchar' => 'value'), $select::JOIN_LEFT )
            ->where("a.parent_id = $object->id")
            ->order('attribute_id DESC');

            $dbAdapter = $this->dbAdapter;
            $statement = $dbAdapter->createStatement();
            $select->prepareStatement($dbAdapter, $statement);
            $driverResult = $statement->execute();

            // contains db information for each property/attribute
            $propertyDataDb = array();

            // convert from indexed array to associative
            foreach ($driverResult as $row) {
                $propertyDataDb[$row['attribute_name']] = $row;
            }

            //compute differences
            //$dbProps = $this->objectHelper($driverResult)[0];       
            $newProps = array_diff_key($passedProps, $propertyDataDb); //name, value array
            $deletedProps = array_diff_key($propertyDataDb, $passedProps); //multi dimensional array
            $existingProps = array_intersect_key($passedProps, $propertyDataDb); // name value array
            //$existingPropsWithNewValues = array_diff($existingPropsPassed, $existingPropsDb);

            //delete attributes
            foreach ($deletedProps as $property_name => $property_value) {
                //skip these two
                if($property_name == 'object_class' || $property_name == 'id'){
                    continue;
                }

                $this->deleteAttribute($propertyDataDb[$property_name]['attribute_id']); 
                $this->deleteValue($propertyDataDb[$property_name]['attribute_id'], $propertyDataDb[$property_name]['attribute_type']);
            }

            // update props
            foreach ($existingProps as $key => $value) {

                if($property_name == 'object_class' || $property_name == 'id'){
                    continue;
                }

                // identical 
                if($value === $propertyDataDb[$key][$propertyDataDb[$key]['attribute_type']]) { // ;-)
                } 
                //same type
                elseif(gettype($value) == $propertyDataDb[$key]['attribute_type']) {
                    // update value

                    $this->updateValue($propertyDataDb[$key]['attribute_id'], $value);
                } 
                // different type
                else {
                    // delete old value
                    $this->deleteValue($propertyDataDb[$key]['attribute_id'], $propertyDataDb[$key]['attribute_type']);
                    // create new value
                    $this->createValue($propertyDataDb[$key]['attribute_id'], $value);
                    // update attribute type
                    $this->updateAttribute($propertyDataDb[$key]['attribute_id'], $value);
                }

            } 

            // create props
            foreach ($newProps as $propertyName => $value) {
                if($property_name == 'object_class' || $property_name == 'id'){
                    continue;
                }
                $attributeId = $this->createAttribute($object->id, $propertyName, $value);
                $this->createValue($attributeId, $value);
            }

            $resultSet = new ResultSet();
            $resultSet->initialize(array('id' => $object->id));
            return $resultSet->getDataSource();
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
        return $resultSet->getDataSource();
    }

    /*
    * return : array on success, empty array on failure
    */
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

        $newDataSource = $this->objectHelper($driverResult);

        if(!$newDataSource){
            return array();
        }

        $resultSet = new ResultSet();
        $resultSet->initialize($newDataSource);
        return $resultSet->toArray();
    }

    /* get a single object
    * return array on success, false on failure
    */
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

        $newDataSource = $this->objectHelper($driverResult);

        if (!$newDataSource) {
            return false;
        }
        $resultSet = new ResultSet();
        $resultSet->initialize($newDataSource);
        return $resultSet;
    }

    //return: associative array('property_name = > 'value', ....)
    protected function objectHelper($driverResult){
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

        return $newDataSource;
    }

    /*
    * create a row in the attribute table
    * returns new attribute id on success. false on failure
    */
    protected function createAttribute($objectId, $name, $value){

        //skip these two
        if($name == 'object_class' || $name == 'id'){
            return false;
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
        $insert->values(array('parent_id' => $objectId, 'name' => $name, 'type' => $type));
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $insert->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $attributeId = $driverResult->getGeneratedValue();

        return $attributeId;
    }

    
    protected function updateAttribute($id, $value){

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

        // create attribute table row
        $update = new \Zend\Db\Sql\Update('attributes');
        //$update->columns(array('parent_id', 'name', 'type')); // set the valid columns
        $update->set(array('type' => $type))
        ->where("id = $id");
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $update->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $attributeId = $driverResult->getGeneratedValue();
        return $attributeId;
    }

    // delete attribute
    protected function deleteAttribute($id){
        //delete the attribute row(s)
        $delete = new \Zend\Db\Sql\Delete;
        $delete->from('attributes');
        $delete->where(array('id' => $id));
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $delete->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $affectedRows = $driverResult->getAffectedRows();
        return $affectedRows;
    }

    public function createValue($attributeId, $value){
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
                throw new \Exception($key . ' field data type unknown');
        }

        //create value value row
        $insert = new \Zend\Db\Sql\Insert;
        $insert->into('value_' . $type);
        $insert->columns(array('parent_id', 'value')); // set the valid columns
        $insert->values(array('parent_id' => $attributeId, 'value' => $value));
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $insert->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $valueId = $driverResult->getGeneratedValue();

        return $valueId;
    }

    public function updateValue($attributeId, $value){
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

        // create attribute table row
        $update = new \Zend\Db\Sql\Update('value_' . $type);
        //$update->columns(array('parent_id', 'name', 'type')); // set the valid columns
        $update->set(array('value' => $value))
        ->where("parent_id = $attributeId");
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $update->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $attributeId = $driverResult->getGeneratedValue();
        return $attributeId;
    }

    protected function deleteValue($attributeId, $type){

        $delete = new \Zend\Db\Sql\Delete;
        $delete->from('value_' . $type);
        $delete->where(array('parent_id' => $attributeId));
        $dbAdapter = $this->dbAdapter;
        $statement = $dbAdapter->createStatement();
        $delete->prepareStatement($dbAdapter, $statement);
        $driverResult = $statement->execute();
        $affectedRows += $driverResult->getAffectedRows();

        return $affectedRows;
    }
}
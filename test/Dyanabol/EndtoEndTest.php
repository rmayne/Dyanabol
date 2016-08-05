<?php

namespace Dyanabol\Controller;
use GuzzleHttp;

class DyanabolZFTest extends \PHPUnit_Framework_TestCase
{
    protected $client;
    protected $base_uri = 'http://testarea.localhost/github/rm-zend-rest/public/';
    protected $username = 'superbasic';
    protected $password = 'thisIsth364';

    public function generateRandomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function setUp() {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => $this->base_uri
        ]);
    }

    public function testCreateObject(){

        $obj12Name = $this->generateRandomString();

        $data = array(
            'object_class' => $obj12Name,
            'prop1' => $this->generateRandomString(),
            'prop2' => rand(0, 99999)
            );

        $data2 = array(
            'object_class' => $obj12Name,
            'prop1' => $this->generateRandomString(),
            'prop2' => rand(0, 99999)
            );

        $data3 =array(
            'object_class' => $this->generateRandomString(),
            'prop1' => $this->generateRandomString(),
            'prop2' => rand(0, 99999)
            );

        // obejct 1
        $response = $this->client->request('POST', '', array(
            'json' => $data,
            'auth' => array($this->username, $this->password)
        ));
        // test return code
        $this->assertEquals(200, $response->getStatusCode(), 'Failed to create object 1');
        $this->assertObjectHasAttribute('id', json_decode($response->getBody()), 'Failed to return created id for object 1');
        // get created object id
        $data['id'] = json_decode($response->getBody())->id;

        // object 2
        $response = $this->client->request('POST', '', array(
            'json' => $data2,
            'auth' => array($this->username, $this->password)
        ));
        // test return code
        $this->assertEquals(200, $response->getStatusCode(), 'Failed to create object 2');
        $this->assertObjectHasAttribute('id', json_decode($response->getBody()), 'Failed to return created id for object 1');
        // get created object id
        $data2['id'] = json_decode($response->getBody())->id;

        // object 3
        $response = $this->client->request('POST', '', array(
            'json' => $data3,
            'auth' => array($this->username, $this->password)
        ));
        // test return code
        $this->assertEquals(200, $response->getStatusCode(), 'Failed to create object 3');
        $this->assertObjectHasAttribute('id', json_decode($response->getBody()), 'Failed to return created id for object 1');
        // get created object id
        $data3['id'] = json_decode($response->getBody())->id;

        return array($data, $data2, $data3);
    }

    /**
    * @depends testCreateObject
    */
    public function testReadObjectsCollection($testDataArray){
        // should return ana array of objects containing all three test objects
        $data = $testDataArray[0];
        $data2 = $testDataArray[1];
        $data3 = $testDataArray[2];

        // get collection
        $response = $this->client->request('GET', '', array(
            'auth' => array($this->username, $this->password)
             )
        );

        // test response code
        $this->assertEquals(200, $response->getStatusCode(), 'Failed to read entities collection');

        $this->assertGreaterThanOrEqual(3, count(json_decode($response->getBody())), 'Objects collection was too small');
        
    }

    /**
    * @depends testCreateObject
    */
    public function testReadObjectCollection($testDataArray){
        // first request should return the test objects one and two, but not three
        // second request should return test object three, but not one and two
        $data = $testDataArray[0];
        $data2 = $testDataArray[1];
        $data3 = $testDataArray[2];

        // test data for objects one and two
        $response = $this->client->request('GET', $data['object_class'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test response code
        $this->assertEquals(200, $response->getStatusCode(), 'failed to read entity collection');
        // check for count of created objects
        $this->assertEquals(2, count(json_decode($response->getBody())), 'wrong entity count for read entity collection (objects 1&2)');

        // test data for object three
        $response = $this->client->request('GET', $data3['object_class'] , array(
            'auth' => array($this->username, $this->password)
        ));
        // test response code
        $this->assertEquals(200, $response->getStatusCode(), 'failed to read entity collection');
        // check for count of created objects
        $this->assertEquals(1, count(json_decode($response->getBody())), 'wrong entity count for read entity collection (object 3_');

    }

    /**
    * @depends testCreateObject
    */
    public function testReadObject($testDataArray){
        // one request for each test object, test each objects data
        $data = $testDataArray[0];
        $data2 = $testDataArray[1];
        $data3 = $testDataArray[2];

        //object one
        $response = $this->client->request('GET', $data['object_class'] . '/' . $data['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test response code
        $this->assertEquals(200, $response->getStatusCode(), 'failed to read entity (object1)');
        // test object
        $object1 = json_decode($response->getBody())[0];
        foreach ($data as $key => $value) {
            $this->assertObjectHasAttribute($key, $object1, 'object 1 property not present');
            $this->assertEquals($value, $object1->$key, 'object 1 property value incorrect');
        }

        // object two
        $response = $this->client->request('GET',  $data2['object_class'] . '/' . $data2['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test response code
        $this->assertEquals(200, $response->getStatusCode(), 'Failed to read entity (object 2)');
        $object2 = json_decode($response->getBody())[0];
        foreach ($data2 as $key => $value) {
            $this->assertObjectHasAttribute($key, $object2, 'obejct 2 property not present');
            $this->assertEquals($value, $object2->$key, 'object 2 property value incorrect');
        }

        // object three
        $response = $this->client->request('GET', $data3['object_class'] . '/' . $data3['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test response code
        $this->assertEquals(200, $response->getStatusCode(), ' failed to read entity (object 3)');
        $object3 = json_decode($response->getBody())[0];
        foreach ($data3 as $key => $value) {
            $this->assertObjectHasAttribute($key, $object3, 'object 3 property not present');
            $this->assertEquals($value, $object3->$key, 'object 3 property value incorrect');
        }

        return array($object1, $object2, $object3);
    }

    /**
    * @depends testReadObject
    */
    public function testUpdateObject($readObjectsArray){
    // should update an entity prop with random data

        $object1 = $readObjectsArray[0];
        $object2 = $readObjectsArray[1];
        $object3 = $readObjectsArray[2];

        $data =array(
            'id' => $object1->id,
            'object_class' => $object1->object_class,
            'prop1' => $this->generateRandomString(),
            'prop2' => rand(0, 99999)
            );

        // Update Object
        $response = $this->client->request('PUT', 
            $data['object_class'] . '/' . $data['id'], 
            array(
            'json' => $data,
            'auth' => array($this->username, $this->password)
        ));
        // test status code
        $this->assertEquals(200, $response->getStatusCode(), 'update entuty failed');

        // read object
        $response = $this->client->request('GET', $data['object_class'] . '/' . $data['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test status code
        $this->assertEquals(200, $response->getStatusCode(), 'read entity during update entity test failed');

         // test object data
        $readObject = json_decode($response->getBody())[0];
        foreach ($data as $key => $value) {
            $this->assertObjectHasAttribute($key, $readObject, 'update test attribute was not present');
            $this->assertEquals($value, $readObject->$key, 'update test property value incorrect');
        }
    }

    /**
    * @depends testCreateObject
    * 
    */
    public function testDeleteObject($testDataArray){
    // make the delete requests
        $data = $testDataArray[0];
        $data2 = $testDataArray[1];
        $data3 = $testDataArray[2];

        // object 1
        $response = $this->client->request('DELETE', $data['object_class'] . '/' . $data['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test reponse code
        $this->assertEquals(200, $response->getStatusCode(), 'delete entity failed');

        // object 2
        $response = $this->client->request('DELETE', $data2['object_class'] . '/' . $data2['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test reponse code
        $this->assertEquals(200, $response->getStatusCode(), 'delete entity failed');

        // object 3
        $response = $this->client->request('DELETE', $data3['object_class'] . '/' . $data3['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test reponse code
        $this->assertEquals(200, $response->getStatusCode(), 'delete entity failed');        
    }

    /**
    * @depends testCreateObject
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testReadDeletedObject($testDataArray){
        //verify that the object is longer available
        $data = $testDataArray[0];
        $data2 = $testDataArray[1];
        $data3 = $testDataArray[2];

        // attempt to read deleted resource
        $response = $this->client->request('GET', $data['object_class'] . '/' . $data['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test status code
        $this->assertEquals(500, $response->getStatusCode(), 'entity still present after delete');
        
        // attempt to read deleted resource
        $response = $this->client->request('GET', $data2['object_class'] . '/' . $data2['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test status code
        $this->assertEquals(500, $response->getStatusCode(), 'entity still present after delete');

        // attempt to read deleted resource
        $response = $this->client->request('GET', $data3['object_class'] . '/' . $data3['id'], array(
            'auth' => array($this->username, $this->password)
        ));
        // test status code
        $this->assertEquals(500, $response->getStatusCode(), 'entity still present after delete');
    }

    /**
    * @depends testCreateObject
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testCreateObjectAuth($testDataArray){
        // should return ana array of objects containing all three test objects
        $data = $testDataArray[0];
        $data2 = $testDataArray[1];
        $data3 = $testDataArray[2];

        // create entity
        $response = $this->client->request('POST', '', array(
            'json' => $data
        ));
        $this->assertEquals(500, $response->getStatusCode(), 'Auth for Create Object broken');
    }

    /**
    * 
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testReadObjectsCollectionAuth(){
       // get entities collection
        $response = $this->client->request('GET', '');
        $this->assertEquals(500, $response->getStatusCode(), 'Auth for Read entities collection broken');
    }

    /**
    * @depends testCreateObject
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testReadObjectCollectionAuth($testDataArray){
        $data = $testDataArray[0];
        $response = $this->client->request('GET', $data['object_class']);
        $this->assertEquals(500, $response->getStatusCode(), 'auth broken for read entity collection');
    }

    /**
    * @depends testCreateObject
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testReadObjectAuth($testDataArray){
        $data = $testDataArray[0];
        $response = $this->client->request('GET', 
            $data['object_class'] . '/' . $data['id']);
        $this->assertEquals(500, $response->getStatusCode(), 'auth broken for read entity');
    }

    /**
    * @depends testCreateObject
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testUpdateObjectAuth($testDataArray){
        $data = $testDataArray[0];
        $response = $this->client->request('PUT', 
            $data['object_class'] . '/' . $data['id'], 
            array(
            'json' => $data
        ));
        $this->assertEquals(500, $response->getStatusCode(), 'auth broken for update entity');
    }

    /**
    * @depends testCreateObject
    * @expectedException GuzzleHttp\Exception\ServerException
    */
    public function testDeleteObjectAuth($testDataArray){
        $data = $testDataArray[0];
        $response = $this->client->request('DELETE', 
            $data['object_class'] . '/' . $data['id']
        );
        $this->assertEquals(500, $response->getStatusCode(), 'Auth broken for delete entity');
    }
}
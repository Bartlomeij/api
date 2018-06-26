<?php

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class ProductControllerTest extends ApiTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createUser('bart', 'secretPass', ['ROLE_ADMIN']);
    }

    public function testPOSTCreateProduct()
    {
        $data = array(
            'title' => 'Test Product',
            'price' => 5.99
        );

        $response = $this->client->post('/api/products', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertContains('/api/products/', $response->getHeader('Location'));
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));

        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Test Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'price', 5.99);
    }

    public function testPOSTCreateProductByUserFailed()
    {
        $this->createUser('test_user');
        $data = array(
            'title' => 'Test Product',
            'price' => 5.99
        );

        $response = $this->client->post('/api/products', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('test_user')
        ]);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Forbidden'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'Access Denied.'
        );
    }

    public function testPOSTCreateProductRoundedPrice()
    {
        $data = array(
            'title' => 'Test Product',
            'price' => 5.9944
        );

        $response = $this->client->post('/api/products', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertContains('/api/products/', $response->getHeader('Location'));
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));

        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Test Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'price', 5.99);
    }

    public function testGETProduct()
    {
        $this->createUser('test_user');

        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 3.99
        ), 'bart');

        $response = $this->client->get('/api/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('test_user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'title',
            'price',
        ));
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'user');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Test Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'price', 3.99);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            '_links.self',
            $this->adjustUri('/api/products/'.$product->getId())
        );
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorTitleEmpty()
    {
        $data = array(
            'price' => 3.99
        );

        $response = $this->client->post('/api/products', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.title');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.title[0]',
            'Please enter a proper title'
        );
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.price');
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorPriceEmpty()
    {
        $data = array(
            'title' => 'Test Product',
        );

        $response = $this->client->post('/api/products', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.price');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.price[0]',
            'Please enter a proper price'
        );
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.title');
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }
    public function testInvalidJson()
    {
        $invalidJson = '{"title": "Test Product, "price": 5.99}';

        $response = $this->client->post('/api/products', [
            'body' => $invalidJson,
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyContains(
            $response,
            'type',
            'invalid_body_format'
        );
    }

    public function test404Exception()
    {
        $response = $this->client->get('/api/products/fake', [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Not Found'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'No product found for id fake'
        );
    }

    public function testGETProductsCollection()
    {
        $this->createProduct(array(
            'title' => 'First Product',
            'price' => 3.99
        ), 'bart');

        $this->createProduct(array(
            'title' => 'Second Product',
            'price' => 4.99
        ), 'bart');

        $this->createProduct(array(
            'title' => 'Third Product',
            'price' => 5.99
        ), 'bart');

        $response = $this->client->get('/api/products');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyIsArray($response, 'items');
        $this->asserter()->assertResponsePropertyCount($response, 'items', 3);
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].title', 'First Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].price', '3.99');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].title', 'Second Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].price', '4.99');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].title', 'Third Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].price', '5.99');
    }

    public function testGETProductsCollectionPaginated()
    {
        $this->createProduct(array(
            'title' => 'Will not match',
            'price' => 4.99
        ), 'bart');

        for( $i = 0; $i < 25; $i++) {
            $this->createProduct(array(
                'title' => 'product_'.$i,
                'price' => 5.99
            ), 'bart');
        }

        $response = $this->client->get('/api/products?filter=product');
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].title', 'product_2');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 3);
        $this->asserter()->assertResponsePropertyEquals($response, 'total', 25);
        $this->asserter()->assertResponsePropertyExists($response, '_links.next');

        $nextUrl = $this->asserter()->readResponseProperty($response, '_links.next');
        $response = $this->client->get($nextUrl);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].title', 'product_5');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 3);
        $this->asserter()->assertResponsePropertyExists($response, '_links.previous');

        $lastUrl = $this->asserter()->readResponseProperty($response, '_links.last');
        $response = $this->client->get($lastUrl);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].title', 'product_24');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[1].title');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 1);
        $this->asserter()->assertResponsePropertyExists($response, '_links.first');
    }

    public function testPUTProduct()
    {
        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $data = array(
            'title' => 'Changed Product',
            'price' => 3.99
        );

        $response = $this->client->put('/api/products/'.$product->getId(), array(
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('bart')
        ));
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'title',
            'price',
        ));
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Changed Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'price', 3.99);
    }

    public function testPATCHProduct()
    {
        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $data = array(
            'price' => 3.99
        );

        $response = $this->client->patch('/api/products/'.$product->getId(), array(
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('bart')
        ));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Test Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'price', 3.99);
    }

    public function testDELETEProduct()
    {
        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $response = $this->client->delete('/api/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->client->get('/api/users/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDELETEProductByUser()
    {
        $this->createUser('testuser');

        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $response = $this->client->delete('/api/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('testuser')
        ]);
        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->client->get('/api/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('testuser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
<?php

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class CartControllerTest extends ApiTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createUser('bart', 'secretPass', ['ROLE_ADMIN']);
        $this->createUser('user');
    }

    public function testPOSTCreateCart()
    {
        $response = $this->client->post('/api/carts', [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertContains('/api/carts/', $response->getHeader('Location'));
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));

        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 0);
        $this->asserter()->assertResponsePropertyEquals($response, '_embedded.user.username', 'bart');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'products[0].title');
        $this->asserter()->assertResponsePropertiesExist($response, [
            'products',
            '_links.self',
            '_links.user'
        ]);
    }

    public function testPOSTCreateCartAnonymously()
    {
        $response = $this->client->post('/api/carts');
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Unauthorized'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'Not privileged to request the resource.'
        );
    }

    public function testGETCart()
    {
        $cart = $this->createCart('user');
        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 0);
        $this->asserter()->assertResponsePropertyEquals($response, '_embedded.user.username', 'user');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'products[0].title');
        $this->asserter()->assertResponsePropertiesExist($response, [
            'products',
            '_links.self',
            '_links.user'
        ]);
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
    }

    public function testGETCartByOtherUser()
    {
        $cart = $this->createCart('bart');
        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
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

    public function testGETCartByAdmin()
    {
        $cart = $this->createCart('user');
        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 0);
        $this->asserter()->assertResponsePropertyEquals($response, '_embedded.user.username', 'user');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'products[0].title');
        $this->asserter()->assertResponsePropertiesExist($response, [
            'products',
            '_links.self',
            '_links.user'
        ]);
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
    }

    public function testGETCartsCollection()
    {
        $this->createCart('bart');
        $this->createCart('bart');
        $this->createCart('user');

        $response = $this->client->get('/api/carts', [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyIsArray($response, 'items');
        $this->asserter()->assertResponsePropertyCount($response, 'items', 3);
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].totalPrice', 0);
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[0].products[0].title');
        $this->asserter()->assertResponsePropertyExists($response, 'items[1]._links.self');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2]._embedded.user.username', 'user');
    }

    public function testGETCartsCollectionPaginated()
    {
        for( $i = 0; $i < 25; $i++) {
            $this->createCart('user');
        }

        $response = $this->client->get('/api/carts', [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].totalPrice', 0);
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 3);
        $this->asserter()->assertResponsePropertyEquals($response, 'total', 25);
        $this->asserter()->assertResponsePropertyExists($response, '_links.next');

        $nextUrl = $this->asserter()->readResponseProperty($response, '_links.next');
        $response = $this->client->get($nextUrl, [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].totalPrice', 0);
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 3);
        $this->asserter()->assertResponsePropertyExists($response, '_links.previous');

        $lastUrl = $this->asserter()->readResponseProperty($response, '_links.last');
        $response = $this->client->get($lastUrl, [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].totalPrice', 0);
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[1].totalPrice');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 1);
        $this->asserter()->assertResponsePropertyExists($response, '_links.first');
    }

    public function testGETCartsCollectionByUser()
    {
        $response = $this->client->get('/api/carts', [
            'headers' => $this->getAuthorizedHeaders('user')
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

    public function testDELETECartByUser()
    {
        $cart = $this->createCart('user');

        $response = $this->client->delete('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDELETECartByOtherUser()
    {
        $cart = $this->createCart('bart');

        $response = $this->client->delete('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDELETECartByAdmin()
    {
        $cart = $this->createCart('user');

        $response = $this->client->delete('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPOSTAddProductToCart()
    {
        $cart = $this->createCart('user');
        $response = $this->client->get('/api/carts/'.$cart->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 0);

        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 4.99);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'Test Product');
    }

    public function testPOSTAddProductTwiceToCart()
    {
        $cart = $this->createCart('user');
        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 4.99);
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'products[1].title');

    }

    public function testPOSTAddFourProductsToCart()
    {
        $cart = $this->createCart('user');

        $products = array();
        for($i = 0; $i < 4; $i++) {
            $products[$i] = $this->createProduct(array(
                'title' => 'Test Product '.$i,
                'price' => $i.'.99'
            ), 'bart');
        }

        for($i = 0; $i < 3; $i++) {
            $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$products[$i]->getId(), [
                'headers' => $this->getAuthorizedHeaders('user')
            ]);
            $this->assertEquals(200, $response->getStatusCode());
        }

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$products[3]->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPOSTAddProductToCartByOtherUser()
    {
        $cart = $this->createCart('bart');
        $product = $this->createProduct(array(
            'title' => 'Test Product',
            'price' => 4.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
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

    public function testPOSTRemoveProductFromCart()
    {
        $cart = $this->createCart('user');

        $product_1 = $this->createProduct(array(
            'title' => 'First Product',
            'price' => 2.99
        ), 'bart');

        $product_2 = $this->createProduct(array(
            'title' => 'Second Product',
            'price' => 3.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_1->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_2->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 6.98);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'First Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'products[1].title', 'Second Product');

        $response = $this->client->delete('/api/carts/'.$cart->getId().'/products/'.$product_1->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 3.99);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'Second Product');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'products[1].title');
    }

    public function testPOSTRemoveNotAddedProductFromCart()
    {
        $cart = $this->createCart('user');

        $product_1 = $this->createProduct(array(
            'title' => 'First Product',
            'price' => 2.99
        ), 'bart');

        $product_2 = $this->createProduct(array(
            'title' => 'Second Product',
            'price' => 3.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_1->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 2.99);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'First Product');

        $response = $this->client->delete('/api/carts/'.$cart->getId().'/products/'.$product_2->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 2.99);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'First Product');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'products[1].title');

    }

    public function testPOSTRemoveProductFromCartByOtherUser()
    {
        $cart = $this->createCart('bart');

        $product_1 = $this->createProduct(array(
            'title' => 'First Product',
            'price' => 2.99
        ), 'bart');

        $product_2 = $this->createProduct(array(
            'title' => 'Second Product',
            'price' => 3.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_1->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_2->getId(), [
            'headers' => $this->getAuthorizedHeaders('bart')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 6.98);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'First Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'products[1].title', 'Second Product');

        $response = $this->client->delete('/api/carts/'.$cart->getId().'/products/'.$product_1->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
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

    public function testGETCartWithProducts()
    {
        $cart = $this->createCart('user');

        $product_1 = $this->createProduct(array(
            'title' => 'First Product',
            'price' => 2.99
        ), 'bart');

        $product_2 = $this->createProduct(array(
            'title' => 'Second Product',
            'price' => 3.99
        ), 'bart');

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_1->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->client->put('/api/carts/'.$cart->getId().'/products/'.$product_2->getId(), [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'totalPrice', 6.98);
        $this->asserter()->assertResponsePropertyEquals($response, 'products[0].title', 'First Product');
        $this->asserter()->assertResponsePropertyEquals($response, 'products[1].title', 'Second Product');
    }
}
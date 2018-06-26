<?php

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class TokenControllerTest extends ApiTestCase
{
    public function testPOSTCreateToken()
    {
        $username = 'bart';
        $pass = 'superSecretPassword';
        $this->createUser($username, $pass);

        $response = $this->client->post('/api/tokens', [
            'auth' => [$username, $pass]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'token'
        );
    }

    public function testPOSTTokenInvalidCredentials()
    {
        $username = 'bart';
        $pass = 'superSecretPassword';
        $this->createUser($username, $pass);

        $response = $this->client->post('/api/tokens', [
            'auth' => [$username, 'foobar']
        ]);
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
            'Invalid credentials.'
        );
    }
}
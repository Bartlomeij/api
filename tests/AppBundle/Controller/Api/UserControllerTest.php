<?php

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class UserControllerTest extends ApiTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createUser('bart');
    }

    public function testPOSTCreateUser()
    {
        $data = array(
            'username' => 'testUser',
            'email' => 'testUser@example.com',
            'password' => 'superSecretPassword'
        );

        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertContains('/api/users/', $response->getHeader('Location'));
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));

        $finishedData = json_decode($response->getBody(), true);
        $this->assertEquals('testUser', $finishedData['username']);
        $this->assertEquals('testUser@example.com', $finishedData['email']);
        $this->assertArrayNotHasKey('password', $finishedData);
    }

    public function testGETUser()
    {
        $user = $this->createUser('testUser');

        $response = $this->client->get('/api/users/'.$user->getId(), [
            'headers' => $this->getAuthorizedHeaders('testUser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'username',
            'email'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'username', 'testUser');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            '_links.self',
            $this->adjustUri('/api/users/'.$user->getId())
        );
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
    }

    public function testGETOtherUser()
    {
        $this->createUser('testUser');
        $otherUser = $this->createUser('testOtherUser');

        $response = $this->client->get('/api/users/'.$otherUser->getId(), [
            'headers' => $this->getAuthorizedHeaders('testUser')
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

    public function testGETOtherUserByAdmin()
    {
        $this->createUser('testAdminUser', 'safePass', ['ROLE_ADMIN']);
        $otherUser = $this->createUser('testOtherUser');

        $response = $this->client->get('/api/users/'.$otherUser->getId(), [
            'headers' => $this->getAuthorizedHeaders('testAdminUser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'username',
            'email'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'username', 'testOtherUser');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            '_links.self',
            $this->adjustUri('/api/users/'.$otherUser->getId())
        );
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorUsernameEmpty()
    {
        $data = array(
            'email' => 'foobar@example.com',
            'password' => 'safePassword'
        );

        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.username');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.username[0]',
            'Please enter a proper username'
        );
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.email');
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorEmailEmpty()
    {
        $data = array(
            'username' => 'foobar',
            'password' => 'safePassword'
        );

        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.email');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.email[0]',
            'Please enter a proper email'
        );
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorPasswordEmpty()
    {
        $data = array(
            'username' => 'foobar',
            'email' => 'foobar@example.com'
        );

        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.password');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            'Please enter a proper password'
        );
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorEmailInvalid()
    {
        $data = array(
            'username' => 'foobarUser',
            'email' => 'example.com',
            'password' => 'safePassword'
        );

        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.email');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.email[0]',
            'This value is not a valid email address.'
        );
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testValidationErrorUsernameAlreadyTaken()
    {
        $this->createUser('testUser');

        $data = array(
            'username' => 'testUser',
            'email' => 'testUser@example.com',
            'password' => 'safePassword'
        );

        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors'
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.username');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.username[0]',
            'This username is already taken'
        );
        $this->asserter()->assertResponsePropertyExists($response, 'errors.email');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.email[0]',
            'This email address is already taken'
        );
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testInvalidJson()
    {
        $invalidJson = '{"username": "testUser, "email": "testUser@example.com", "password": "foobar"}';

        $response = $this->client->post('/api/users', [
            'body' => $invalidJson
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
        $this->createUser('testAdminUser', 'safePass', ['ROLE_ADMIN']);

        $response = $this->client->get('/api/users/fake', [
            'headers' => $this->getAuthorizedHeaders('testAdminUser')
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
            'No user found for id fake'
        );
    }

    public function testGETClientsCollectionByAdmin()
    {
        $this->createUser('testAdminUser', 'safePass', ['ROLE_ADMIN']);

        $this->createUser('testUser');

        $response = $this->client->get('/api/users', [
            'headers' => $this->getAuthorizedHeaders('testAdminUser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyIsArray($response, 'items');
        $this->asserter()->assertResponsePropertyCount($response, 'items', 3);
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].username', 'bart');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].username', 'testAdminUser');
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].username', 'testUser');
    }

    public function testGETClientsCollectionByUser()
    {
        $this->createUser('testUser');

        $response = $this->client->get('/api/users', [
            'headers' => $this->getAuthorizedHeaders('testUser')
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

    public function testGETClientsCollectionPaginated()
    {
        $this->createUser('testAdminUser', 'safePass', ['ROLE_ADMIN']);
        $this->createUser('will_not_match');

        for( $i = 0; $i < 25; $i++) {
            $this->createUser('testuser_'.$i);
        }

        $response = $this->client->get('/api/users?filter=testuser', [
            'headers' => $this->getAuthorizedHeaders('testAdminUser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].username', 'testuser_2');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 3);
        $this->asserter()->assertResponsePropertyEquals($response, 'total', 25);
        $this->asserter()->assertResponsePropertyExists($response, '_links.next');

        $nextUrl = $this->asserter()->readResponseProperty($response, '_links.next');
        $response = $this->client->get($nextUrl, [
            'headers' => $this->getAuthorizedHeaders('testAdminUser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[2].username', 'testuser_5');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 3);
        $this->asserter()->assertResponsePropertyExists($response, '_links.previous');

        $lastUrl = $this->asserter()->readResponseProperty($response, '_links.last');
        $response = $this->client->get($lastUrl, [
            'headers' => $this->getAuthorizedHeaders('testAdminUser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].username', 'testuser_24');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[1].username');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 1);
        $this->asserter()->assertResponsePropertyExists($response, '_links.first');
    }

    public function testPUTUser()
    {
        $user = $this->createUser('testuser');

        $data = array(
            'username' => 'testuser_newusername',
            'email' => 'new_email@example.com',
            'password' => 'newSecretPassword',
        );

        $response = $this->client->put('/api/users/'.$user->getId(), array(
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('testuser')
        ));
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'username',
            'email',
        ));
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals($response, 'username', 'testuser');
        $this->asserter()->assertResponsePropertyEquals($response, 'email', 'new_email@example.com');
    }

    public function testPATCHClient()
    {
        $user = $this->createUser('testuser');

        $data = array(
            'email' => 'new_email@example.com',
        );


        $response = $this->client->patch('/api/users/'.$user->getId(), array(
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('testuser')
        ));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/hal+json', $response->getHeader('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals($response, 'email', 'new_email@example.com');
    }

    public function testDELETEUser()
    {
        $user = $this->createUser('testuser');
        $this->createUser('testadminuser', 'safePass', ['ROLE_ADMIN']);

        $response = $this->client->delete('/api/users/'.$user->getId(), [
            'headers' => $this->getAuthorizedHeaders('testuser')
        ]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->client->get('/api/users/'.$user->getId(), [
            'headers' => $this->getAuthorizedHeaders('testadminuser')
        ]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDELETEOtherUser()
    {
        $this->createUser('testuser');
        $this->createUser('testadminuser', 'safePass', ['ROLE_ADMIN']);

        $otherUser = $this->createUser('testotheruser');

        $response = $this->client->delete('/api/users/'.$otherUser->getId(), [
            'headers' => $this->getAuthorizedHeaders('testuser')
        ]);
        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->client->get('/api/users/'.$otherUser->getId(), [
            'headers' => $this->getAuthorizedHeaders('testadminuser')
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->client->delete('/api/users/'.$otherUser->getId(), [
            'headers' => $this->getAuthorizedHeaders('testadminuser')
        ]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->client->get('/api/users/'.$otherUser->getId(), [
            'headers' => $this->getAuthorizedHeaders('testadminuser')
        ]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRequiresAuthentication()
    {
        $response = $this->client->get('/api/users', [
            'body' => '[]'
        ]);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }

    public function testBadToken()
    {
        $response = $this->client->get('/api/users', [
            'body' => '[]',
            'headers' => [
                'Authorization' => 'Bearer WRONG'
            ]
        ]);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type'));
    }
}
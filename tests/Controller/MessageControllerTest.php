<?php
namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Conversation;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MessageControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        // Clear the test database in the correct order
        $this->entityManager->createQuery('UPDATE App\Entity\Conversation c SET c.lastMessage = NULL')->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Message')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Participant')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Conversation')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testNewMessageSuccess(): void
    {
        // Create a test user with unique username
        $user = new User();
        $user->setUsername('testuser_' . uniqid());
        $user->setRoles(['ROLE_USER']);
        
        // Hash the password
        $passwordHasher = $this->client->getContainer()->get('security.password_hasher');
        $hashedPassword = $passwordHasher->hashPassword($user, 'testpass');
        $user->setPassword($hashedPassword);
        
        // Create a conversation
        $conversation = new Conversation();
        
        // Persist both entities
        $this->entityManager->persist($user);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        // Login the user
        $this->client->loginUser($user);

        // Create the POST request
        $this->client->request(
            'POST',
            '/messages/' . $conversation->getId(),
            ['content' => 'Test message']
        );

        // Debug response
        $response = $this->client->getResponse();
        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            var_dump($response->getContent());
        }

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testNewMessageWithEmptyContent(): void
    {
        // Create a test user with unique username
        $user = new User();
        $user->setUsername('testuser_' . uniqid());
        $user->setRoles(['ROLE_USER']);
        
        // Hash the password
        $passwordHasher = $this->client->getContainer()->get('security.password_hasher');
        $hashedPassword = $passwordHasher->hashPassword($user, 'testpass');
        $user->setPassword($hashedPassword);
        
        // Create a conversation
        $conversation = new Conversation();
        
        // Persist both entities
        $this->entityManager->persist($user);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        // Login the user
        $this->client->loginUser($user);

        // Create the POST request with empty content
        $this->client->request(
            'POST',
            '/messages/' . $conversation->getId(),
            ['content' => '']
        );

        $response = $this->client->getResponse();
        
        // Verify that we get a 404 Not Found response (as specified in the controller)
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testNewMessageUnauthenticated(): void
    {
        // Create a conversation
        $conversation = new Conversation();
        
        // Persist the conversation
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        // Try to create a message without being authenticated
        $this->client->request(
            'POST',
            '/messages/' . $conversation->getId(),
            ['content' => 'Test message']
        );

        $response = $this->client->getResponse();
        var_dump($response->getContent()); // Debug output
        
        // Should get a 401 Unauthorized response
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        
        // Check response content
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Authentication Required', $content['message']);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        parent::tearDown();
    }
}

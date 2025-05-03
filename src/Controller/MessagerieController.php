<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use App\Entity\User;

final class MessagerieController extends AbstractController
{
    #[Route('/', name: 'app_messagerie')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        $username = $user->getUserIdentifier();
        
        // Create JWT Configuration with properly encoded key
        $jwtSecret = $this->getParameter('mercure_jwt_secret');
        
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret)
        );

        // Build the token with expiration
        $token = $config->builder()
            ->withClaim('mercure', ['subscribe' => [sprintf('/%s', $username)]])
            ->expiresAt(new \DateTimeImmutable('+2 hours'))
            ->getToken($config->signer(), $config->signingKey());

        $response = $this->render('messagerie/index.html.twig', [
            'controller_name' => 'MessagerieController',
        ]);

        $response->headers->setCookie(
            new Cookie(
                'mercureAuthorization',
                $token->toString(),
                (new \DateTime())->add(new \DateInterval('PT2H')),
                '/.well-known/mercure',
                null,
                false,
                true,
                false,
                'strict'
            )
        );

        return $response;
    }
}

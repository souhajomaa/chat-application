<?php

namespace App\Controller;

use App\Entity\Conversation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    #[Route('/messages/{id}', name: 'getMessages')]
    public function index(Request $request, Conversation $conversation): Response
    
    {
        $this->denyAccessUnlessGranted('view', $conversation);

        return $this->render('message/index.html.twig', parameters: [
            'controller_name' => 'MessageController',
            'conversation' => $conversation,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Participant;
use App\Entity\User;  // Ajoute cette ligne
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;

final class ConversationController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private ConversationRepository $conversationRepository;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ConversationRepository $conversationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->conversationRepository = $conversationRepository;
    }

    #[Route('/conversation/{id}', name: 'app_conversation', methods: ['GET'])]
    public function index(Request $request, int $id): Response
    {
        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new Exception("Utilisateur non authentifié.");
        }

        // Récupérer l'autre utilisateur
        $otherUser = $this->userRepository->find($id);
        if (!$otherUser instanceof User) {
            throw new Exception("L'utilisateur n'a pas été trouvé.");
        }

        // Vérifier si on essaie de créer une conversation avec soi-même
        if ($otherUser->getId() === $currentUser->getId()) {
            throw new Exception("Impossible de créer une conversation avec soi-même.");
        }

        // Vérifier si la conversation existe déjà
        $conversation = $this->conversationRepository->findConversationByParticipants(
            $otherUser->getId(),
            $currentUser->getId()
        );

        if ($conversation) {
            return $this->json([
                'message' => 'La conversation existe déjà.',
                'conversationId' => $conversation->getId(),
            ]);
        }

        // Création de la nouvelle conversation
        $conversation = new Conversation();
        $participant = new Participant();
        $participant->setUser($currentUser);
        $participant->setConversation($conversation);

        $otherParticipant = new Participant();
        $otherParticipant->setUser($otherUser);
        $otherParticipant->setConversation($conversation);

        $this->entityManager->beginTransaction();

        try {
            $this->entityManager->persist($conversation);
            $this->entityManager->persist($participant);
            $this->entityManager->persist($otherParticipant);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->json([
            'id' => $conversation->getId()
        ], Response::HTTP_CREATED,[],[]);
    }
}

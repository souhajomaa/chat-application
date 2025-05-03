<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Conversation;
use App\Repository\MessageRepository;
use App\Repository\ParticipantRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/messages', name: 'messages_')]
class MessageController extends AbstractController
{
    private const SERIALIZATION_GROUPS = [
        'id',
        'content',
        'createdAt',
        'mine',
        'user'
    ];
    
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
        private readonly ParticipantRepository $participantRepository,
        private readonly HubInterface $hub,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/{id}', name: 'getMessages', methods: ['GET'])]
    public function index(Conversation $conversation): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $conversation);
        
        $messages = $this->messageRepository->findMessageByConversationId($conversation->getId());
        /** @var User|null */
        $currentUser = $this->getUser();
        $currentUserId = $currentUser?->getId();
        
        $messagesArray = array_map(function (Message $message) use ($currentUserId) {
            $messageUserId = $message->getUser()?->getId();
            $message->setMine($currentUserId !== null && $messageUserId !== null && $messageUserId === $currentUserId);
            return $message;
        }, $messages);
        
        return $this->json([
            'messages' => $messagesArray
        ], Response::HTTP_OK, [], [
            'groups' => self::SERIALIZATION_GROUPS
        ]);
    }

    #[Route('/{id}', name: 'newMessage', methods: ['POST'])]
    public function newMessage(Request $request, Conversation $conversation): JsonResponse 
    {
        $this->denyAccessUnlessGranted('view', $conversation);
        
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json([
                'message' => 'Authentication Required'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $content = $request->request->get('content');
        if (!$content) {
            return $this->json([
                'message' => 'Content cannot be empty'
            ], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setContent($content);
        $message->setUser($currentUser);
        $message->setMine(true);
        $message->setConversation($conversation);
        
        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->persist($message);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // Publish update to Mercure
            $update = new Update(
                sprintf("/conversations/%s", $conversation->getId()),
                $this->serializer->serialize($message, 'json', ['groups' => self::SERIALIZATION_GROUPS])
            );
            
            $this->hub->publish($update);

            return $this->json(
                $message,
                Response::HTTP_CREATED,
                [],
                ['groups' => self::SERIALIZATION_GROUPS]
            );
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return $this->json([
                'message' => 'An error occurred while saving the message'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

namespace App\Security\voter;

use App\Entity\User;
use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ConversationVoter extends Voter
{
    private $conversationRepository;

    public function __construct(ConversationRepository $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }

    const VIEW = 'view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Conversation;
    }

    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Conversation) {
            return false;
        }

        $user = $token->getUser();
        
        // Change the type check to use App\Entity\User
        if (!$user instanceof User) {
            return false;
        }

        // Now we can safely call getId() since we've confirmed it's our User entity
        $result = $this->conversationRepository->checkIfUserIsParticipant(
            $subject,
            $user->getId()
        );

        return (bool) $result;
    }
}

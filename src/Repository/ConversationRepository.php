<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findConversationByParticipants(int $otherUserId, int $myId): ?Conversation
    {
        $qb = $this->createQueryBuilder('c');
        
        $qb->innerJoin('c.participants', 'p')
            ->where('p.user IN (:users)')
            ->groupBy('c.id')
            ->having('COUNT(p.user) = 2')
            ->setParameter('users', value: [$myId, $otherUserId]);
    
        return $qb->getQuery()->getOneOrNullResult();
    }
    /**
 * @return array<int, array{
 *     conversationId: int,
 *     userId: int,
 *     username: string,
 *     content: string|null,
 *     createdAt: \DateTimeInterface|null
 * }>
 */
public function findConversationsByUser(int $userId): array

    {
        $qb = $this->createQueryBuilder('c');
    
        $qb->select('otherUser.username', 'c.id as conversationId', 'lm.content', 'lm.createdAt')
            ->innerJoin('c.participants', 'p', Join::WITH, $qb->expr()->neq('p.user', ':user'))
            ->innerJoin('c.participants', 'me', Join::WITH, $qb->expr()->eq('me.user', ':user'))
            ->leftJoin('c.lastMessage', 'lm')
            ->innerJoin('p.user', 'otherUser')
            ->setParameter('user', $userId)
            ->orderBy('lm.createdAt', 'DESC');
    
        return $qb->getQuery()->getResult();
    }
    

    public function checkIfUserIsParticipant(Conversation $conversation, int $userId): bool
{
    $qb = $this->createQueryBuilder('c');
    $qb
        ->innerJoin('c.participants', 'p')
        ->where('c.id = :conversationId')
        ->andWhere($qb->expr()->eq('p.user', ':userId'))
        ->setParameter('conversationId', $conversation->getId())
        ->setParameter('userId', $userId);
        
    return $qb->getQuery()->getOneOrNullResult() ;
}

}

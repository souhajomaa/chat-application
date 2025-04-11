<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    //    /**
    //     * @return Conversation[] Returns an array of Conversation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conversation
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findConversationByParticipants(int $otherUserId, int $myId): ?Conversation
    {
        $qb = $this->createQueryBuilder('c');
        
        $qb->innerJoin('c.participants', 'p')
        ->where('p.user IN (:users)')
        ->groupBy('c.id')
        ->having('COUNT(p.user) = 2')
        ->setParameter('users', [$myId, $otherUserId]);
    
        return $qb->getQuery()->getOneOrNullResult(); // Retourne une conversation ou null
    }
    public function findConversationsByUser(int $userId){

        $qb = $this->createQueryBuilder('c');
        $qb->select('otherUser.username','c.id as conversationId','lm.content','lm.createdAt')
        ->innerJoin('c.participants','p',Join::WITH, $qb->expr()->neq('p.user',':user'))
        ->innerJoin('c.participants','me',Join::WITH, $qb->expr()->eq('me.user',':user'))
        ->leftJoin('c.lastMessage','lm')
        ->innerJoin('p.user','otherUser')
        ->where('meUser.id :user')
        ->setParameter('user',$userId)
        ->orderBy('lm.createdAt','DESC')
        ;


        return $qb->getQuery()->getResult(); // Retourne une conversation ou null

    }
}

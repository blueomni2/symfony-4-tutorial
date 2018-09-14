<?php

namespace App\Controller;

use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_USER')")
 * @Route("/following")
 */
class FollowingController extends Controller
{
    /**
     * @Route("/follow/{id}", name="following_follow")
     */
    public function follow(User $userToFollow)
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if($userToFollow->getId() !== $currentUser->getId()) {
            $currentUser->follow($userToFollow);

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('micro_post_user', ['username' => $userToFollow->getUsername()]);
    }

    /**
     * @Route("/unfollow/{id}", name="following_unfollow")
     */
    public function unfollow(User $userToUnFollow)
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $currentUser->getFollowing()->removeElement($userToUnFollow);

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('micro_post_user', ['username' => $userToUnFollow->getUsername()]);
    }
}
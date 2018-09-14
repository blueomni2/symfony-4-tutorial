<?php

namespace App\Controller;

use App\Entity\MicroPost;
use App\Entity\User;
use App\Form\MicroPostType;
use App\Repository\MicroPostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/micro-post")
 *
 * Class MicroPostController
 * @package App\Controller
 */
class MicroPostController
{

    /**
     * @var \Twig_Environment
     */
    private $twig;
    /**
     * @var MicroPostRepository
     */
    private $microPostRepository;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        \Twig_Environment $twig,
        MicroPostRepository $microPostRepository,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $em,
        RouterInterface $router,
        FlashBagInterface $flashBag,
        AuthorizationCheckerInterface $authorizationChecker
    )
    {
        $this->twig = $twig;
        $this->microPostRepository = $microPostRepository;
        $this->formFactory = $formFactory;
        $this->em = $em;
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/", name="micro_post_index")
     */
    public function index(TokenStorageInterface $tokenStorage, UserRepository $userRepository)
    {
        $currentUser = $tokenStorage->getToken()->getUser();

        $usersToFollow = [];

        if($currentUser instanceof User) {
            $posts = $this->microPostRepository->findAllByUsers($currentUser->getFollowing());

            $usersToFollow = count($posts) === 0
                ? $userRepository->findAllWithMoreThan5PostsExceptUser($currentUser)
                : [];
        } else {
            $posts = $this->microPostRepository->findBy([], ['time' => 'DESC']);
        }

        $html = $this->twig->render('micro-post/index.html.twig', [
            'posts' => $posts,
            'usersToFollow' => $usersToFollow
        ]);

        return new Response($html);
    }

    /**
     * @Route("/edit/{id}", name="micro_post_edit")
     * @Security("is_granted('edit', microPost)", message="Access denied")
     */
    public function edit(MicroPost $microPost, Request $request)
    {
        //$this->>denyUnlessGranted('edit', $microPost);
//        if(!$this->authorizationChecker->isGranted('edit', $microPost)) {
//            throw new UnauthorizedHttpException();
//        }

        $form = $this->formFactory->create(MicroPostType::class, $microPost);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return new RedirectResponse($this->router->generate('micro_post_index'));
        }

        return new Response(
            $this->twig->render('micro-post/add.html.twig', [
                'form' => $form->createView()
            ])
        );
    }

    /**
     * @Route("/delete/{id}", name="micro_post_delete")
     * @Security("is_granted('delete', microPost)", message="Access denied")
     */
    public function delete(MicroPost $microPost)
    {
        $this->em->remove($microPost);
        $this->em->flush();

        $this->flashBag->add('notice', 'Micro post was deleted');

        return new RedirectResponse($this->router->generate('micro_post_index'));
    }

    /**
     * @Route("/add", name="micro_post_add")
     * @Security("is_granted('ROLE_USER')", message="Access denied")
     */
    public function add(Request $request, TokenStorageInterface $tokenStorage)
    {
        $user = $tokenStorage->getToken()->getUser();
        $microPost = new MicroPost();
        $microPost->setUser($user);

        $form = $this->formFactory->create(MicroPostType::class, $microPost);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($microPost);
            $this->em->flush();

            return new RedirectResponse($this->router->generate('micro_post_index'));
        }

        return new Response(
            $this->twig->render('micro-post/add.html.twig', [
                'form' => $form->createView()
            ])
        );
    }

    /**
     * @Route("/user/{username}", name="micro_post_user")
     */
    public function userPosts(User $userWithPosts)
    {
        $html = $this->twig->render('micro-post/user-posts.html.twig', [
//            'posts' => $this->microPostRepository->findBy(['user' => $userWithPosts], ['time' => 'DESC'])
            'posts' => $userWithPosts->getPosts(),
            'user' => $userWithPosts
        ]);

        return new Response($html);
    }

    /**
     * @Route("/{id}", name="micro_post_post")
     */
    public function post(MicroPost $post)
    {
        return new Response(
            $this->twig->render(
                'micro-post/post.html.twig', [
                    'post' => $post
                ]
            )
        );
    }
}
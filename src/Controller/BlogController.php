<?php

namespace App\Controller;

use App\Service\Greeting;
use App\Service\VeryBadDesign;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 *
 * @Route("/blog")
 *
 * Class BlogController
 * @package App\Controller
 */
class BlogController extends AbstractController
{

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="blog_index")
     *
     * @param $name
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        return $this->render('blog/index.html.twig', [
            'posts' => $this->session->get('posts')
        ]);
    }

    /**
     * @Route("/add", name="blog_add")
     */
    public function add()
    {
        $posts = $this->session->get('posts');

        $posts[uniqid('', true)] = [
            'title' => 'A random title '.rand(1, 500),
            'text' => 'Some random text nr '.rand(1, 500),
            'date' => new \DateTime()
        ];

        $this->session->set('posts', $posts);

        return $this->redirectToRoute('blog_index');
    }

    /**
     * @Route("/show/{id}", name="blog_show")
     *
     * @param $id
     * @return \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function show($id)
    {
        $posts = $this->session->get('posts');

        if(!$posts || !isset($posts[$id])) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('blog/post.html.twig', [
            'id' => $id,
            'post' => $posts[$id]
        ]);
    }
}
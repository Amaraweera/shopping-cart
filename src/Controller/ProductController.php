<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Prod;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProductController extends AbstractController
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="product")
     */
    public function index(): Response
    {
        $products = $this->getDoctrine()->getRepository(Prod::class)->findAll();

        $form = $this->createFormBuilder([
            'action' => $this->generateUrl('add_to_cart'),
            'method' => 'GET',
        ])->getForm();

        return $this->render('product/index.html.twig', [
            'form'  =>  $form->createView(),
            'products' => $products,
            "cart"  => $this->session->get('cart_item')
        ]);
    }

    /**
     * @Route("/product/add", name="add")
     */
    public function addProduct(Request $request): Response
    {
        $product = new Prod();

        $form = $this->createFormBuilder($product)
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('description', TextareaType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product       = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product');
        }

        return $this->render('product/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product/edit/{id}", name="edit")
     */
    public function editProduct(Request $request): Response
    {
        $id = $request->get('id');
        $product = $this->getDoctrine()->getRepository(Prod::class)->find($id);

        $form = $this->createFormBuilder($product)
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('description', TextareaType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product       = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product');
        }

        return $this->render('product/edit.html.twig', [
            'prod' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product/addToCart", name="add_to_cart")
     */
    public function addToCart(Request $request): Response
    {
        $cartAr = $this->session->get('cart_item');

        $qty = $request->get('qty');
        $productId = $request->get('id');

        $prod = $this->getDoctrine()->getRepository(Prod::class)->find($productId);

        if (isset($cartAr[$productId])) {
            $qty = $cartAr[$productId]['prod_qty'] + $qty;

            $cartAr[$productId]['prod_qty']   = $qty;
            $cartAr[$productId]['item_total'] = $prod->getPrice() * $qty;
        } else {
            $cartAr[$productId]['prod_id']    = $productId;
            $cartAr[$productId]['prod_name']  = $prod->getName();
            $cartAr[$productId]['prod_qty']   = $qty;
            $cartAr[$productId]['unit_price'] = $prod->getPrice();
            $cartAr[$productId]['item_total'] = $prod->getPrice() * $qty;
        }

        $this->session->set('cart_item', $cartAr);

        return $this->redirectToRoute('product');
    }

    /**
     * @Route("/product/deleteCartItem/{id}/{action}", name="delete_item")
     */
    public function deleteCartItem(Request $request): Response
    {
        $action = $request->get('action');
        $id = $request->get('id');

        $cartAr = $this->session->get('cart_item');

        // if action is "delete_item" delete one item else delete all items
        if ($action === 'delete_item') {
            unset($cartAr[$id]);
            $this->session->set('cart_item', $cartAr);
        } else
            $this->session->remove('cart_item');

        return $this->redirectToRoute('product');
    }

    /**
     * @Route("/product/checkout", name="checkout")
     */
    public function checkout(Request $request): Response
    {
        $cartAr = $this->session->get('cart_item');


        return $this->redirectToRoute('product');
    }
}

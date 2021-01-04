<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Prod;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
        $prods = $this->getDoctrine()->getRepository(Prod::class)->findAll();

        $form = $this->createFormBuilder([
            'action' => $this->generateUrl('add_to_cart'),
            'method' => 'GET',
        ])->getForm();

        return $this->render('product/index.html.twig', [
            'form' =>  $form->createView(),
            'prods' => $prods,
            "cart" => $this->session->get('cart_item')
        ]);
    }

    /**
     * @Route("/product/addProd", name="add_prod")
     */
    public function addProd(Request $request): Response
    {
        $prod = new Prod();
        $form = $this->createForm(ProductType::class, $prod);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
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
     * @Route("/product/editProd/{id}", name="edit_prod")
     */
    public function editProd(Request $request): Response
    {
        $id = $request->get('id');
        $prod = $this->getDoctrine()->getRepository(Prod::class)->find($id);
        $form = $this->createForm(ProductType::class, $prod);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product');
        }

        return $this->render('product/edit.html.twig', [
            'prod' => $prod,
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
        $prodId = $request->get('id');

        $prod = $this->getDoctrine()->getRepository(Prod::class)->find($prodId);

        $cartAr[$prodId]['prod_id'] = $prodId;
        $cartAr[$prodId]['prod_name'] = $prod->getName();
        $cartAr[$prodId]['prod_qty'] = $qty;
        $cartAr[$prodId]['unit_price'] = $prod->getPrice();
        $cartAr[$prodId]['item_total'] = $prod->getPrice() * $qty;

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

        if ($action == 'delete_item') { // Delete selected item
            unset($cartAr[$id]);
            $this->session->set('cart_item', $cartAr);
        } else // Delete all items
            $this->session->remove('cart_item');

        return $this->redirectToRoute('product');
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderMap;
use DateTime;
use Exception;
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
     * @Route("/", methods="GET", name="product")
     */
    public function index(): Response
    {
        $products = $this->getDoctrine()->getRepository(Product::class)
            ->findAll();

        $orders = $this->getDoctrine()->getRepository(Order::class)->findAll();
        $form   = $this->createFormBuilder([
            'action' => $this->generateUrl('add_to_cart'),
            'method' => 'GET'
        ])
            ->getForm();

        return $this->render('product/index.html.twig', [
            'form'     =>  $form->createView(),
            'products' => $products,
            'orders'   => $orders,
            'cart'     => $this->session->get('cart_item')
        ]);
    }

    /**
     * @Route("/product/add", methods={"GET", "POST"}, name="add")
     */
    public function addProduct(Request $request): Response
    {
        $product = new Product();
        $form    = $this->createFormBuilder($product)
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('description', TextareaType::class, ['required' => false])
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
     * @Route("/product/edit/{id}", methods={"GET", "POST"}, name="edit")
     */
    public function editProduct(Request $request): Response
    {
        $id      = $request->get('id');
        $product = $this->getDoctrine()->getRepository(Product::class)
            ->find($id);

        $form = $this->createFormBuilder($product)
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('description', TextareaType::class, ['required' => false])
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
     * @Route("/product/addToCart", methods="POST", name="add_to_cart")
     */
    public function addToCart(Request $request): Response
    {
        $cart      = $this->session->get('cart_item');
        $quantity  = $request->get('quantity');
        $productId = $request->get('id');
        $prod      = $this->getDoctrine()->getRepository(Product::class)
            ->find($productId);

        //If product is exsist in cart increment quantity else add to new product
        if (isset($cart[$productId])) {
            $quantity = $cart[$productId]['quantity'] + $quantity;

            $cart[$productId]['quantity']   = $quantity;
            $cart[$productId]['item_total'] = $prod->getPrice() * $quantity;
        } else {
            $cart[$productId]['product_id']    = $productId;
            $cart[$productId]['product_name']  = $prod->getName();
            $cart[$productId]['quantity']      = $quantity;
            $cart[$productId]['unit_price']    = $prod->getPrice();
            $cart[$productId]['item_total']    = $prod->getPrice() * $quantity;
        }

        $this->session->set('cart_item', $cart);

        return $this->redirectToRoute('product');
    }

    /**
     * @Route("/product/deleteCartItem/{id}/{action}", methods="GET", name="delete_item")
     */
    public function deleteCartItem(Request $request): Response
    {
        $action = $request->get('action');
        $id     = $request->get('id');
        $cart   = $this->session->get('cart_item');

        // if action is "delete_item" delete one item else delete all items
        if ($action === 'delete_item') {
            unset($cart[$id]);

            $this->session->set('cart_item', $cart);
            return $this->json(['isDelete' => true]);
        } else {
            $this->session->remove('cart_item');
            return $this->redirectToRoute('product');
        }
    }

    /**
     * @Route("/product/delete/{id}", methods="GET", name="delete")
     */
    public function delete(Request $request): Response
    {
        $id   = $request->get('id');
        $cart = $this->session->get('cart_item');

        try {
            $product = $this->getDoctrine()->getRepository(Product::class)
                ->find($id);

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->remove($product);

            foreach ($product->getOrderMap() as $attr) {
                $entityManager->remove($attr);
            }

            $entityManager->flush();

            // Delete product from cart
            if (isset($cart[$id])) {
                unset($cart[$id]);

                $this->session->set('cart_item', $cart);
            }
        } catch (Exception $e) {
            return $this->json(['isDelete' => false, 'error' => $e]);
        }

        return $this->json(['isDelete' => true]);
    }

    /**
     * @Route("/product/checkout", methods="GET", name="checkout")
     */
    public function checkout(Request $request): Response
    {
        $cartItems     = $this->session->get('cart_item');
        $order         = new Order();
        $orderMap      = new OrderMap();
        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->getConnection()->beginTransaction();

        try {
            // Insert order 
            if (!empty($cartItems)) {
                // Place order
                // Active - A, I - Inactive
                $order->setStatus("A");
                $order->setDate(new DateTime());

                $entityManager->persist($order);
                foreach ($cartItems as $key => $cartItem) {
                    $orderMap = new OrderMap();

                    // Get product object 
                    $product = $this->getDoctrine()->getRepository(Product::class)
                        ->find($cartItem['product_id']);

                    $orderMap->setProduct($product);
                    $orderMap->setOrder($order);
                    $orderMap->setQuantity($cartItem['quantity']);

                    $entityManager->persist($orderMap);
                }

                $entityManager->flush();
                $entityManager->getConnection()->commit();

                $this->session->remove('cart_item');
            }
        } catch (Exception $e) {
            $entityManager->getConnection()->rollBack();
            throw $e;
        }

        return $this->redirectToRoute('product');
    }

    /**
     * @Route("/order/detail/{id}", methods="GET", name="detail")
     */
    public function orderDetail(Request $request): Response
    {
        $id         = $request->get('id');
        $orderItems = $this->getDoctrine()
            ->getRepository(OrderMap::class)->findBy(
                ['order' => $id]
            );

        return $this->render('product/detail.html.twig', [
            'orderItems' => $orderItems
        ]);
    }

    /**
     * @Route("/product/validateName/{name}", methods="GET", name="validate_name")
     */
    public function validateName(Request $request): Response
    {
        $name    = $request->get('name');
        $product = $this->getDoctrine()->getRepository(Product::class)
            ->findBy(['name' => $name]);

        $isExsist = $product ? true : false;

        return $this->json(['isExsist' => $isExsist]);
    }
}

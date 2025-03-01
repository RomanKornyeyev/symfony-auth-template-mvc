<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Connection;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\TestType;

use PDO;
use PDOException;

class TestController extends AbstractController
{
    #[Route('/test/number')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return $this->render('test/number.html.twig', [
            'number' => $number,
        ]);
    }

    #[Route('/usuarios', name: 'usuarios_list')]
    public function index(Connection $connection): JsonResponse
    {
        // Ejecutar consulta SQL directa sin Repository
        $sql = "SELECT * FROM usuario";
        $usuarios = $connection->fetchAllAssociative($sql);

        return $this->json($usuarios);
    }

    #[Route('/test/forms', name: 'app_test_forms')]
    public function test_forms(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TestType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Formulario válido');
        }else if ($form->isSubmitted()) {
            $this->addFlash('error', 'Formulario inválido');
        }

        return $this->render('test/form_test.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
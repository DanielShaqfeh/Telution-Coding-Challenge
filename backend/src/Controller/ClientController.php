<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients')]
class ClientController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, ClientRepository $clientRepository): JsonResponse
    {
        $search = $request->query->get('search', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        if ($search) {
            $clients = $clientRepository->searchByNameOrCompany($search, $limit, $offset);
            $total = $clientRepository->countByNameOrCompany($search);
        } else {
            $clients = $clientRepository->findBy([], ['id' => 'DESC'], $limit, $offset);
            $total = $clientRepository->count([]);
        }

        $data = array_map(fn(Client $c) => [
            'id'      => $c->getId(),
            'name'    => $c->getName(),
            'email'   => $c->getEmail(),
            'company' => $c->getCompany(),
            'address' => $c->getAddress(),
        ], $clients);

        return $this->json([
            'data'  => $data,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
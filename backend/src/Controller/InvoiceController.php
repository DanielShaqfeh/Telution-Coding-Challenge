<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Repository\ClientRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices')]
class InvoiceController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, InvoiceRepository $invoiceRepository): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $invoices = $invoiceRepository->findBy([], ['id' => 'DESC'], $limit, $offset);
        $total    = $invoiceRepository->count([]);

        $data = array_map(fn(Invoice $inv) => $this->serializeInvoice($inv), $invoices);

        return $this->json([
            'data'  => $data,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        ClientRepository $clientRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['clientId'], $body['items']) || empty($body['items'])) {
            return $this->json(['error' => 'clientId and items are required'], 400);
        }

        $client = $clientRepository->find($body['clientId']);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCreatedAt(new \DateTimeImmutable());

        $total = 0;
        foreach ($body['items'] as $itemData) {
            if (!isset($itemData['description'], $itemData['quantity'], $itemData['unitPrice'])) {
                return $this->json(['error' => 'Each item needs description, quantity, and unitPrice'], 400);
            }

            $qty   = (int) $itemData['quantity'];
            $price = (float) $itemData['unitPrice'];

            if ($qty <= 0 || $price <= 0) {
                return $this->json(['error' => 'Quantity and unitPrice must be positive'], 400);
            }

            $subtotal = $qty * $price;
            $total   += $subtotal;

            $item = new InvoiceItem();
            $item->setDescription($itemData['description']);
            $item->setQuantity($qty);
            $item->setUnitPrice((string) $price);
            $item->setSubtotal((string) $subtotal);
            $item->setInvoice($invoice);

            $em->persist($item);
        }

        $invoice->setTotal((string) $total);
        $em->persist($invoice);
        $em->flush();

        return $this->json($this->serializeInvoice($invoice), 201);
    }

    #[Route('/export', methods: ['GET'])]
    public function export(InvoiceRepository $invoiceRepository): \Symfony\Component\HttpFoundation\Response
    {
        $invoices = $invoiceRepository->findBy([], ['id' => 'DESC']);

        $rows   = [];
        $rows[] = implode(',', ['Invoice ID', 'Client', 'Email', 'Company', 'Total', 'Created At', 'Item Description', 'Quantity', 'Unit Price', 'Subtotal']);

        foreach ($invoices as $invoice) {
            foreach ($invoice->getItems() as $item) {
                $rows[] = implode(',', [
                    $invoice->getId(),
                    '"' . $invoice->getClient()->getName() . '"',
                    $invoice->getClient()->getEmail(),
                    '"' . $invoice->getClient()->getCompany() . '"',
                    $invoice->getTotal(),
                    $invoice->getCreatedAt()->format('Y-m-d H:i:s'),
                    '"' . $item->getDescription() . '"',
                    $item->getQuantity(),
                    $item->getUnitPrice(),
                    $item->getSubtotal(),
                ]);
            }
        }

        $csv = implode("\n", $rows);

        $response = new \Symfony\Component\HttpFoundation\Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="invoices.csv"');

        return $response;
    }

    private function serializeInvoice(Invoice $inv): array
    {
        return [
            'id'        => $inv->getId(),
            'client'    => [
                'id'      => $inv->getClient()->getId(),
                'name'    => $inv->getClient()->getName(),
                'email'   => $inv->getClient()->getEmail(),
                'company' => $inv->getClient()->getCompany(),
            ],
            'total'     => $inv->getTotal(),
            'createdAt' => $inv->getCreatedAt()->format('Y-m-d H:i:s'),
            'items'     => array_map(fn(InvoiceItem $item) => [
                'id'          => $item->getId(),
                'description' => $item->getDescription(),
                'quantity'    => $item->getQuantity(),
                'unitPrice'   => $item->getUnitPrice(),
                'subtotal'    => $item->getSubtotal(),
            ], $inv->getItems()->toArray()),
        ];
    }
}
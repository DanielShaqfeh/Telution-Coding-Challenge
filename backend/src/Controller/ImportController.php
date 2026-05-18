<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices')]
class ImportController extends AbstractController
{
    #[Route('/import', methods: ['POST'])]
    public function import(
        Request $request,
        ClientRepository $clientRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            return $this->json(['error' => 'Only CSV and Excel files are supported'], 400);
        }

        $rows = $this->parseFile($file->getPathname(), $extension);

        $created = 0; 
        $failed  = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; 

            $clientEmail = trim($row['client_email'] ?? '');
            $description = trim($row['description'] ?? '');
            $quantity    = $row['quantity'] ?? '';
            $unitPrice   = $row['unit_price'] ?? '';

            if (!$clientEmail || !$description || $quantity === '' || $unitPrice === '') {
                $failed[] = ['row' => $rowNum, 'reason' => 'Missing required fields (client_email, description, quantity, unit_price)'];
                continue;
            }

            $client = $clientRepository->findOneBy(['email' => $clientEmail]);
            if (!$client) {
                $failed[] = ['row' => $rowNum, 'reason' => "Client with email '$clientEmail' not found"];
                continue;
            }

            $qty   = (int) $quantity;
            $price = (float) $unitPrice;

            if ($qty <= 0) {
                $failed[] = ['row' => $rowNum, 'reason' => 'Quantity must be a positive integer'];
                continue;
            }

            if ($price <= 0) {
                $failed[] = ['row' => $rowNum, 'reason' => 'Unit price must be a positive number'];
                continue;
            }

            $subtotal = $qty * $price;

            $invoice = new Invoice();
            $invoice->setClient($client);
            $invoice->setCreatedAt(new \DateTimeImmutable());
            $invoice->setTotal((string) $subtotal);

            $item = new InvoiceItem();
            $item->setDescription($description);
            $item->setQuantity($qty);
            $item->setUnitPrice((string) $price);
            $item->setSubtotal((string) $subtotal);
            $item->setInvoice($invoice);

            $em->persist($invoice);
            $em->persist($item);

            $created++;
        }

        $em->flush();

        return $this->json([
            'created' => $created, 
            'failed'  => $failed, 
            'total'   => count($rows),
        ]);
    }

    private function parseFile(string $path, string $extension): array
    {
        if ($extension === 'csv') {
            return $this->parseCsv($path);
        }

        return $this->parseExcel($path);
    }

    private function parseCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');
        $header = null;

        while (($line = fgetcsv($handle)) !== false) {
            if (!$header) {
                $header = array_map('trim', $line);
                continue;
            }
            // Skip blank lines
            $nonEmpty = array_filter($line, fn($v) => trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }
            if (count($line) === count($header)) {
                $rows[] = array_combine($header, array_map('trim', $line));
            }
        }

        fclose($handle);
        return $rows;
    }

    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray();

        if (empty($data)) {
            return [];
        }

        $header = array_map('trim', $data[0]);
        $rows   = [];

        for ($i = 1; $i < count($data); $i++) {
            // Skip completely blank rows (Excel often pads sheets with empty trailing rows)
            $nonEmpty = array_filter($data[$i], fn($v) => $v !== null && trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            if (count($data[$i]) === count($header)) {
                $rows[] = array_combine($header, array_map('trim', array_map('strval', $data[$i])));
            }
        }

        return $rows;
    }
}
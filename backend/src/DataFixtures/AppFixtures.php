<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $clientsData = [
            ['Alice Johnson', 'alice@techcorp.com', 'TechCorp', '123 Silicon Valley, CA'],
            ['Bob Smith', 'bob@designstudio.com', 'Design Studio', '456 Creative Ave, NY'],
            ['Carol White', 'carol@marketingpro.com', 'Marketing Pro', '789 Brand Street, TX'],
            ['David Brown', 'david@cloudservices.com', 'Cloud Services Inc', '321 Cloud Blvd, WA'],
            ['Emma Davis', 'emma@startupxyz.com', 'Startup XYZ', '654 Innovation Road, SF'],
        ];

        $clients = [];
        foreach ($clientsData as [$name, $email, $company, $address]) {
            $client = new Client();
            $client->setName($name);
            $client->setEmail($email);
            $client->setCompany($company);
            $client->setAddress($address);
            $manager->persist($client);
            $clients[] = $client;
        }

        $invoicesData = [
            [
                'client' => 0,
                'items'  => [
                    ['Web Development', 10, 150.00],
                    ['UI Design', 5, 80.00],
                ],
            ],
            [
                'client' => 1,
                'items'  => [
                    ['Logo Design', 1, 500.00],
                    ['Brand Guidelines', 1, 300.00],
                    ['Business Cards Design', 2, 75.00],
                ],
            ],
            [
                'client' => 2,
                'items'  => [
                    ['Social Media Campaign', 1, 1200.00],
                    ['Content Writing', 8, 50.00],
                ],
            ],
            [
                'client' => 3,
                'items'  => [
                    ['Cloud Migration', 1, 2500.00],
                    ['Setup & Configuration', 3, 200.00],
                ],
            ],
            [
                'client' => 4,
                'items'  => [
                    ['MVP Development', 20, 100.00],
                    ['Technical Consulting', 4, 150.00],
                    ['DevOps Setup', 1, 800.00],
                ],
            ],
        ];
        
        foreach ($invoicesData as $invoiceData) {
            $invoice = new Invoice();
            $invoice->setClient($clients[$invoiceData['client']]);
            $invoice->setCreatedAt(new \DateTimeImmutable());

            $total = 0;
            foreach ($invoiceData['items'] as [$description, $quantity, $unitPrice]) {
                $subtotal = $quantity * $unitPrice;
                $total   += $subtotal;

                $item = new InvoiceItem();
                $item->setDescription($description);
                $item->setQuantity($quantity);
                $item->setUnitPrice((string) $unitPrice);
                $item->setSubtotal((string) $subtotal);
                $item->setInvoice($invoice);
                $manager->persist($item);
            }

            $invoice->setTotal((string) $total);
            $manager->persist($invoice);
        }

        $manager->flush();
    }
}
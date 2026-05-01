import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService, Invoice, InvoiceItem } from '../../services/api';

@Component({
  selector: 'app-invoice-list',
  imports: [CommonModule, RouterLink],
  templateUrl: './invoice-list.html',
  styleUrl: './invoice-list.css'
})
export class InvoiceListComponent implements OnInit {
  invoices: Invoice[] = [];
  page = 1;
  total = 0;
  limit = 10;
  loading = false;
  expandedId: number | null = null;

  // Modal
  modalItem: InvoiceItem | null = null;
  modalInvoice: Invoice | null = null;

  constructor(private api: ApiService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading = true;
    this.api.getInvoices(this.page).subscribe({
      next: (res) => { this.invoices = res.data; this.total = res.total; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  toggleExpand(id: number): void {
    this.expandedId = this.expandedId === id ? null : id;
  }

  openModal(invoice: Invoice, item: InvoiceItem, event: Event): void {
    event.stopPropagation();
    this.modalInvoice = invoice;
    this.modalItem = item;
  }

  closeModal(): void {
    this.modalItem = null;
    this.modalInvoice = null;
  }

  exportCsv(): void { this.api.exportInvoices(); }
  prevPage(): void { if (this.page > 1) { this.page--; this.load(); } }
  nextPage(): void { if (this.page * this.limit < this.total) { this.page++; this.load(); } }
  get totalPages(): number { return Math.ceil(this.total / this.limit); }
}

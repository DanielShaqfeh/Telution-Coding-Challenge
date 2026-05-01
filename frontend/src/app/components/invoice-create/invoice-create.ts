import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService, Client, InvoiceItem } from '../../services/api';

@Component({
  selector: 'app-invoice-create',
  imports: [CommonModule, FormsModule],
  templateUrl: './invoice-create.html',
  styleUrl: './invoice-create.css'
})
export class InvoiceCreateComponent implements OnInit {
  clients: Client[] = [];
  selectedClientId: number | null = null;
  items: InvoiceItem[] = [{ description: '', quantity: 1, unitPrice: 0 }];
  submitting = false;
  error = '';
  success = false;

  constructor(private api: ApiService, private router: Router) {}

  ngOnInit(): void {
    this.api.getClients('', 1).subscribe({
      next: (res) => this.clients = res.data
    });
  }

  addItem(): void {
    this.items.push({ description: '', quantity: 1, unitPrice: 0 });
  }

  removeItem(index: number): void {
    if (this.items.length > 1) this.items.splice(index, 1);
  }

  get total(): number {
    return this.items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0);
  }

  submit(): void {
    if (!this.selectedClientId) { this.error = 'Please select a client.'; return; }
    const invalid = this.items.some(i => !i.description || i.quantity <= 0 || i.unitPrice <= 0);
    if (invalid) { this.error = 'All items must have a description, quantity > 0, and unit price > 0.'; return; }

    this.submitting = true;
    this.error = '';

    this.api.createInvoice(this.selectedClientId, this.items).subscribe({
      next: () => {
        this.submitting = false;
        this.success = true;
        setTimeout(() => this.router.navigate(['/invoices']), 1500);
      },
      error: (err) => {
        this.submitting = false;
        this.error = err.error?.error || 'Failed to create invoice.';
      }
    });
  }
}
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService, ImportResult } from '../../services/api';

@Component({
  selector: 'app-invoice-import',
  imports: [CommonModule],
  templateUrl: './invoice-import.html',
  styleUrl: './invoice-import.css'
})
export class InvoiceImportComponent {
  selectedFile: File | null = null;
  uploading = false;
  result: ImportResult | null = null;
  error = '';

  constructor(private api: ApiService) {}

  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.selectedFile = input.files?.[0] || null;
    this.result = null;
    this.error = '';
  }

  upload(): void {
    if (!this.selectedFile) { this.error = 'Please select a file.'; return; }

    this.uploading = true;
    this.error = '';
    this.result = null;

    this.api.importInvoices(this.selectedFile).subscribe({
      next: (res) => {
        this.result = res;
        this.uploading = false;
      },
      error: (err) => {
        this.error = err.error?.error || 'Import failed.';
        this.uploading = false;
      }
    });
  }
}
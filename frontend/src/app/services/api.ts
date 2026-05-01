import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Client {
  id: number;
  name: string;
  email: string;
  company: string;
  address: string;
}

export interface InvoiceItem {
  id?: number;
  description: string;
  quantity: number;
  unitPrice: number;
  subtotal?: number;
}

export interface Invoice {
  id: number;
  client: Client;
  total: string;
  createdAt: string;
  items: InvoiceItem[];
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
}

export interface ImportResult {
  created: number;
  failed: { row: number; reason: string }[];
  total: number;
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private base = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getClients(search = '', page = 1): Observable<PaginatedResponse<Client>> {
    let params = new HttpParams().set('page', page);
    if (search) params = params.set('search', search);
    return this.http.get<PaginatedResponse<Client>>(`${this.base}/clients`, { params });
  }

  getInvoices(page = 1): Observable<PaginatedResponse<Invoice>> {
    const params = new HttpParams().set('page', page);
    return this.http.get<PaginatedResponse<Invoice>>(`${this.base}/invoices`, { params });
  }

  createInvoice(clientId: number, items: InvoiceItem[]): Observable<Invoice> {
    return this.http.post<Invoice>(`${this.base}/invoices`, { clientId, items });
  }

  importInvoices(file: File): Observable<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<ImportResult>(`${this.base}/invoices/import`, formData);
  }

  exportInvoices(): void {
    window.open(`${this.base}/invoices/export`, '_blank');
  }
}
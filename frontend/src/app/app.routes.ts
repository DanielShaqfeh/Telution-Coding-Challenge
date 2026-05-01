import { Routes } from '@angular/router';
import { authGuard } from './auth.guard';
import { ClientListComponent } from './components/client-list/client-list';
import { InvoiceListComponent } from './components/invoice-list/invoice-list';
import { InvoiceCreateComponent } from './components/invoice-create/invoice-create';
import { InvoiceImportComponent } from './components/invoice-import/invoice-import';
import { LoginComponent } from './components/login/login';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: '', redirectTo: 'clients', pathMatch: 'full' },
  { path: 'clients',         component: ClientListComponent,    canActivate: [authGuard] },
  { path: 'invoices',        component: InvoiceListComponent,   canActivate: [authGuard] },
  { path: 'invoices/create', component: InvoiceCreateComponent, canActivate: [authGuard] },
  { path: 'invoices/import', component: InvoiceImportComponent, canActivate: [authGuard] },
];
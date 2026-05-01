import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InvoiceImportComponent } from './invoice-import';

describe('InvoiceImportComponent', () => {
  let component: InvoiceImportComponent;
  let fixture: ComponentFixture<InvoiceImportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [InvoiceImportComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(InvoiceImportComponent);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

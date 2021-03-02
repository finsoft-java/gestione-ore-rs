import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RaccoltaDateFirmaComponent } from './raccolta-date-firma.component';

describe('RaccoltaDateFirmaComponent', () => {
  let component: RaccoltaDateFirmaComponent;
  let fixture: ComponentFixture<RaccoltaDateFirmaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RaccoltaDateFirmaComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RaccoltaDateFirmaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

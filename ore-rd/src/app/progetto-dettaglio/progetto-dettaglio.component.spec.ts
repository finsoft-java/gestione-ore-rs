import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProgettoDettaglioComponent } from './progetto-dettaglio.component';

describe('ProgettoDettaglioComponent', () => {
  let component: ProgettoDettaglioComponent;
  let fixture: ComponentFixture<ProgettoDettaglioComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ProgettoDettaglioComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ProgettoDettaglioComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

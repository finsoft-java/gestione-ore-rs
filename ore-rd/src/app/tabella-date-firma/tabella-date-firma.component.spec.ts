import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TabellaDateFirmaComponent } from './tabella-date-firma.component';

describe('TabellaDateFirmaComponent', () => {
  let component: TabellaDateFirmaComponent;
  let fixture: ComponentFixture<TabellaDateFirmaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TabellaDateFirmaComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TabellaDateFirmaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

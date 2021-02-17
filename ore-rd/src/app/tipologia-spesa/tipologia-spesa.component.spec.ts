import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TipologiaSpesaComponent } from './tipologia-spesa.component';

describe('TipologiaSpesaComponent', () => {
  let component: TipologiaSpesaComponent;
  let fixture: ComponentFixture<TipologiaSpesaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TipologiaSpesaComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TipologiaSpesaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

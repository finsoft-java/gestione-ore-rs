import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProgettoCommesseComponent } from './progetto-commesse.component';

describe('ProgettoCommesseComponent', () => {
  let component: ProgettoCommesseComponent;
  let fixture: ComponentFixture<ProgettoCommesseComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ProgettoCommesseComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ProgettoCommesseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

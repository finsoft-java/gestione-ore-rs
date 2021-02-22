import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GeneraDatiTestComponent } from './genera-dati-test.component';

describe('GeneraDatiTestComponent', () => {
  let component: GeneraDatiTestComponent;
  let fixture: ComponentFixture<GeneraDatiTestComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GeneraDatiTestComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(GeneraDatiTestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

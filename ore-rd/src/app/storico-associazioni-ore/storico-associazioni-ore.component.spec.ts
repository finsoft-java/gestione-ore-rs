import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StoricoAssociazioniOreComponent } from './storico-associazioni-ore.component';

describe('StoricoAssociazioniOreComponent', () => {
  let component: StoricoAssociazioniOreComponent;
  let fixture: ComponentFixture<StoricoAssociazioniOreComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StoricoAssociazioniOreComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StoricoAssociazioniOreComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

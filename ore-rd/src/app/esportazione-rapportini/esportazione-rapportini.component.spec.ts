import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EsportazioneRapportiniComponent } from './esportazione-rapportini.component';

describe('EsportazioneRapportiniComponent', () => {
  let component: EsportazioneRapportiniComponent;
  let fixture: ComponentFixture<EsportazioneRapportiniComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EsportazioneRapportiniComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(EsportazioneRapportiniComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

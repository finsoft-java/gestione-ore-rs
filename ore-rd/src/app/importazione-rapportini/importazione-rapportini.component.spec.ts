import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImportazioneRapportiniComponent } from './importazione-rapportini.component';

describe('ImportazioneRapportiniComponent', () => {
  let component: ImportazioneRapportiniComponent;
  let fixture: ComponentFixture<ImportazioneRapportiniComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ImportazioneRapportiniComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ImportazioneRapportiniComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImportazioneLulComponent } from './importazione-lul.component';

describe('ImportazioneLulComponent', () => {
  let component: ImportazioneLulComponent;
  let fixture: ComponentFixture<ImportazioneLulComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ImportazioneLulComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ImportazioneLulComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

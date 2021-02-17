import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportCompletoComponent } from './report-completo.component';

describe('ReportCompletoComponent', () => {
  let component: ReportCompletoComponent;
  let fixture: ComponentFixture<ReportCompletoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReportCompletoComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ReportCompletoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

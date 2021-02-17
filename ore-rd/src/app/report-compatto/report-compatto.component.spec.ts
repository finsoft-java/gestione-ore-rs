import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportCompattoComponent } from './report-compatto.component';

describe('ReportCompattoComponent', () => {
  let component: ReportCompattoComponent;
  let fixture: ComponentFixture<ReportCompattoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReportCompattoComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ReportCompattoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

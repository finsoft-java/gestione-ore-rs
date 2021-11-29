import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MatEditTableComponent } from './mat-edit-table.component';

describe('MatEditTableComponent', () => {
  let component: MatEditTableComponent;
  let fixture: ComponentFixture<MatEditTableComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MatEditTableComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MatEditTableComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

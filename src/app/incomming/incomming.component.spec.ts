import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IncommingComponent } from './incomming.component';

describe('IncommingComponent', () => {
  let component: IncommingComponent;
  let fixture: ComponentFixture<IncommingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IncommingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(IncommingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

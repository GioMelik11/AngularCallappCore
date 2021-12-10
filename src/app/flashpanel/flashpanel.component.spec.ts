import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FlashpanelComponent } from './flashpanel.component';

describe('FlashpanelComponent', () => {
  let component: FlashpanelComponent;
  let fixture: ComponentFixture<FlashpanelComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FlashpanelComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(FlashpanelComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

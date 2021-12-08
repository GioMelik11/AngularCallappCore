import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
  @ViewChild('dropdownChange', { static: true }) dropdownChange: ElementRef<HTMLElement>;
  checkdropdown: any;

  constructor(dropdownChange: ElementRef<HTMLElement>) {
    this.dropdownChange = dropdownChange;

  }

  ngOnInit(): void {

  }

  dropdownMenu() {
    this.checkdropdown = this.dropdownChange.nativeElement.getAttribute("aria-dropdown");

    if (this.checkdropdown == "false") {
      this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "true");
      this.dropdownChange.nativeElement.style.transform = "rotate(180deg)";
    } else {
      this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
      this.dropdownChange.nativeElement.style.transform = "rotate(0deg)"
    }
  }

}

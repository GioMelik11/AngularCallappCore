import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
  @ViewChild('dropdownChange', { static: true }) dropdownChange: ElementRef<HTMLElement>;
  @ViewChild('dropdownNotif', { static: true }) dropdownNotif: ElementRef<HTMLElement>;
  @ViewChild('dropdownChat', { static: true }) dropdownChat: ElementRef<HTMLElement>;
  @ViewChild('menuAreaContent', { static: true }) menuArea: ElementRef<HTMLDivElement>;
  @ViewChild('notificationAreaContent', { static: true }) notificationArea: ElementRef<HTMLDivElement>;
  @ViewChild('chatAreaContent', { static: true }) chatArea: ElementRef<HTMLDivElement>;

  constructor(dropdown: ElementRef<HTMLElement>, divElement: ElementRef<HTMLDivElement>) {
    this.dropdownChange = dropdown;
    this.dropdownNotif = dropdown;
    this.dropdownChat = dropdown;
    this.menuArea = divElement;
    this.notificationArea = divElement;
    this.chatArea = divElement;
  }

  ngOnInit(): void {

  }

  dropdownMenu(type: any) {
    switch (type) {
      case 1:
        this.menuArea.nativeElement.getAttribute("aria-dropdown") == "false" ? this.DropDownRotate(this.menuArea.nativeElement, "show") : this.DropDownRotate(this.menuArea.nativeElement, "hide");
        this.hideDropDown(this.chatArea.nativeElement);
        this.hideDropDown(this.notificationArea.nativeElement);

        break;
      case 2:
        this.notificationArea.nativeElement.getAttribute("aria-dropdown") == "false" ? this.createContent(this.notificationArea.nativeElement) : this.hideDropDown(this.notificationArea.nativeElement);
        this.hideDropDown(this.menuArea.nativeElement);
        this.hideDropDown(this.chatArea.nativeElement);
        this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
        this.dropdownChange.nativeElement.style.transform = "rotate(0deg)";
        break;
      case 3:
        this.chatArea.nativeElement.getAttribute("aria-dropdown") == "false" ? this.createContent(this.chatArea.nativeElement) : this.hideDropDown(this.chatArea.nativeElement);
        this.hideDropDown(this.menuArea.nativeElement);
        this.hideDropDown(this.notificationArea.nativeElement);
        this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
        this.dropdownChange.nativeElement.style.transform = "rotate(0deg)";
        break;
      default:
        break;
    }

  }

  DropDownRotate(el: HTMLDivElement, check: string) {
    switch (check) {
      case "show":
        this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "true");
        this.dropdownChange.nativeElement.style.transform = "rotate(180deg)";
        this.createContent(el);
        break;
      case "hide":
        this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
        this.dropdownChange.nativeElement.style.transform = "rotate(0deg)";
        this.hideDropDown(el);
        break;
      default:
        break;
    }
  }

  hideDropDown(el: HTMLDivElement) {
    el.style.height = "0px";
    el.style.filter = "unset";
    el.setAttribute("aria-dropdown", "false");
  }

  createContent(el: HTMLDivElement) {
    el.setAttribute("aria-dropdown", "true");
    el.style.background = "#fff";
    el.style.width = "210px";
    el.style.height = "220px";
    el.style.filter = "drop-shadow(0px 1px 10px rgba(0, 0, 0, 0.08))";
  }

}

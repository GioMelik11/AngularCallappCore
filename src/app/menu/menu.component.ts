import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss']
})
export class MenuComponent implements OnInit {
  @ViewChild('menu', { static: true }) menu: ElementRef<HTMLDivElement>;

  constructor(menu: ElementRef<HTMLDivElement>) {
    this.menu = menu;
  }

  ngOnInit(): void {
  }

}

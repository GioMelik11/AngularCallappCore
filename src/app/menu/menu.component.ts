import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss']
})
export class MenuComponent {
  @ViewChild('menu', { static: true }) menu: ElementRef<HTMLDivElement>;
  menuItem: any = new Object();
  currentRoute: any;

  constructor(menu: ElementRef<HTMLDivElement>, private router: Router) {
    this.menu = menu;

    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
      .subscribe(event => {
        this.currentRoute = event as Object;
        this.currentRoute = this.currentRoute.url;
      })

    this.menuItem = [{
      id: "1",
      name: "მთავარი",
      route: "dashboard"
    }, {
      id: "1",
      name: "შემომავალი",
      route: "incomming"
    }, {
      id: "1",
      name: "ტესტ გვერდი",
      route: "test1"
    }, {
      id: "1",
      name: "ტესტ გვერდი 2",
      route: "test2"
    }];
  }

}

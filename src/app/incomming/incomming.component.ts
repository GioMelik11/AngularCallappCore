import { Component, OnInit } from '@angular/core';
import { Title } from '@angular/platform-browser';

@Component({
  selector: 'app-incomming',
  templateUrl: './incomming.component.html',
  styleUrls: ['./incomming.component.scss']
})
export class IncommingComponent implements OnInit {

  constructor(private titleService: Title) { }

  ngOnInit(): void {
    this.titleService.setTitle("შემომავალი ზარები");
  }

}
